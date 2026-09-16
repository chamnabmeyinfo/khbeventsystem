<?php

namespace App\Http\Controllers;

use App\Services\GitDeploymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GitDeploymentController extends Controller
{
    public function __construct(
        protected GitDeploymentService $gitDeploymentService
    ) {}

    /**
     * Get current git deployment and cPanel sync status.
     */
    public function status(Request $request): JsonResponse
    {
        if (! auth()->check() && ! auth('admin')->check()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $status = $this->gitDeploymentService->getStatus(false);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Force-refresh the Git and GitHub status.
     */
    public function refresh(Request $request): JsonResponse
    {
        if (! auth()->check() && ! auth('admin')->check()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $status = $this->gitDeploymentService->getStatus(true);

        return response()->json([
            'success' => true,
            'message' => 'Git deployment status refreshed successfully.',
            'data' => $status,
        ]);
    }
}
