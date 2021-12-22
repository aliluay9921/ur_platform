<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class statusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Status::create([
            "status_ar" => "انتضار",
            "status_en" => "pending",
        ]);
        Status::create([
            "status_ar" => "قبول",
            "status_en" => "accept",
        ]);
        Status::create([
            "status_ar" => "رفض",
            "status_en" => "reject",
        ]);
        Status::create([
            "status_ar" => "مقبول جزئي",
            "status_en" => "partially acceptable",
        ]);
    }
}