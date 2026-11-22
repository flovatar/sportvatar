<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns for the built-in Flow event indexer that replaces the Graffle webhook.
 * Graffle rows keep NULL transaction_index/event_index (MySQL allows repeated NULLs in
 * the unique key), indexer rows are deduplicated on (transaction_id, event_index).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'transaction_index')) {
                $table->unsignedInteger('transaction_index')->nullable()->after('index');
            }
            if (! Schema::hasColumn('events', 'event_index')) {
                $table->unsignedInteger('event_index')->nullable()->after('transaction_index');
            }
            if (! Schema::hasColumn('events', 'source')) {
                $table->string('source', 16)->nullable()->after('graffle')->comment('graffle | rest | stream | backfill');
            }
            if (! Schema::hasColumn('events', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(0)->after('synced');
            }
        });

        Schema::table('events', function (Blueprint $table) {
            $table->unique(['transaction_id', 'event_index'], 'events_tx_event_unique');
            $table->index(['synced', 'block_id'], 'events_synced_block_index');
            $table->index(['type', 'block_id'], 'events_type_block_index');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique('events_tx_event_unique');
            $table->dropIndex('events_synced_block_index');
            $table->dropIndex('events_type_block_index');
            $table->dropColumn(['transaction_index', 'event_index', 'source', 'attempts']);
        });
    }
};
