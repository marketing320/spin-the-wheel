<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('rarity')->default('common'); // common, uncommon, rare, epic, legendary
            $table->string('color', 20)->nullable();
            $table->decimal('win_percentage', 8, 4)->nullable();
            $table->unsignedInteger('weight')->nullable();
            $table->unsignedInteger('inventory_quantity')->nullable();
            $table->boolean('inventory_enabled')->default(false);
            $table->string('confetti_level')->default('light'); // light, medium, strong, heavy, max
            $table->text('redemption_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['campaign_id', 'is_active']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prizes');
    }
};
