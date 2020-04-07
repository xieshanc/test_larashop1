<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\UserAddress;
use App\Models\CouponCode;
use App\Services\OrderService;
use App\Services\CouponCodeService;
use Illuminate\Http\Request;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\SendReviewRequest;
use App\Http\Requests\ApplyRefundRequest;
use App\Jobs\CloseOrder;
use App\Events\OrderReviewedEvent;
use App\Exceptions\InvalidRequestException;
use Carbon\Carbon;

class OrdersController extends Controller
{
    public function index(OrderService $orderService)
    {
        $orders = $orderService->get();
        return view('orders.index', ['orders' => $orders]);
    }

    public function show(Order $order, Request $request)
    {
        $this->authorize('own', $order);
        $order = $order->load(['items.productSku', 'items.product']);
        return view('orders.show', ['order' => $order]);
    }

    public function store(OrderRequest $request, OrderService $orderService, CouponCodeService $couponCodeService)
    {
        $address = UserAddress::find($request->input('address_id'));

        $coupon = null;

        if ($code = $request->input('coupon_code')) {
            $coupon = $couponCodeService->couponCodeExists($code);
        }

        return $orderService->store($address, $request->input('remark'), $request->input('items'), $coupon);
    }

    public function received(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->ship_status !== Order::SHIP_STATUS_DELIVERED) {
            throw new InvalidRequestException('错误');
        }

        $order->update(['ship_status' => Order::SHIP_STATUS_RECEIVED]);
        return $order;

    }

    public function review(Order $order)
    {
        $this->authorize('own', $order);
        if (!$order->paid_at) {
            throw new InvalidRequestException('没有支付');
        }
        return view('orders.review', ['order' => $order->load(['items.product', 'items.productSku'])]);
    }

    public function sendReview(Order $order, SendReviewRequest $request, OrderService $orderService)
    {
        $this->authorize('own', $order);
        if (!$order->paid_at) {
            throw new InvalidRequestException('没有支付');
        }

        if ($order->reviewed) {
            throw new InvalidRequestException('已评价');
        }
        $reviews = $request->input('reviews');
        \DB::transaction(function () use ($reviews, $order, $orderService) {
            foreach ($reviews as $review) {
                $orderItem = $order->items()->find($review['id']);
                $orderItem->update([
                    'rating'    => $review['rating'],
                    'review'    => $review['review'],
                    'reviewed_at' => Carbon::now(),
                ]);
            }
            $order->reviewed = true;
            $order->save();

            // event(new OrderReviewedEvent($order));
            $orderService->updateProductRating($order);

        });

        return redirect()->back();
    }

    public function applyRefund(Order $order, ApplyRefundRequest $request)
    {
        $this->authorize('own', $order);
        if (!$order->paid_at) {
            throw new InvalidRequestException('订单未支付，不可退款');
        }

        if ($order->refund_status !== Order::REFUND_STATUS_PENDING) {
            throw new InvalidRequestException('申请过了');
        }

        $extra = $order->extra ?: [];
        $extra['refund_reason'] = $request->input('reason');

        $order->update([
            'refund_status' => Order::REFUND_STATUS_APPLIED,
            'extra'         => $extra,
        ]);

        return $order;
    }



    public function test(\App\Models\Category $category)
    {
        $categories = app(\App\Services\CategoryService::class)->getCategoryTree();
        var_dump($categories);

        return view('pages.white');
    }
}
