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
        Schema::create('professionals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->json('skills')->nullable(); // Array of skills
            $table->string('photo')->nullable();
            $table->decimal('reputation_score', 3, 2)->default(0.00); // 0.00 to 1.00
            $table->json('reputation_badges')->nullable(); // Array of badges
            $table->json('schedule')->nullable(); // Availability schedule
            $table->integer('total_reviews')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('is_active');
            $table->index('reputation_score');
        });

        // Add PGVector column using raw SQL
        DB::statement('ALTER TABLE professionals ADD COLUMN embedding_profile vector(768)');

        // Create IVFFLAT index for embedding_profile (PGVector)
        DB::statement('CREATE INDEX IF NOT EXISTS professionals_embedding_profile_idx ON professionals USING ivfflat (embedding_profile vector_cosine_ops) WITH (lists = 100)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professionals');
    }
};

