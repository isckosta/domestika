<?php

namespace App\Jobs;

use App\Models\ServiceRequest;
use App\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateRequestEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ServiceRequest $serviceRequest
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(EmbeddingService $embeddingService): void
    {
        try {
            // Generate embedding text from request data
            $embeddingText = $embeddingService->generateRequestText([
                'category' => $this->serviceRequest->category,
                'workload_size' => $this->serviceRequest->workload_size,
                'frequency' => $this->serviceRequest->frequency,
                'urgency' => $this->serviceRequest->urgency,
                'description' => $this->serviceRequest->description,
            ]);

            // Generate embedding vector
            $embedding = $embeddingService->generateEmbedding($embeddingText);

            if ($embedding) {
                // Store embedding in database using raw SQL
                // Convert array to PostgreSQL vector format
                $vectorString = '[' . implode(',', $embedding) . ']';
                DB::statement(
                    'UPDATE service_requests SET embedding_request = ?::vector WHERE id = ?',
                    [$vectorString, $this->serviceRequest->id]
                );

                // Dispatch matching job after embedding is created
                dispatch(new \App\Jobs\MatchProfessionalsJob($this->serviceRequest->fresh()));

                Log::info('Request embedding generated', [
                    'service_request_id' => $this->serviceRequest->id,
                ]);
            } else {
                Log::warning('Failed to generate embedding for request', [
                    'service_request_id' => $this->serviceRequest->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error generating request embedding', [
                'service_request_id' => $this->serviceRequest->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

