<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->index();
            $table->string('item_type');
            $table->unsignedFloat('price')->unsigned()->default(1);
            $table->string('transaction_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('flow_buyer_address')->index()->nullable();
            $table->string('flow_seller_address')->index()->nullable();
            $table->boolean('is_dapper')->default(0);
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
        Schema::dropIfExists('sales');
    }
}
