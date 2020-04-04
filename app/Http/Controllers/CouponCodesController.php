<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CouponCode;
use Carbon\Carbon;

class CouponCodesController extends Controller
{
    public function show($code)
    {
        // 找不到
        if (!$record = CouponCode::where('code', $code)->first()) {
            abort(404);
        }

        // 没启用
        if (!$record->enabled) {
            abort(404);
        }

        if ($record->total - $record->used <= 0) {
            return response()->json(['msg' => '优惠券已兑完'], 403);
        }

        if ($record->not_before && $record->not_before->gt(Carbon::now())) {
            return response()->json(['msg' => '优惠券还不能使用'], 403);
        }

        if ($record->not_after && $record->not_after->lt(Carbon::now())) {
            return response()->json(['msg' => '优惠券已过期'], 403);
        }

        return $record;
    }
}
