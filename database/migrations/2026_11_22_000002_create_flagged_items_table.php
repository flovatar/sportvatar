<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NFTs linked to the 2026-11-16 incident (filled by `flovatar:flag-incident-items`).
 * They are hidden from marketplace listings/search and their pages show a notice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flagged_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_type');
            $table->unsignedBigInteger('flow_id');
            $table->string('reason')->nullable();
            $table->string('address')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamps();
            $table->unique(['item_type', 'flow_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flagged_items');
    }
};
