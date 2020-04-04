<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CouponCode;
use App\Services\CouponCodeService;

class CouponCodesController extends Controller
{
    public function show($code, Request $request, CouponCodeService $couponCodeService)
    {
        $couponCode = $couponCodeService->couponCodeExists($code);
        $couponCode->checkAvailable($request->user());

        return $couponCode;
    }
}
