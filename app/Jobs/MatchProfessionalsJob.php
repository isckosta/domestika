<?php

namespace App\Jobs;

use App\Models\ServiceRequest;
use App\Services\MatchingEngineService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchProfessionalsJob implements ShouldQueue
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
    public function handle(MatchingEngineService $matchingEngine, NotificationService $notificationService): void
    {
        try {
            // Wait for embedding to be generated
            if (!$this->serviceRequest->embedding_request) {
                // Retry if embedding not ready
                $this->release(30); // Retry in 30 seconds
                return;
            }

            // Find matching professionals
            $matches = $matchingEngine->findMatchingProfessionals($this->serviceRequest, 5);

            if (empty($matches)) {
                Log::info('No matching professionals found', [
                    'service_request_id' => $this->serviceRequest->id,
                ]);
                return;
            }

            // Format matched professionals data
            $matchedProfessionals = array_map(function ($match) {
                return [
                    'professional_id' => $match['professional']->id,
                    'score' => $match['score'],
                    'scores' => $match['scores'],
                ];
            }, $matches);

            // Update service request with matches
            DB::transaction(function () use ($matchedProfessionals) {
                $this->serviceRequest->update([
                    'matched_professionals' => $matchedProfessionals,
                    'status' => 'matched',
                ]);

                // Log activity
                activity()
                    ->performedOn($this->serviceRequest)
                    ->causedBy($this->serviceRequest->user)
                    ->withProperties([
                        'matches_count' => count($matchedProfessionals),
                    ])
                    ->log('service_request.matched');
            });

            // Notify matched professionals
            $notificationService->notifyMatchedProfessionals($this->serviceRequest, $matches);

            Log::info('Professionals matched successfully', [
                'service_request_id' => $this->serviceRequest->id,
                'matches_count' => count($matches),
            ]);
        } catch (\Exception $e) {
            Log::error('Error matching professionals', [
                'service_request_id' => $this->serviceRequest->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

