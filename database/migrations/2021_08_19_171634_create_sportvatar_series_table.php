<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSportvatarSeriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sportvatar_series', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->longText('svg_prefix')->nullable();
            $table->longText('svg_suffix')->nullable();
            $table->json('layers')->nullable();
            $table->json('colors')->nullable();
            $table->integer('max_mintable')->nullable();
            $table->integer('minted')->default(0);
            $table->integer('flow_id')->nullable()->unique();
            $table->string('flow_address')->nullable()->index();
            $table->json('flow_metadata')->nullable();
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
        Schema::dropIfExists('sportvatar_series');
    }
}
