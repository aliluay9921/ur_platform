<?php

namespace Database\Seeders;

use App\Models\ChangeCurrncy;
use Illuminate\Database\Seeder;

class CurrenciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ChangeCurrncy::create([
            'currency' => 'points',
            'points' => 1
        ]);
        ChangeCurrncy::create([
            'currency' => 'dinar',
            'points' => 1
        ]);
        ChangeCurrncy::create([
            'currency' => 'dollar',
            'points' => 1
        ]);
    }
}
