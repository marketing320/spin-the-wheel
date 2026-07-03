<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geofence_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('distance_meters', 12, 2)->nullable();
            $table->boolean('passed')->default(false);
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'created_at']);
            $table->index('player_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geofence_logs');
    }
};
