<?php

namespace Database\Seeders;

use App\Models\OrderKeyType;
use Illuminate\Database\Seeder;

class orderKeyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        OrderKeyType::create([
            "name_en" => "email",
            "name_ar" => "البريد الالكتروني"
        ]);
        OrderKeyType::create([
            "name_en" => "Serial number",
            "name_ar" => "الرقم السري"
        ]);
        OrderKeyType::create([
            "name_en" => "Phone number",
            "name_ar" => "رقم الهاتف"
        ]);
        OrderKeyType::create([
            "name_en" => "user_name",
            "name_ar" => "اسم المستخدم"
        ]);
    }
}