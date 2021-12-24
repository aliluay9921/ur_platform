<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("status_id");
            $table->uuid("order_id");
            $table->text("message")->nullable();
            $table->double("after_operation")->default(0);
            $table->double("before_operation")->default(0);
            $table->integer("type"); // 0 deposit , 1 withdraw , 2 charge_balance 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_statuses');
    }
}