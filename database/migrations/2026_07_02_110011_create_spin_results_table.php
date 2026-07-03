<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spin_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spin_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prize_id')->constrained()->cascadeOnDelete();
            $table->json('result_payload')->nullable();
            $table->timestamps();

            $table->unique('spin_session_id');
            $table->index(['campaign_id', 'created_at']);
            $table->index(['prize_id', 'created_at']);
            $table->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spin_results');
    }
};
