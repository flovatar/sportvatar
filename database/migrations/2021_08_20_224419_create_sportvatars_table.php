<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSportvatarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sportvatars', function (Blueprint $table) {
            $table->id();
            $table->integer('mint')->nullable()->unsigned();
            $table->string('name')->nullable()->index();
            $table->foreignId('sportvatar_series_id')->nullable()->index();
            $table->longText('svg')->nullable();
            $table->integer('flow_id')->nullable()->unique();
            $table->string('rarity')->nullable()->index();
            $table->integer('ability')->default(0)->unsigned()->index();
            $table->string('flow_address')->nullable()->index();
            $table->json('flow_metadata')->nullable();
            $table->timestamps();

            $table->foreign('sportvatar_series_id')
                ->references('id')->on('sportvatar_series')
                ->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sportvatars');
    }
}
