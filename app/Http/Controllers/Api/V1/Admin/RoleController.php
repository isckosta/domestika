<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/roles",
     *     tags={"Admin - Roles"},
     *     summary="List all roles",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Roles retrieved successfully"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        return $this->success($roles, 'Roles retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/roles/{id}",
     *     tags={"Admin - Roles"},
     *     summary="Get role by ID",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Role ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role retrieved successfully"
     *     )
     * )
     */
    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        return $this->success($role, 'Role retrieved successfully');
    }
}
