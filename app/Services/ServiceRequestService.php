<?php

namespace App\Services;

use App\Jobs\GenerateRequestEmbeddingJob;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceRequestService
{
    public function __construct(
        private EmbeddingService $embeddingService
    ) {
    }

    /**
     * Create a new service request with embedding generation.
     */
    public function createServiceRequest(User $user, array $data): ServiceRequest
    {
        return DB::transaction(function () use ($user, $data) {
            // Create the service request
            $serviceRequest = ServiceRequest::create([
                'user_id' => $user->id,
                'category' => $data['category'],
                'workload_size' => $data['workload_size'],
                'frequency' => $data['frequency'],
                'urgency' => $data['urgency'],
                'description' => $data['description'] ?? null,
                'status' => 'pending',
            ]);

            // Generate embedding asynchronously
            dispatch(new GenerateRequestEmbeddingJob($serviceRequest));

            // Log activity
            activity()
                ->performedOn($serviceRequest)
                ->causedBy($user)
                ->withProperties([
                    'category' => $data['category'],
                    'urgency' => $data['urgency'],
                ])
                ->log('service_request.created');

            Log::info('Service request created', [
                'service_request_id' => $serviceRequest->id,
                'user_id' => $user->id,
                'category' => $data['category'],
            ]);

            return $serviceRequest;
        });
    }

    /**
     * Update service request status.
     */
    public function updateStatus(ServiceRequest $serviceRequest, string $status, User $user): void
    {
        DB::transaction(function () use ($serviceRequest, $status, $user) {
            $oldStatus = $serviceRequest->status;
            $serviceRequest->update(['status' => $status]);

            // Log activity
            activity()
                ->performedOn($serviceRequest)
                ->causedBy($user)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                ])
                ->log("service_request.{$status}");

            Log::info('Service request status updated', [
                'service_request_id' => $serviceRequest->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
            ]);
        });
    }

    /**
     * Cancel a service request.
     */
    public function cancelRequest(ServiceRequest $serviceRequest, User $user): void
    {
        $this->updateStatus($serviceRequest, 'cancelled', $user);
    }
}

