<?php

namespace App\Services;

use Auth;
// use App\Models\User;
use App\Models\UserAddress;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\OrderItem;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
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

    public function store(UserAddress $address, $remark, $items)
    {
        $user = Auth::user();
        $order = \DB::transaction(function () use ($user, $address, $remark, $items) {

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
            $order->update(['total_amount' => $totalAmount]);
            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);
            return $order;
        }); // END TRANSACTION

        dispatch(new CloseOrder($order, config('app.order_ttl')));
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
}
// $product->sold_count = $product->sold_count + $item->amount;
            // $product->save();
