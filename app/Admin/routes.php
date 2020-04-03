<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('admin.home');
    $router->get('users', 'UsersController@index');

    $router->get('products', 'ProductsController@index');
    $router->get('products/create', 'ProductsController@create');
    $router->post('products', 'ProductsController@store');
    $router->get('products/{id}/edit', 'ProductsController@edit');
    $router->put('products/{id}', 'ProductsController@update');
    $router->delete('products/{id}', 'ProductsController@destroy');

    $router->get('orders', 'OrdersController@index')->name('admin.orders.index');
    $router->get('orders/{order}', 'OrdersController@show')->name('admin.orders.show');
    $router->post('orders/{order}/ship', 'OrdersController@ship')->name('admin.orders.ship');
    $router->post('orders/{order}/refund', 'OrdersController@handleRefund')->name('admin.orders.handle_refund');

    $router->get('coupon_codes', 'CouponCodesController@index')->name('coupon_codes.index');
    $router->get('coupon_codes/create', 'CouponCodesController@create')->name('coupon_codes.create');
    $router->post('coupon_codes', 'CouponCodesController@store')->name('coupon_codes.store');
    $router->get('coupon_codes/{coupon_code}/edit', 'CouponCodesController@edit');
    $router->put('coupon_codes/{coupon_code}', 'CouponCodesController@update');
    $router->delete('coupon_codes/{啥都行}', 'CouponCodesController@destroy');
});
