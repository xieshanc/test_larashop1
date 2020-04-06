<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->call(UsersSeeder::class);
        $this->call(UserAddressSeeder::class);
        $this->call(CategoriesSeeder::class);
        $this->call(ProductsSeeder::class);

        // var_dump(ProductsSeeder::class);
        // var_dump(CouponCodesSeeder::class);
        // var_dump(class_exists(ProductsSeeder::class));
        // var_dump(class_exists(CouponCodesSeeder::class));
        // exit;
        $this->call(CouponCodesSeeder::class);
        $this->call(OrdersSeeder::class);

        Model::reguard();
    }
}
