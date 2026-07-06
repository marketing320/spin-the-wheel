<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            // physical = today's behaviour (just a redemption_message shown).
            // voucher  = a unique, expiring, QR/barcode redemption code is
            // generated automatically for every win.
            $table->string('type')->default('physical')->after('rarity');

            // Per-prize override of the global voucher expiry window (hours).
            // Null = fall back to the admin-configurable global default.
            $table->unsignedInteger('voucher_expiry_hours')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->dropColumn(['type', 'voucher_expiry_hours']);
        });
    }
};
