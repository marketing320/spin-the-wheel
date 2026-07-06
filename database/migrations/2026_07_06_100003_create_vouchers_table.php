<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spin_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prize_id')->constrained()->cascadeOnDelete();

            // Canonical redemption value — encoded into both the QR and the
            // barcode image, and what staff type/scan to look it up.
            $table->string('code', 32)->unique();

            // pending -> redeemed (terminal) or expired (terminal, lazily
            // evaluated from expires_at rather than swept by a job).
            $table->string('status')->default('pending');

            $table->timestamp('expires_at');
            $table->timestamp('redeemed_at')->nullable();
            $table->foreignId('redeemed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique('spin_result_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
