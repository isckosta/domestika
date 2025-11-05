<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Professional\CreateProfessionalRequest;
use App\Http\Requests\Professional\UpdateProfessionalRequest;
use App\Http\Resources\ProfessionalResource;
use App\Jobs\GenerateProfileEmbeddingJob;
use App\Models\Professional;
use App\Services\EmbeddingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Professionals",
 *     description="Professional profile management endpoints"
 * )
 */
class ProfessionalController extends BaseController
{
    public function __construct(
        private EmbeddingService $embeddingService
    ) {
    }

    /**
     * Create a new professional profile.
     *
     * @OA\Post(
     *     path="/api/v1/professionals",
     *     summary="Create professional profile",
     *     tags={"Professionals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"bio","skills"},
     *             @OA\Property(property="bio", type="string", minLength=50, maxLength=2000),
     *             @OA\Property(property="skills", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="photo", type="string", format="binary"),
     *             @OA\Property(property="schedule", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Professional profile created successfully"
     *     ),
     *     @OA\Response(response=403, description="Already has professional profile")
     * )
     */
    public function store(CreateProfessionalRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check authorization
            $this->authorize('create', Professional::class);

            // Check if user already has a professional profile
            if ($user->professional) {
                return $this->error(
                    'Professional profile already exists',
                    'You already have a professional profile. Use update endpoint to modify it.',
                    403
                );
            }

            // Handle photo upload if provided
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('professionals/photos', 'public');
            }

            // Create professional profile
            $professional = Professional::create([
                'user_id' => $user->id,
                'bio' => $request->bio,
                'skills' => $request->skills,
                'photo' => $photoPath,
                'schedule' => $request->schedule ?? [],
                'is_active' => true,
                'reputation_score' => 0.00,
                'total_reviews' => 0,
            ]);

            // Assign professional role to user (keeps contractor role if exists)
            if (!$user->hasRole('professional')) {
                $user->assignRole('professional');
            }

            // Generate embedding asynchronously
            dispatch(new GenerateProfileEmbeddingJob($professional));

            Log::info('Professional profile created', [
                'user_id' => $user->id,
                'professional_id' => $professional->id,
            ]);

            return $this->success(
                new ProfessionalResource($professional->load('user')),
                'Professional profile created successfully. Profile embedding is being generated.',
                201
            );
        } catch (\Exception $e) {
            Log::error('Failed to create professional profile', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'Failed to create professional profile',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get current user's professional profile.
     *
     * @OA\Get(
     *     path="/api/v1/professionals/me",
     *     summary="Get my professional profile",
     *     tags={"Professionals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Professional profile retrieved successfully"
     *     ),
     *     @OA\Response(response=404, description="Professional profile not found")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $professional = $user->professional;

            if (!$professional) {
                return $this->error(
                    'Professional profile not found',
                    'You do not have a professional profile. Create one to start offering services.',
                    404
                );
            }

            return $this->success(
                new ProfessionalResource($professional->load('user')),
                'Professional profile retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve professional profile',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get a specific professional profile.
     *
     * @OA\Get(
     *     path="/api/v1/professionals/{id}",
     *     summary="Get professional profile",
     *     tags={"Professionals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Professional profile retrieved successfully"
     *     ),
     *     @OA\Response(response=404, description="Professional profile not found")
     * )
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $professional = Professional::findOrFail($id);

            // Check authorization
            $this->authorize('view', $professional);

            return $this->success(
                new ProfessionalResource($professional->load('user')),
                'Professional profile retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve professional profile',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update current user's professional profile.
     *
     * @OA\Put(
     *     path="/api/v1/professionals/me",
     *     summary="Update my professional profile",
     *     tags={"Professionals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="bio", type="string"),
     *             @OA\Property(property="skills", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="photo", type="string", format="binary"),
     *             @OA\Property(property="schedule", type="object"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Professional profile updated successfully"
     *     ),
     *     @OA\Response(response=404, description="Professional profile not found")
     * )
     */
    public function update(UpdateProfessionalRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $professional = $user->professional;

            if (!$professional) {
                return $this->error(
                    'Professional profile not found',
                    'You do not have a professional profile. Create one first.',
                    404
                );
            }

            // Check authorization
            $this->authorize('update', $professional);

            // Handle photo upload if provided
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($professional->photo && Storage::disk('public')->exists($professional->photo)) {
                    Storage::disk('public')->delete($professional->photo);
                }
                $photoPath = $request->file('photo')->store('professionals/photos', 'public');
                $professional->photo = $photoPath;
            }

            // Update professional profile
            $professional->fill($request->only(['bio', 'skills', 'schedule', 'is_active']));
            $professional->save();

            // Regenerate embedding if bio or skills changed
            if ($request->has('bio') || $request->has('skills')) {
                dispatch(new GenerateProfileEmbeddingJob($professional));
            }

            Log::info('Professional profile updated', [
                'user_id' => $user->id,
                'professional_id' => $professional->id,
            ]);

            return $this->success(
                new ProfessionalResource($professional->load('user')),
                'Professional profile updated successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to update professional profile', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'Failed to update professional profile',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Delete (deactivate) current user's professional profile.
     *
     * @OA\Delete(
     *     path="/api/v1/professionals/me",
     *     summary="Delete my professional profile",
     *     tags={"Professionals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Professional profile deleted successfully"
     *     ),
     *     @OA\Response(response=404, description="Professional profile not found")
     * )
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $professional = $user->professional;

            if (!$professional) {
                return $this->error(
                    'Professional profile not found',
                    'You do not have a professional profile.',
                    404
                );
            }

            // Check authorization
            $this->authorize('delete', $professional);

            // Soft delete professional profile
            $professional->delete();

            // Remove professional role (keeps contractor role if exists)
            $user->removeRole('professional');

            Log::info('Professional profile deleted', [
                'user_id' => $user->id,
                'professional_id' => $professional->id,
            ]);

            return $this->success(
                null,
                'Professional profile deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Failed to delete professional profile',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * List all active professionals (for contractors to browse).
     *
     * @OA\Get(
     *     path="/api/v1/professionals",
     *     summary="List professionals",
     *     tags={"Professionals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="min_reputation",
     *         in="query",
     *         description="Minimum reputation score",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Professionals retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Professional::with('user')
                ->where('is_active', true);

            // Filter by category (if skills contain category)
            if ($request->has('category')) {
                $category = $request->get('category');
                $query->whereJsonContains('skills', $category);
            }

            // Filter by minimum reputation
            if ($request->has('min_reputation')) {
                $minReputation = (float) $request->get('min_reputation');
                $query->where('reputation_score', '>=', $minReputation);
            }

            // Order by reputation score
            $professionals = $query->orderBy('reputation_score', 'desc')
                ->orderBy('total_reviews', 'desc')
                ->paginate(15);

            return $this->success([
                'professionals' => ProfessionalResource::collection($professionals),
                'pagination' => [
                    'current_page' => $professionals->currentPage(),
                    'last_page' => $professionals->lastPage(),
                    'per_page' => $professionals->perPage(),
                    'total' => $professionals->total(),
                ],
            ], 'Professionals retrieved successfully');
        } catch (\Exception $e) {
            return $this->error(
                'Failed to retrieve professionals',
                $e->getMessage(),
                500
            );
        }
    }
}

