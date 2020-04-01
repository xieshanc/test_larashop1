<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AddCartRequest;
use App\Models\CartItem;

class CartController extends Controller
{
    public function add(AddCartRequest $request)
    {
        $user = $request->user();
        $skuId = $request->input('sku_id');
        $amount = $request->input('amount');

        if ($cart = $user->cartItems()->where('product_sku_id', $skuId)->first()) {
            $cart->update(['amount' => $cart->amount + $amount]);
            // $cart->increment();
        } else {
            // $cart = new CartItem(['amount' => $amount]);
            $cart = new CartItem;
            $cart->amount = $amount;
            $cart->user()->associate($user);
            $cart->productSku()->associate($skuId);
            $cart->save();
        }
        return [];
    }
}
