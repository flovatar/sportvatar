<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('memes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meme_template_id')->nullable()->index();
            $table->string('flow_address')->nullable()->index();
            $table->string('image');
            $table->timestamps();


            $table->foreign('meme_template_id')
                ->references('id')->on('meme_templates')
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
        Schema::dropIfExists('memes');
    }
}
