<?php

namespace Database\Seeders;

use App\Models\Card;
use App\Models\Company;
use App\Models\joinRelations;
use App\Models\SerialKeyCard;
use Illuminate\Database\Seeder;

class cardsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $company = Company::all();
        $card_1 =  Card::create([
            "card_sale" => 50,
            "value" => 55,
            "points" => 55
        ]);
        joinRelations::create([
            "company_id" => $company[3]->id,
            "card_id" => $card_1->id
        ]);
        SerialKeyCard::create([
            "card_id" => $card_1->id,
            "serial" => "1528743BC"
        ]);
    }
}