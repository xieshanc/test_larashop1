<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductSku;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use App\Http\Requests\OrderRequest;
use Carbon\Carbon;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Services\OrderService;

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

    public function store(OrderRequest $request, OrderService $orderService)
    {
        $address = UserAddress::find($request->input('address_id'));

        return $orderService->store($address, $request->input('remark'), $request->input('items'));
    }

    public function received(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->ship_status !== Order::SHIP_STATUS_DELIVERED) {
            throw new InvalidRequestException('错误');
        }

        $order->update(['ship_status' => Order::SHIP_STATUS_RECEIVED]);
        return $order;
        // return redirect()->back();
    }

    public function test()
    {
        $order = Order::find(33);
        echo '<pre>';
        var_dump($order->items->toArray());
        exit;
    }
}
