<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Listing / sale currency (Flow Wallet support).
 *
 * NFTStorefrontV2 listings can be paid in DapperUtilityCoin (Dapper Wallet, shown as USD) or in FlowToken
 * (Flow Wallet). `currency` stores which one: 'DUC' or 'FLOW' (from the event's salePaymentVaultType).
 * Existing rows: every storefront listing/sale so far was a DUC listing (is_dapper = 1); legacy rows with
 * is_dapper = 0 came from the old FLOW marketplace.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['sale_offers', 'sales'] as $table) {
            if (!Schema::hasColumn($table, 'currency')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->string('currency', 16)->nullable()->after('price')->index();
                });
            }
            DB::table($table)->whereNull('currency')->where('is_dapper', 1)->update(['currency' => 'DUC']);
            DB::table($table)->whereNull('currency')->where('is_dapper', 0)->update(['currency' => 'FLOW']);
        }
    }

    public function down(): void
    {
        foreach (['sale_offers', 'sales'] as $table) {
            if (Schema::hasColumn($table, 'currency')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropIndex(['currency']);
                    $t->dropColumn('currency');
                });
            }
        }
    }
};
