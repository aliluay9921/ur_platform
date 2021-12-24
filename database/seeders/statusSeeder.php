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
            "type" => 0,
            "status_ar" => " بانتظار المراجعة",
            "status_en" => "waiting review",
        ]);
        Status::create([
            "type" => 1,
            "status_ar" => " تحت المراجعة",
            "status_en" => "under review",
        ]);
        Status::create([
            "type" => 2,
            "status_ar" => " اكتمل الطلب",
            "status_en" => "done ",
        ]);
        Status::create([
            "type" => 3,
            "status_ar" => "اكتمل الطلب جزئيا ",
            "status_en" => "partial done",
        ]);
        Status::create([
            "type" => 4,
            "status_ar" => "رفض ",
            "status_en" => "reject ",
        ]);
    }
}