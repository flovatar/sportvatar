<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('block_id')->index();
            $table->string('transaction_id')->index();
            $table->string('type')->index();
            $table->integer('index')->index();
            $table->json('flow_metadata');
            $table->boolean('synced')->default(0);
            $table->boolean('graffle')->default(0);
            $table->dateTime('event_date', 6)->nullable();
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
        Schema::dropIfExists('events');
    }
}
