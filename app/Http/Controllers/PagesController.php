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
        $arr = Product::find(1)->toESArray();

        // $res = app('es')->index([
        //     'index' => 'productdffe',
        //     'id' => $arr['id'],
        //     'type'  => '_doc',
        //     'body'  => $arr,
        // ]);
        // echo '<pre>';
        // var_dump($res);
        // exit;


        $res = app('es')->get([
            'index' => 'productdffe',
            'id'    => 1,
        ]);
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
