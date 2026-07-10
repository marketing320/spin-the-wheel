<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            // How many wheel slots this prize occupies (purely visual layout
            // — its actual winning odds stay win_percentage/weight either
            // way; see WheelAnimationService::segments()/indexOfPrize()).
            $table->unsignedTinyInteger('segment_count')->default(1)->after('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->dropColumn('segment_count');
        });
    }
};
