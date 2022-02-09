<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            "first_name" => "ali",
            "last_name" => "luay",
            "user_name" => "ur_admin",
            "email" => "ur_admin@admin.com",
            "password" => bcrypt("ur_platform!@#"),
            "phone_number" => "07713982401",
            "user_type" => 2
        ]);
        User::create([
            "first_name" => "ibrahim",
            "last_name" => "ayad",
            "user_name" => "ibrahim_ayad",
            "email" => "ibrahim_ayad@gmail.com",
            "password" => bcrypt("11111111"),
            "phone_number" => "07712345678",
            "user_type" => 0
        ]);
    }
}