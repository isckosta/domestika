<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('category', ['cleaning', 'cooking', 'laundry', 'babysitting', 'gardening']);
            $table->enum('workload_size', ['small', 'medium', 'large']);
            $table->enum('frequency', ['once', 'weekly', 'biweekly', 'monthly']);
            $table->enum('urgency', ['low', 'medium', 'high']);
            $table->text('description')->nullable(); // Additional context
            $table->enum('status', ['pending', 'matched', 'completed', 'cancelled'])->default('pending');
            $table->jsonb('matched_professionals')->nullable(); // Array of matched professionals with scores
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('category');
            $table->index('created_at');
        });

        // Add PGVector column using raw SQL
        DB::statement('ALTER TABLE service_requests ADD COLUMN embedding_request vector(768)');

        // Create IVFFLAT index for embedding_request using raw SQL
        DB::statement('CREATE INDEX IF NOT EXISTS service_requests_embedding_request_idx ON service_requests USING ivfflat (embedding_request vector_cosine_ops) WITH (lists = 100)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};

