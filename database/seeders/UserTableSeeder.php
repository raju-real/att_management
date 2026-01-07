<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Mr. Admin',
            'email' => 'admin@mail.com',
            'mobile' => '12345679810',
            'password_plain' => '123456',
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
            'image' => null,
            'password' => Hash::make('123456'),
            'status' => 'active'
        ]);
    }
}
