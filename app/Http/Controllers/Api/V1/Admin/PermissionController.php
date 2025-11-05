<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/admin/permissions",
     *     tags={"Admin - Permissions"},
     *     summary="List all permissions",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Permissions retrieved successfully"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::all();

        return $this->success($permissions, 'Permissions retrieved successfully');
    }
}
