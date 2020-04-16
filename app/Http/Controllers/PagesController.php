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
        $params = [
            'index' => 'products',
            'type'  => '_doc',
            'body'  => [
                'query' => [
                    'bool' => [
                        'should'   => [
                            [
                                'nested' => [
                                    'path'  => 'properties',
                                    'query' => [
                                        ['term' => ['properties.search_value' => '品牌名称:金士顿']],
                                    ],
                                ],
                            ],
                            [
                                'nested' => [
                                    'path'  => 'properties',
                                    'query' => [
                                        ['term' => ['properties.search_value' => '内存容量:8GB']],
                                    ],
                                ],
                            ],
                            [
                                'nested' => [
                                    'path'  => 'properties',
                                    'query' => [
                                        ['term' => ['properties.search_value' => '传输类型:DDR4']],
                                    ],
                                ],
                            ],
                        ],
                        'minimum_should_match' => 2,
                    ],
                ],
            ],
        ];
        echo '<pre>';
        var_dump(app('es')->search($params));
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

