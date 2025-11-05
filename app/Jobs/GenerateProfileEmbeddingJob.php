<?php

namespace App\Jobs;

use App\Models\Professional;
use App\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateProfileEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Professional $professional
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(EmbeddingService $embeddingService): void
    {
        try {
            // Generate embedding text from professional profile
            $embeddingText = $embeddingService->generateProfileText([
                'bio' => $this->professional->bio,
                'skills' => $this->professional->skills,
            ]);

            // Generate embedding vector
            $embedding = $embeddingService->generateEmbedding($embeddingText);

            if ($embedding) {
                // Store embedding in database using raw SQL
                // Convert array to PostgreSQL vector format
                $vectorString = '[' . implode(',', $embedding) . ']';
                DB::statement(
                    'UPDATE professionals SET embedding_profile = ?::vector WHERE id = ?',
                    [$vectorString, $this->professional->id]
                );

                Log::info('Profile embedding generated', [
                    'professional_id' => $this->professional->id,
                ]);
            } else {
                Log::warning('Failed to generate embedding for professional', [
                    'professional_id' => $this->professional->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error generating profile embedding', [
                'professional_id' => $this->professional->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

