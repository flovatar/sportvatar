<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSportbitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sportbits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sportvatar_template_id')->nullable()->index();
            $table->integer('flow_id')->nullable()->unique();
            $table->string('flow_address')->nullable()->index();
            $table->json('flow_metadata')->nullable();
            $table->integer('mint')->nullable()->unsigned();
            $table->boolean('destroyed')->default(0);
            $table->boolean('packed')->default(0);
            $table->timestamps();



            $table->foreign('sportvatar_template_id')
                ->references('id')->on('sportvatar_templates')
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
        Schema::dropIfExists('sportbits');
    }
}
