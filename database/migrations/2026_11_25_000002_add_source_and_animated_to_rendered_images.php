<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rendered_images', function (Blueprint $table) {
            if (! Schema::hasColumn('rendered_images', 'source')) {
                // server = resvg on the web server (instant fallback); browser = Chromium render (preferred, e.g. animated items)
                $table->string('source', 10)->default('server')->after('path');
            }
            if (! Schema::hasColumn('rendered_images', 'animated')) {
                $table->boolean('animated')->nullable()->after('source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rendered_images', function (Blueprint $table) {
            $table->dropColumn(['source', 'animated']);
        });
    }
};
