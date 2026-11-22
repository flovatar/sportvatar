<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSportvatarTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sportvatar_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('layer')->unsigned()->nullable()->index();
            $table->string('rarity')->nullable()->index();
            $table->string('sport')->nullable()->index();
            $table->longText('svg')->nullable();
            $table->integer('max_mintable')->nullable();
            $table->integer('minted')->default(0);
            $table->foreignId('sportvatar_series_id')->nullable()->index();
            $table->integer('flow_id')->nullable()->unique();
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
        Schema::dropIfExists('sportvatar_templates');
    }
}
