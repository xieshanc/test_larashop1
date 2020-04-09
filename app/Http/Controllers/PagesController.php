<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductSku;
use App\Models\UserAddress;
use App\Models\CouponCode;
use App\Models\CrowdfundingProduct;
use App\Services\OrderService;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function root()
    {
        return view('pages.root');
    }

    public function test(CrowdfundingProduct $crowdfunding)
    {
        $crowdfunding = CrowdfundingProduct::find(2);
        $orderService = app(OrderService::class);

        Order::query()
            ->where('type', Order::TYPE_CROWDFUNDING)
            ->whereNotNull('paid_at')
            ->whereHas('items', function ($query) use ($crowdfunding) {
                $query->where('product_id', $crowdfunding->product_id);
            })
            ->get()
            ->each(function (Order $order) use ($orderService) {
                $orderService->refundOrder($order);
            });

        return view('pages.white');
    }

    public function getUrl(Request $request)
    {
        return $request->all();
    }

    public function postUrl(Request $request)
    {
        echo '<pre>';
        var_dump($request->all());
        exit;
    }
}
