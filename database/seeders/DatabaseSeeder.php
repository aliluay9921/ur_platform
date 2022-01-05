<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(UserSeeder::class);
        $this->call(orderKeyTypeSeeder::class);
        $this->call(PaymentMethodsSeeder::class);
        $this->call(CompaniesSeeder::class);
        $this->call(cardsSeeder::class);
        $this->call(CountriesSeeder::class);
        $this->call(statusSeeder::class);
        $this->call(boxSeeder::class);
    }
}