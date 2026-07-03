<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('display_name')->nullable();
            $table->boolean('otp_verified')->default(false);
            $table->timestamp('form_completed_at')->nullable();
            $table->timestamp('last_spin_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->unique('email');
            $table->index('email_verified_at');
            $table->index('last_spin_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
