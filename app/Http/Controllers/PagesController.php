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
        $res = Product::find(71)->toESArray();
        echo '<pre>';
        var_dump($res);
        exit;
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

