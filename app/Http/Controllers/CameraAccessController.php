<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\UserCameraAccess;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CameraAccessController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:can_manage_cameras');
    }

    /**
     * Get all access entries for a specific camera
     */
    public function getCameraAccessList(int $cameraId): JsonResponse
    {
        $camera = Camera::find($cameraId);
        if (!$camera) {
            return response()->json(['error' => 'Camera not found'], 404);
        }

        $accessList = UserCameraAccess::where('camera_id', $cameraId)
            ->with(['user:id,name,email', 'grantedByAdmin:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($access) {
                return [
                    'id' => $access->id,
                    'user_id' => $access->user_id,
                    'user_name' => $access->user->name ?? 'Unknown',
                    'user_email' => $access->user->email ?? 'Unknown',
                    'camera_id' => $access->camera_id,
                    'can_view' => (bool) $access->can_view,
                    'can_control' => (bool) $access->can_control,
                    'can_configure' => (bool) $access->can_configure,
                    'access_starts_at' => $access->access_starts_at?->format('Y-m-d H:i'),
                    'access_expires_at' => $access->access_expires_at?->format('Y-m-d H:i'),
                    'is_expired' => $access->access_expires_at && $access->access_expires_at->isPast(),
                    'is_active' => !$access->access_expires_at || $access->access_expires_at->isFuture(),
                    'granted_by' => $access->grantedByAdmin->name ?? 'System',
                    'created_at' => $access->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'camera_id' => $cameraId,
            'access_list' => $accessList
        ]);
    }

    /**
     * Grant camera access to a user
     */
    public function grantAccess(Request $request, int $cameraId): JsonResponse
    {
        $camera = Camera::find($cameraId);
        if (!$camera) {
            return response()->json(['error' => 'Camera not found'], 404);
        }

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'can_view' => 'required|boolean',
            'can_control' => 'required|boolean',
            'can_configure' => 'required|boolean',
            'access_starts_at' => 'nullable|date',
            'access_expires_at' => 'nullable|date|after:access_starts_at',
        ]);

        // Check if user already has access to this camera
        $existingAccess = UserCameraAccess::where('user_id', $request->user_id)
            ->where('camera_id', $cameraId)
            ->first();

        if ($existingAccess) {
            return response()->json([
                'error' => 'User already has access to this camera. Use update instead.'
            ], 422);
        }

        // Don't allow granting access to admin users
        $user = User::find($request->user_id);
        if ($user && $user->is_admin) {
            return response()->json([
                'error' => 'Admin users already have full access to all cameras.'
            ], 422);
        }

        $accessEntry = UserCameraAccess::create([
            'user_id' => $request->user_id,
            'camera_id' => $cameraId,
            'can_view' => $request->can_view,
            'can_control' => $request->can_control,
            'can_configure' => $request->can_configure,
            'access_starts_at' => $request->access_starts_at ?? now(),
            'access_expires_at' => $request->access_expires_at,
            'granted_by_admin_id' => auth()->id(),
        ]);

        $accessEntry->load(['user:id,name,email', 'grantedByAdmin:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Camera access granted successfully',
            'access' => [
                'id' => $accessEntry->id,
                'user_id' => $accessEntry->user_id,
                'user_name' => $accessEntry->user->name ?? 'Unknown',
                'user_email' => $accessEntry->user->email ?? 'Unknown',
                'camera_id' => $accessEntry->camera_id,
                'can_view' => (bool) $accessEntry->can_view,
                'can_control' => (bool) $accessEntry->can_control,
                'can_configure' => (bool) $accessEntry->can_configure,
                'access_starts_at' => $accessEntry->access_starts_at?->format('Y-m-d H:i'),
                'access_expires_at' => $accessEntry->access_expires_at?->format('Y-m-d H:i'),
                'granted_by' => $accessEntry->grantedByAdmin->name ?? 'System',
            ]
        ], 201);
    }

    /**
     * Update user's camera access permissions
     */
    public function updateAccess(Request $request, int $cameraId, int $accessId): JsonResponse
    {
        $accessEntry = UserCameraAccess::where('id', $accessId)
            ->where('camera_id', $cameraId)
            ->first();

        if (!$accessEntry) {
            return response()->json(['error' => 'Access entry not found'], 404);
        }

        $request->validate([
            'can_view' => 'sometimes|boolean',
            'can_control' => 'sometimes|boolean',
            'can_configure' => 'sometimes|boolean',
            'access_starts_at' => 'sometimes|nullable|date',
            'access_expires_at' => 'sometimes|nullable|date',
        ]);

        $accessEntry->update($request->only([
            'can_view',
            'can_control',
            'can_configure',
            'access_starts_at',
            'access_expires_at',
        ]));

        $accessEntry->load(['user:id,name,email', 'grantedByAdmin:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Camera access updated successfully',
            'access' => [
                'id' => $accessEntry->id,
                'user_id' => $accessEntry->user_id,
                'user_name' => $accessEntry->user->name ?? 'Unknown',
                'user_email' => $accessEntry->user->email ?? 'Unknown',
                'camera_id' => $accessEntry->camera_id,
                'can_view' => (bool) $accessEntry->can_view,
                'can_control' => (bool) $accessEntry->can_control,
                'can_configure' => (bool) $accessEntry->can_configure,
                'access_starts_at' => $accessEntry->access_starts_at?->format('Y-m-d H:i'),
                'access_expires_at' => $accessEntry->access_expires_at?->format('Y-m-d H:i'),
                'granted_by' => $accessEntry->grantedByAdmin->name ?? 'System',
            ]
        ]);
    }

    /**
     * Revoke user's camera access
     */
    public function revokeAccess(int $cameraId, int $accessId): JsonResponse
    {
        $accessEntry = UserCameraAccess::where('id', $accessId)
            ->where('camera_id', $cameraId)
            ->first();

        if (!$accessEntry) {
            return response()->json(['error' => 'Access entry not found'], 404);
        }

        $userName = $accessEntry->user->name ?? 'Unknown';
        $accessEntry->delete();

        return response()->json([
            'success' => true,
            'message' => "Camera access revoked for {$userName}"
        ]);
    }

    // Legacy methods below - kept for backwards compatibility

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
