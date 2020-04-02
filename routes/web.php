<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::redirect('/', '/products')->name('root');

Auth::routes(['verify' => true]);

Route::get('products', 'ProductsController@index')->name('products.index');
Route::get('products/{product}', 'ProductsController@show')->name('products.show')->where(['product' => '\d+']);
// 资源路由做不了上面那样的限制?
// Route::resource('products', 'ProductsController');

Route::group(['middleware' => ['auth', 'verified']], function () {
    // Route::get('user_addresses', 'UserAddressesController@index')->name('user_addresses.index');
    // Route::get('user_addresses/create', 'UserAddressesController@create')->name('user_addresses.create');
    // Route::post('user_addresses', 'UserAddressesController@store')->name('user_addresses.store');
    // Route::get('user_addresses/{user_address}/edit', 'UserAddressesController@edit')->name('user_addresses.edit');
    // Route::patch('user_addresses/{user_address}', 'UserAddressesController@update')->name('user_addresses.update');
    // Route::delete('user_addresses/{user_address}', 'UserAddressesController@destroy')->name('user_addresses.destroy');
    // Route::resource('user_addresses', 'UserAddressesController', ['only' => ['index', 'create', 'store', 'edit', 'update', 'destroy']]);
    Route::resource('user_addresses', 'UserAddressesController', ['except' => ['show']]);

    Route::post('products/{product}/favorite', 'ProductsController@favor')->name('products.favor');
    Route::delete('pproducts/{product}/favorite', 'ProductsController@disfavor')->name('products.disfavor');
    Route::get('products/favorites', 'ProductsController@favorites')->name('products.favorites');

    Route::get('cart', 'CartController@index')->name('cart.index');
    Route::post('cart', 'CartController@add')->name('cart.add');
    Route::delete('cart/{sku}', 'CartController@remove')->name('cart.remove');

    Route::get('orders', 'OrdersController@index')->name('orders.index');
    Route::get('orders/{order}', 'OrdersController@show')->name('orders.show');
    Route::post('orders', 'OrdersController@store')->name('orders.store');

    Route::get('payment/{order}/alipay', 'PaymentController@payByAlipay')->name('payment.alipay');
    Route::get('payment/alipay/return', 'PaymentController@alipayReturn')->name('payment.alipay.return');
    Route::get('payment/{order}/wechat', 'PaymentController@payByWechat')->name('payment.wechat');
});
Route::post('payment/alipay/notify', 'PaymentController@alipayNotify')->name('payment.alipay.notify');
Route::post('payment/wechat/notify', 'PaymentController@wechatNotify')->name('payment.wechat.notify');

Route::get('test', 'OrdersController@test');

// Route::get('alipay', function () {
//     return app('alipay')->web([
//         'out_trade_no' => time(),
//         'total_amount' => '1',
//         'subject' => '刚刚好，亲本什么都算到了',
//     ]);
// });
