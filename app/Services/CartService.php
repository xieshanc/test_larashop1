<?php

namespace App\Services;

use Auth;
use App\Models\CartItem;
use Illuminate\Http\Request;

class CartService
{

    public function get()
    {
        return Auth::user()->cartItems()->with(['productSku.product'])->get();
    }

    public function add($skuId, $amount)
    {
        $user = Auth::user();
        if ($item = $user->cartItems()->where('product_sku_id', $skuId)->first()) {
            $item->amount += $amount;
            $item->save();
        } else {
            $item = new CartItem;
            $item->amount = $amount;
            $item->user()->associate($user);
            $item->productSku()->associate($skuId);
            $item->save();
        }
        return $item;
    }

    public function remove($skuIds)
    {
        if (!is_array($skuIds)) {
            $skuIds = [$skuIds];
        }
        Auth::user()->cartItems()->whereIn('product_sku_id', $skuIds)->delete();
    }
}
