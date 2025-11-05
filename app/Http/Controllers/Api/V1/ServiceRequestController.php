<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\ServiceRequest\CreateServiceRequestRequest;
use App\Http\Requests\ServiceRequest\RespondToRequestRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Jobs\MatchProfessionalsJob;
use App\Models\Professional;
use App\Models\ProfessionalResponse;
use App\Models\ServiceRequest;
use App\Services\MatchingEngineService;
use App\Services\NotificationService;
use App\Services\ServiceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Service Requests",
 *     description="Direct Request module endpoints for contractors and professionals"
 * )
 */
class ServiceRequestController extends BaseController
{
    public function __construct(
        private ServiceRequestService $serviceRequestService,
        private MatchingEngineService $matchingEngine,
        private NotificationService $notificationService
    ) {
    }

    /**
     * Create a new service request.
     *
     * @OA\Post(
     *     path="/api/v1/service-requests",
     *     summary="Create a new service request",
     *     tags={"Service Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"category", "workload_size", "frequency", "urgency"},
     *             @OA\Property(property="category", type="string", enum={"cleaning", "cooking", "laundry", "babysitting", "gardening"}),
     *             @OA\Property(property="workload_size", type="string", enum={"small", "medium", "large"}),
     *             @OA\Property(property="frequency", type="string", enum={"once", "weekly", "biweekly", "monthly"}),
     *             @OA\Property(property="urgency", type="string", enum={"low", "medium", "high"}),
     *             @OA\Property(property="description", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service request created successfully"
     *     )
     * )
     */
    public function store(CreateServiceRequestRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $serviceRequest = $this->serviceRequestService->createServiceRequest($user, $request->validated());

            // Dispatch job to match professionals after embedding is generated
            dispatch(new MatchProfessionalsJob($serviceRequest));

            return $this->success(
                new ServiceRequestResource($serviceRequest),
                'Service request created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error('Failed to create service request', $e->getMessage(), 500);
        }
    }

    /**
     * Get user's service requests.
     *
     * @OA\Get(
     *     path="/api/v1/service-requests",
     *     summary="Get user's service requests",
     *     tags={"Service Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Service requests retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $serviceRequests = ServiceRequest::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->success(
                ServiceRequestResource::collection($serviceRequests),
                'Service requests retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve service requests', $e->getMessage(), 500);
        }
    }

    /**
     * Get a specific service request.
     *
     * @OA\Get(
     *     path="/api/v1/service-requests/{id}",
     *     summary="Get a specific service request",
     *     tags={"Service Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service request retrieved successfully"
     *     )
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $serviceRequest = ServiceRequest::findOrFail($id);

            // Check authorization
            if ($request->user()->cannot('view', $serviceRequest)) {
                return $this->error('Forbidden', 'You do not have permission to view this request', 403);
            }

            return $this->success(
                new ServiceRequestResource($serviceRequest),
                'Service request retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve service request', $e->getMessage(), 500);
        }
    }

    /**
     * Get compatible service requests for professionals.
     *
     * @OA\Get(
     *     path="/api/v1/service-requests/compatible",
     *     summary="Get compatible service requests for professionals",
     *     tags={"Service Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Compatible service requests retrieved successfully"
     *     )
     * )
     */
    public function compatible(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $professional = Professional::where('user_id', $user->id)->first();

            if (!$professional) {
                return $this->error('Professional profile not found', 'You must have a professional profile to view compatible requests', 404);
            }

            // Get matched requests (where this professional appears in matched_professionals)
            $serviceRequests = ServiceRequest::where('status', 'matched')
                ->whereJsonContains('matched_professionals', [['professional_id' => $professional->id]])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->success(
                ServiceRequestResource::collection($serviceRequests),
                'Compatible service requests retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve compatible requests', $e->getMessage(), 500);
        }
    }

    /**
     * Respond to a service request (professional).
     *
     * @OA\Post(
     *     path="/api/v1/service-requests/{id}/respond",
     *     summary="Respond to a service request",
     *     tags={"Service Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"interested", "declined"}),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Response recorded successfully"
     *     )
     * )
     */
    public function respond(RespondToRequestRequest $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $serviceRequest = ServiceRequest::findOrFail($id);

            $professional = Professional::where('user_id', $user->id)->first();

            if (!$professional) {
                return $this->error('Professional profile not found', 'You must have a professional profile to respond', 404);
            }

            // Check if already responded
            $existingResponse = ProfessionalResponse::where('service_request_id', $serviceRequest->id)
                ->where('professional_id', $professional->id)
                ->first();

            if ($existingResponse) {
                return $this->error('Already responded', 'You have already responded to this request', 400);
            }

            // Create response
            $response = ProfessionalResponse::create([
                'service_request_id' => $serviceRequest->id,
                'professional_id' => $professional->id,
                'status' => $request->status,
                'message' => $request->message,
                'responded_at' => now(),
            ]);

            // Notify contractor
            if ($request->status === 'interested') {
                $this->notificationService->notifyContractorOfResponse($serviceRequest, $professional);
            }

            return $this->success(
                ['response' => $response],
                'Response recorded successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to record response', $e->getMessage(), 500);
        }
    }

    /**
     * Cancel a service request.
     *
     * @OA\Post(
     *     path="/api/v1/service-requests/{id}/cancel",
     *     summary="Cancel a service request",
     *     tags={"Service Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service request cancelled successfully"
     *     )
     * )
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $serviceRequest = ServiceRequest::findOrFail($id);

            if ($request->user()->cannot('cancel', $serviceRequest)) {
                return $this->error('Forbidden', 'You do not have permission to cancel this request', 403);
            }

            $this->serviceRequestService->cancelRequest($serviceRequest, $user);

            return $this->success(
                new ServiceRequestResource($serviceRequest->fresh()),
                'Service request cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to cancel service request', $e->getMessage(), 500);
        }
    }

    /**
     * Mark service request as completed.
     *
     * @OA\Post(
     *     path="/api/v1/service-requests/{id}/complete",
     *     summary="Mark service request as completed",
     *     tags={"Service Requests"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service request completed successfully"
     *     )
     * )
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $serviceRequest = ServiceRequest::findOrFail($id);

            if ($request->user()->cannot('complete', $serviceRequest)) {
                return $this->error('Forbidden', 'You do not have permission to complete this request', 403);
            }

            $this->serviceRequestService->updateStatus($serviceRequest, 'completed', $user);
            $this->notificationService->notifyRequestCompleted($serviceRequest);

            return $this->success(
                new ServiceRequestResource($serviceRequest->fresh()),
                'Service request completed successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to complete service request', $e->getMessage(), 500);
        }
    }
}

