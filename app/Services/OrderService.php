<?php

namespace App\Services;

use DB;
use Auth;
use App\Models\UserAddress;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\OrderItem;
use App\Models\CouponCode;
use App\Exceptions\InvalidRequestException;
use App\Exceptions\CouponCodeUnavailableException;
use App\Jobs\CloseOrder;
use App\Jobs\RefundInstallmentOrderJob;
use Carbon\Carbon;

class OrderService
{

    public function get()
    {
        $orders = Order::query()
                ->with(['items.product', 'items.productSku'])
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->paginate();
        return $orders;
    }

    public function store(UserAddress $address, $remark, $items, CouponCode $coupon = null)
    {
        $user = Auth::user();

        if ($coupon) {
            $coupon->checkAvailable($user);
        }

        $order = \DB::transaction(function () use ($user, $address, $remark, $items, $coupon) {

            $address->update(['last_used_at' => Carbon::now()]);
            $order = new Order([
                'address'       => [
                    'address'       => $address->full_address,
                    'zip'           => $address->zip,
                    'contact_name'  => $address->contact_name,
                    'contact_phone' => $address->contact_phone,
                ],
                'remark'        => $remark,
                'total_amount'  => 0,
                'type'          => Order::TYPE_NORMAL,
            ]);
            $order->user()->associate($user); // 设置 user_id ?
            $order->save();

            $totalAmount = 0;
            foreach ($items as $data) {
                $sku = ProductSku::find($data['sku_id']);
                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price'  => $sku->price,
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $sku->price * $data['amount'];
                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('库存不足');
                }
            }
            if ($coupon) {
                $coupon->checkAvailable($user, $totalAmount);
                $totalAmount = $coupon->getAdjustedPrice($totalAmount);
                $order->couponCode()->associate($coupon);
                if ($coupon->changeUsed() <= 0) {
                    throw new CouponCodeUnavailableException('优惠券没有了');
                }
            }

            $order->update(['total_amount' => $totalAmount]);
            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);
            return $order;
        }); // END TRANSACTION

        // dispatch(new CloseOrder($order, config('app.order_ttl')));
        return $order;
    }

    public function crowdfunding(UserAddress $address, ProductSku $sku, $amount)
    {
        $user = Auth::user();
        $order = \DB::transaction(function () use ($user, $address, $sku, $amount) {
            $address->update(['last_used_at' => Carbon::now()]);
            $order = new Order([
                'address' => [
                    'address'   => $address->full_address,
                    'zip'       => $address->zip,
                    'contact_name'  => $address->contact_name,
                    'contact_phone' => $address->contact_phone,
                ],
                'remark'    => '',
                'total_amount'  => $sku->price * $amount,
                'type'      => Order::TYPE_CROWDFUNDING,
            ]);
            $order->user()->associate($user);
            $order->save();

            $item = $order->items()->make([
                'amount'    => $amount,
                'price'     => $sku->price,
            ]);
            $item->product()->associate($sku->product_id);
            $item->productSku()->associate($sku);
            $item->save();

            if ($sku->decreaseStock($amount) <= 0) {
                throw new InvalidRequestException('商品库存不足');
            }
            return $order;
        });

        $crowdfundingTtl = $sku->product->crowdfunding->end_at->getTimestamp() - time();
        dispatch(new CloseOrder($order, min(config('app.order_ttl'), $crowdfundingTtl)));
        // dispatch(new CloseOrder($order, $crowdfungingTtl));

        return $order;
    }

    public function updateProductSoldCount(Order $order)
    {
        // 预加载商品数据
        $order->load('items.product');
        // 循环遍历订单的商品
        foreach ($order->items as $item) {
            $product   = $item->product;
            // 计算对应商品的销量
            $soldCount = OrderItem::query()
                ->where('product_id', $product->id)
                ->whereHas('order', function ($query) {
                    $query->whereNotNull('paid_at');  // 关联的订单状态是已支付
                })->sum('amount');

            // 更新商品销量
            $product->sold_count = $soldCount;
            $product->save();
        }
    }

    public function updateProductRating(Order $order)
    {
        // orders:      reviewed
        // order_items: rating, review, review_at
        // product:     rating, review_count
        // product_sku: 无

        $items = $order->items()->with(['product'])->get();
        foreach ($items as $item) {
            $result = OrderItem::query()
                ->where('product_id', $item->product_id)
                ->whereNotNull('reviewed_at')
                ->whereHas('order', function ($query) {
                    $query->whereNotNull('paid_at');
                })
                ->first([
                    DB::raw('count(*) as review_count'),
                    DB::raw('avg(rating) as rating')
                ]);
            $item->product->rating = $result->rating;
            $item->product->review_count = $result->review_count;
            $item->product->save();
        }
    }

    public function refundOrder(Order $order)
    {
        switch ($order->payment_method) {
            case 'alipay':
                $refundNo = Order::getAvailableRefundNo();
                $ret = app('alipay')->refund([
                    'out_trade_no'  => $order->no,  // 原订单流水号
                    'refund_amount' => $order->total_amount,    // 退款金额
                    'out_request_no'    => $refundNo,   // 生成的退款号
                ]);
                // 根据支付宝的文档，如果返回值里有 sub_code 说明退款失败
                if ($ret->sub_code) {
                    $extra = $order->extra;
                    $extra['refund_failed_code'] = $ret->sub_code;
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_FAILED,
                        'extra' => $extra,
                    ]);
                } else {
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;
            case 'wechat':
                // 生成退款订单号
                $refundNo = Order::getAvailableRefundNo();
                app('wechat_pay')->refund([
                    'out_trade_no' => $order->no, // 之前的订单流水号
                    'total_fee' => $order->total_amount * 100, //原订单金额，单位分
                    'refund_fee' => $order->total_amount * 100, // 要退款的订单金额，单位分
                    'out_refund_no' => $refundNo, // 退款订单号
                    // 微信支付的退款结果并不是实时返回的，而是通过退款回调来通知，因此这里需要配上退款回调接口地址
                    'notify_url' => 'http://requestbin.fullcontact.com/******' // 由于是开发环境，需要配成 requestbin 地址
                ]);
                // 将订单状态改成退款中
                $order->update([
                    'refund_no' => $refundNo,
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);
                break;
            case 'installment':
                $order->update([
                    'refund_no' => Order::getAvailableRefundNo(),
                    'refund_status' => Order::REFUND_STATUS_PROCESSING, // 处理中
                ]);
                dispatch(new RefundInstallmentOrderJob($order));
                break;
            default:
                throw new InternalException('未知订单支付方式：' . $order->payment_method);
                break;
        }
    }
}
