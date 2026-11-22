<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pack codes v2: one row per free pack entitlement. Codes are never stored in clear text.
class CreatePackVouchersTable extends Migration
{
    public function up()
    {
        Schema::create('pack_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 32)->index();             // flovatar_holder | gazzetta | giveaway | ...
            $table->string('batch', 64)->nullable()->index();   // issuing run, e.g. 2026-11-reissue-gazzetta
            $table->char('code_hash', 64)->nullable()->unique(); // HMAC-SHA256(code); null for address-bound vouchers
            $table->string('assigned_address', 18)->nullable()->index(); // only this wallet may redeem (flovatar_holder)
            $table->string('status', 16)->default('issued')->index(); // issued | reserved | claimed | revoked
            $table->unsignedBigInteger('pack_flow_id')->nullable()->index(); // pack reserved for the current authorisation
            $table->string('redeemed_by', 18)->nullable()->index();
            $table->unsignedBigInteger('authorization_expiry')->nullable();
            $table->unsignedInteger('authorization_epoch')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->string('legacy_pack_flow_id', 32)->nullable(); // v1 pack id the voucher replaces (audit trail)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pack_vouchers');
    }
}
