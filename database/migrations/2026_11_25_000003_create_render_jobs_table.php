<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Queue for the browser (Chromium) render worker on the agent box: one row per item; the worker claims
 * the highest-priority pending row (lease), renders PNG/JPG/GIF/MP4, uploads to R2 and completes it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('render_jobs')) {
            return;
        }
        Schema::create('render_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('item_type', 20);
            $table->unsignedBigInteger('flow_id');
            // 100 no image at all, 90 new item, 80 changed item, 30 animated backfill, 10 rest
            $table->unsignedSmallInteger('priority')->default(10);
            $table->string('status', 10)->default('pending'); // pending | leased | done | failed
            $table->string('hash', 16)->nullable();           // item state the current lease renders
            $table->boolean('requeue')->default(false);       // item changed while leased: back to pending afterwards
            $table->timestamp('available_at')->nullable();
            $table->timestamp('lease_until')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('reason', 30)->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('render_ms')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
            $table->unique(['item_type', 'flow_id']);
            $table->index(['status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('render_jobs');
    }
};
