<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/v1/admin/users",
     *     tags={"Admin - Users"},
     *     summary="Create a new user",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="email_verified", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully"
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', User::class);

            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ];

            // Mark email as verified if requested
            if ($request->boolean('email_verified')) {
                $userData['email_verified_at'] = now();
            }

            $user = User::create($userData);

            // Assign roles if provided
            if ($request->has('roles') && is_array($request->roles)) {
                $user->assignRole($request->roles);
            } else {
                // Default role: contractor
                $user->assignRole('contractor');
            }

            // Send verification email if not verified
            if (!$user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
            }

            Log::info('User created by admin', [
                'created_by' => $request->user()->id,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return $this->success(
                new UserResource($user->load('roles')),
                'User created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error(
                'Failed to create user',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/users",
     *     tags={"Admin - Users"},
     *     summary="List all users",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')
            ->paginate(request('per_page', 15));

        return $this->success(
            UserResource::collection($users)->response()->getData(true),
            'Users retrieved successfully'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/users/{id}",
     *     tags={"Admin - Users"},
     *     summary="Get user by ID",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully"
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load('roles', 'permissions');

        return $this->success(
            new UserResource($user),
            'User retrieved successfully'
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/users/{id}",
     *     tags={"Admin - Users"},
     *     summary="Update user",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="email_verified", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully"
     *     ),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $this->authorize('update', $user);

            // Update basic fields
            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email') && $request->email !== $user->email) {
                $user->email = $request->email;
                $user->email_verified_at = null; // Reset verification if email changed
            }

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            // Handle email verification
            if ($request->has('email_verified')) {
                if ($request->boolean('email_verified')) {
                    $user->email_verified_at = now();
                } else {
                    $user->email_verified_at = null;
                }
            }

            $user->save();

            // Update roles if provided
            if ($request->has('roles') && is_array($request->roles)) {
                $user->syncRoles($request->roles);
            }

            Log::info('User updated by admin', [
                'updated_by' => $request->user()->id,
                'user_id' => $user->id,
            ]);

            return $this->success(
                new UserResource($user->load('roles')),
                'User updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error(
                'Failed to update user',
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/admin/users/{id}",
     *     tags={"Admin - Users"},
     *     summary="Delete user",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully"
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        Log::info('User deleted by admin', [
            'deleted_by' => request()->user()->id,
            'user_id' => $user->id,
        ]);

        return $this->success(null, 'User deleted successfully');
    }
}
