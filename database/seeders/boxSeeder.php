<?php

namespace Database\Seeders;

use App\Models\Box;
use Illuminate\Database\Seeder;

class boxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Box::create([
            "total_value" => 0,
            "company_ratio" => 0,
            "programmer_ratio" => 0,
            "managment_ratio" => 0
        ]);
    }
}