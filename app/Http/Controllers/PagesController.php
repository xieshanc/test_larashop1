<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductSku;
use App\Models\UserAddress;
use App\Models\CouponCode;
use App\Models\CrowdfundingProduct;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    public function root()
    {
        return view('pages.root');
    }

    public function test(CrowdfundingProduct $crowdfunding)
    {
        $crowdfunding = CrowdfundingProduct::find(1);
        return view('pages.white');
    }
}
