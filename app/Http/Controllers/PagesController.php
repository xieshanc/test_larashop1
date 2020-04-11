<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
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

    public function test()
    {
        $product = Product::find(60);

        $res = $product->properties->groupBy('name')->map(function ($property) {
            // echo '<pre>';
            // var_dump($property->pluck('value')->all());
            // exit;
            return $property->pluck('value')->toArray();
        });

        echo '<pre>';
        var_dump($res);
        exit;

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
