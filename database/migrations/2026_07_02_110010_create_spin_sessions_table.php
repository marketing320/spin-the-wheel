<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spin_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prize_id')->nullable()->constrained()->nullOnDelete();
            // pending, active, completed, expired, failed
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('spin_duration_ms')->default(8000);
            $table->decimal('final_angle', 10, 4)->default(0);
            $table->string('animation_seed')->nullable();
            $table->string('request_ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            // Global single-spin guard: set to 1 while the row is the one and
            // only active spin, NULL otherwise. MySQL treats multiple NULLs as
            // distinct, so this unique index guarantees at most one active spin
            // exists across the entire system at any moment.
            $table->unsignedTinyInteger('active_guard')->nullable();
            $table->timestamps();

            $table->unique('active_guard');
            $table->index(['campaign_id', 'status']);
            $table->index(['player_id', 'created_at']);
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spin_sessions');
    }
};
