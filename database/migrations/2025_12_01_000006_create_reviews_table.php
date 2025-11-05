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
        Schema::create('reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('service_request_id')->constrained('service_requests')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade'); // Contractor who reviews
            $table->foreignId('professional_id')->constrained('professionals')->onDelete('cascade');
            $table->integer('rating')->unsigned(); // 1-5
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable(); // Additional review data
            $table->timestamps();

            // Indexes
            $table->index('service_request_id');
            $table->index('professional_id');
            $table->index('reviewer_id');
            $table->index('rating');
            // Unique constraint: one review per request per reviewer
            $table->unique(['service_request_id', 'reviewer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

