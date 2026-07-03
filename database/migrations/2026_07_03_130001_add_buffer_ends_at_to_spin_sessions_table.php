<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spin_sessions', function (Blueprint $table) {
            $table->timestamp('buffer_ends_at')->nullable()->after('ends_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('spin_sessions', function (Blueprint $table) {
            $table->dropColumn('buffer_ends_at');
        });
    }
};
