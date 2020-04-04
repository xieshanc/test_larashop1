<?php

namespace App\Services;

use App\Models\CouponCode;
use App\Exceptions\CouponCodeUnavailableException;

class CouponCodeService
{
    public function couponCodeExists($code)
    {
        // 找不到
        if (!$couponCode = CouponCode::where('code', $code)->first()) {
            throw new CouponCodeUnavailableException('优惠券不存在');
        }
        return $couponCode;
    }
}
