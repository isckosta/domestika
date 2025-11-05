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
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('amount');
            $table->enum('type', ['credit', 'debit', 'transfer_in', 'transfer_out']);
            $table->string('reason');
            $table->string('reference_id')->nullable();
            $table->foreignId('related_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('transaction_hash', 64);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();



            // Unique constraint for idempotency
            $table->unique(['reference_id', 'user_id'], 'unique_reference_per_user');

            // Indexes for performance
            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
            $table->index('transaction_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
