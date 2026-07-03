<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('play_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            // once_per_campaign, once_per_day, every_x_hours,
            // max_per_campaign, max_per_day
            $table->string('rule_type')->default('once_per_campaign');
            $table->unsignedInteger('cooldown_hours')->nullable();
            $table->unsignedInteger('max_spins_per_campaign')->nullable();
            $table->unsignedInteger('max_spins_per_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_rules');
    }
};
