<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->json('responses');
            $table->timestamps();

            $table->unique(['player_id', 'campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_form_responses');
    }
};
