<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Image;
use Illuminate\Database\Seeder;

class CompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $company_1 = Company::Create([
            "name_ar" => "بايبال",
            "name_en" => "paypal",
            "currncy_type" => "dollar",
        ]);
        Image::create([
            "target_id" => $company_1->id,
            "image" => "images/companies_image/paypal.png"
        ]);
        $company_2 = Company::Create([
            "name_ar" => "ماستر كارد الرافدين",
            "name_en" => "master card alrafedin",
            "currncy_type" => "dollar",
        ]);
        Image::create([
            "target_id" => $company_2->id,
            "image" => "images/companies_image/master.jfif"
        ]);
        $company_3 =  Company::Create([
            "name_ar" => "ببجي",
            "name_en" => "pubg",
            "currncy_type" => "BP",
        ]);
        Image::create([
            "target_id" => $company_3->id,
            "image" => "images/companies_image/pubg.jfif"
        ]);
        $company_4 = Company::Create([
            "name_ar" => "ايتونز",
            "name_en" => "itunes ",
            "currncy_type" => "dollar",
        ]);
        Image::create([
            "target_id" => $company_4->id,
            "image" => "images/companies_image/itunes.jfif"
        ]);
    }
}