<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            // Snapshot of the admin-configured odds, kept independent of the
            // live win_percentage/weight (which are zeroed when a prize sells
            // out). Lets an unredeemed voucher be "rotated" back onto the wheel
            // with the prize's original odds restored. Maintained by Prize's
            // saving hook whenever the prize is in stock.
            $table->decimal('base_win_percentage', 8, 4)->nullable()->after('win_percentage');
            $table->unsignedInteger('base_weight')->nullable()->after('weight');
        });

        // Seed the base columns from whatever is currently configured so
        // existing prizes can be rotated correctly straight away.
        DB::table('prizes')->update([
            'base_win_percentage' => DB::raw('win_percentage'),
            'base_weight' => DB::raw('weight'),
        ]);
    }

    public function down(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->dropColumn(['base_win_percentage', 'base_weight']);
        });
    }
};
