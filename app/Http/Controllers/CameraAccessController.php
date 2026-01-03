<?php

namespace App\Http\Controllers;

use App\Models\UserCameraAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CameraAccessController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:can_manage_cameras');
    }

    public function index(): JsonResponse
    {
        $accessList = UserCameraAccess::where('user_id', auth()->id())
            ->orWhere('granted_by_admin_id', auth()->id())
            ->orderByRaw('user_id = ? DESC', [auth()->id()])
            ->orderBy('access_expires_at', 'desc')
            ->get();
        return response()->json(['data' => $accessList]);
    }
    public function store(Request $request): JsonResponse
    {

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'camera_id' => 'required|integer|exists:cameras,id',
            'access_starts_at' => 'nullable|date',
            'access_expires_at' => 'nullable|date|after:access_starts_at',
            'can_view' => 'required|boolean',
            'can_control' => 'required|boolean',
            'can_configure' => 'required|boolean',
        ]);

        $accessEntry = UserCameraAccess::create([
            'user_id' => $request->user_id,
            'camera_id' => $request->camera_id,
            'access_starts_at' => $request->access_starts_at,
            'access_expires_at' => $request->access_expires_at,
            'can_view' => $request->can_view,
            'can_control' => $request->can_control,
            'can_configure' => $request->can_configure,
            'granted_by_admin_id' => auth()->id(),
        ]);

        return response()->json(['data' => $accessEntry], 201);
    }

    public function show(string $id): JsonResponse
    {
        $access = UserCameraAccess::findOrfail($id)->first();
        return response()->json(['data' => $access]);
    }

    public function update(Request $request, UserCameraAccess $userCameraAccess): JsonResponse
    {
        if (auth()->id() !== $userCameraAccess->granted_by_admin_id) {
            return response()->json(['error' => 'Forbidden - You can only update your own cameras'], 403);
        }
        try {
            $request->validate([
                'access_starts_at' => 'sometimes|date',
                'access_expires_at' => 'sometimes|date|after:access_starts_at',
                'can_view' => 'sometimes|boolean',
                'can_control' => 'sometimes|boolean',
                'can_configure' => 'sometimes|boolean',
            ]);

            $userCameraAccess->update($request->only([
                'access_starts_at',
                'access_expires_at',
                'can_view',
                'can_control',
                'can_configure',
            ]));
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
        return response()->json(['data' => $userCameraAccess]);
    }

    public function destroy(UserCameraAccess $userCameraAccess): JsonResponse
    {
        if (auth()->id() !== $userCameraAccess->granted_by_admin_id) {
            return response()->json(['error' => 'Forbidden - You can only delete your own camera access entries'], 403);
        }

        $userCameraAccess->delete();
        return response()->json(['message' => 'Camera access entry deleted successfully']);
    }

    // Auto run cleanup of expired access entries
    public function cleanupExpiredAccess(): JsonResponse
    {
        $now = now();
        $expiredEntries = UserCameraAccess::whereNotNull('access_expires_at')
            ->where('access_expires_at', '<', $now)
            ->delete();

        return response()->json(['message' => 'Expired camera access entries cleaned up successfully']);
    }

}
