<?php

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = factory(User::class)->times(100)->make()->makeVisible(['password'])->toArray();
        User::insert($users);

        $user1 = User::find(1);

        $user1->email = 'exy2000a@163.com';
        $user1->name = '孙哥';
        $user1->password = bcrypt(123123);
        $user1->save();
    }
}
