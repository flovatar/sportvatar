<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rendered_images')) {
            return;
        }
        Schema::create('rendered_images', function (Blueprint $table) {
            $table->id();
            $table->string('item_type', 20);
            $table->unsignedBigInteger('flow_id');
            $table->string('hash', 16);
            $table->string('path', 191);
            $table->json('variants')->nullable();
            $table->unsignedInteger('bytes')->default(0);
            $table->unsignedInteger('render_ms')->default(0);
            $table->unsignedSmallInteger('warnings')->default(0);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('rendered_at')->nullable();
            $table->timestamps();
            $table->unique(['item_type', 'flow_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendered_images');
    }
};
