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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('email')->index();
            $table->string('ip_address', 45)->index();
            $table->text('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamps();

            $table->index(['email', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
            $table->index(['user_id', 'attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};

