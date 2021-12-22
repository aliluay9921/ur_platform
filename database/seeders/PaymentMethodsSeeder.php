<?php

namespace Database\Seeders;

use App\Models\OrderKeyType;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $order_keys = OrderKeyType::all();
        PaymentMethod::create([
            "key" => "luaya577@gmail.com",
            "order_key_type_id" => $order_keys[0]->id,
            "tax" => 15,
            "note" => "سيتم خصم 15%  من قبل الشركة "
        ]);
        PaymentMethod::create([
            "key" => "07713982401",
            "order_key_type_id" => $order_keys[2]->id,
            "tax" => 10,
            "note" => "سيتم خصم 2%  من قبل الشركة "
        ]);
    }
}