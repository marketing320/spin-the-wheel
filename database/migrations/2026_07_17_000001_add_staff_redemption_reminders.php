<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->text('staff_redemption_reminder')->nullable()->after('redemption_message');
        });

        Schema::table('vouchers', function (Blueprint $table) {
            // Snapshot the operational terms that applied when the voucher
            // was issued so later prize edits cannot rewrite issued terms.
            $table->text('staff_redemption_reminder')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('staff_redemption_reminder');
        });

        Schema::table('prizes', function (Blueprint $table) {
            $table->dropColumn('staff_redemption_reminder');
        });
    }
};
