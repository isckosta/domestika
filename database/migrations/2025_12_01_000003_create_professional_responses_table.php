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
        Schema::create('professional_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('service_request_id')->constrained('service_requests')->onDelete('cascade');
            $table->foreignId('professional_id')->constrained('professionals')->onDelete('cascade');
            $table->enum('status', ['interested', 'declined', 'accepted', 'completed'])->default('interested');
            $table->text('message')->nullable();
            $table->timestamp('responded_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->index('service_request_id');
            $table->index('professional_id');
            $table->index('status');
            // Unique constraint: one response per professional per request
            $table->unique(['service_request_id', 'professional_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professional_responses');
    }
};

