<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleOffersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->index();
            $table->string('item_type');
            $table->integer('flow_id')->nullable()->index();
            $table->string('flow_address')->nullable()->index();
            $table->json('flow_metadata')->nullable();
            $table->unsignedFloat('price')->unsigned()->default(1)->index();
            $table->boolean('is_completed')->default(0);
            $table->boolean('is_dapper')->default(0);
            $table->unsignedBigInteger('listing_id')->nullable();
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
        Schema::dropIfExists('sale_offers');
    }
}
