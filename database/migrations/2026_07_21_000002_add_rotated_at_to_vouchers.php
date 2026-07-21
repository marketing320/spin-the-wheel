<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            // Set when an expired, unredeemed voucher is rotated back onto the
            // wheel (its prize stock + odds restored). Prevents a voucher from
            // being rotated — and thus restocked — more than once.
            $table->timestamp('rotated_at')->nullable()->after('redeemed_by');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('rotated_at');
        });
    }
};
