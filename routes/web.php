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
});



// Route::get('products', 'ProductsController@index')->name('products.index');
Route::resource('products', 'ProductsController');
