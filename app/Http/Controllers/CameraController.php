<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CameraController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:can_manage_cameras')->only(['store', 'update', 'destroy', 'toggleOnOff']);
        $this->middleware('permission:can_read')->only(['index', 'show', 'getUserCameras']);
    }

    public function index(): JsonResponse
    {
        $cameras = Camera::with('user:id,name')->paginate(5);
        return response()->json(['data' => $cameras]);
    }

    // Get cameras for regular users (their own cameras + shared access)
    public function getUserCameras(): JsonResponse
    {
        $user = auth()->user();

        // Get cameras based on user role
        if ($user->is_admin) {
            // Admin sees all cameras
            $cameras = Camera::with('user:id,name')->get();
        } else {
            // Regular users see cameras they have access to
            // For now, return all active cameras (can be modified to filter by user_camera_access)
            $cameras = Camera::where('is_active', true)
                ->with('user:id,name')
                ->get();
        }

        return response()->json(['data' => $cameras]);
    }

    public function store(Request $request): JsonResponse
    {
        try {

            $request->validate([
                'name' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'connection_type' => 'required|in:USB,STREAM',
                'usb_path' => 'nullable|required_if:connection_type,USB|string|max:255',
                'stream_url' => 'nullable|required_if:connection_type,STREAM|url|max:500',
                'stream_username' => 'nullable|string|max:255',
                'stream_password' => 'nullable|string|max:255',
                'description' => 'required|string',
                'resolution' => 'nullable|string|max:50',
                'frame_rate' => 'nullable|integer|min:1|max:120',
                // Detection settings validation
                'detection_type' => 'nullable|in:NONE,MOTION,HUMAN,MOTION_HUMAN',
                'detection_enabled' => 'nullable|boolean',
                'detection_sensitivity' => 'nullable|integer|min:1|max:100',
                'recording_duration' => 'nullable|integer|min:30|max:600',
                // Thumbnail settings
                'thumbnail_enabled' => 'nullable|boolean',
            ]);
            $camera = Camera::create([
                'name' => $request->name,
                'location' => $request->location,
                'connection_type' => $request->connection_type,
                'usb_path' => $request->usb_path,
                'stream_url' => $request->stream_url,
                'stream_username' => $request->stream_username,
                'stream_password' => $request->stream_password,
                'description' => $request->description,
                'resolution' => $request->resolution ?? '640x640',
                'frame_rate' => $request->frame_rate ?? 30,
                'added_by' => auth()->id(),
                // Detection settings
                'detection_type' => $request->detection_type ?? 'NONE',
                'detection_enabled' => $request->detection_enabled ?? false,
                'detection_sensitivity' => $request->detection_sensitivity ?? 50,
                'recording_duration' => $request->recording_duration ?? 180,
                // Thumbnail settings
                'thumbnail_enabled' => $request->thumbnail_enabled ?? true,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
        return response()->json(['data' => $camera], 201);
    }

    public function show(string $id): JsonResponse
    {
        // fixed: findOrFail already returns the model, no ->first()
        $camera = Camera::findOrFail($id);
        return response()->json(['data' => $camera]);
    }

    public function update(Request $request, Camera $camera): JsonResponse
    {
        if (auth()->id() !== $camera->added_by) {
            return response()->json(['error' => 'Forbidden - You can only update your own cameras'], 403);
        }
        try {
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'location' => 'sometimes|required|string|max:255',
                // fixed validation rule to use `in`
                'connection_type' => 'sometimes|required|in:USB,STREAM',
                'usb_path' => 'sometimes|required_if:connection_type,USB|string|max:255',
                'stream_url' => 'sometimes|required_if:connection_type,STREAM|url|max:500',
                'stream_username' => 'nullable|string|max:255',
                'stream_password' => 'nullable|string|max:255',
                'description' => 'sometimes|required|string',
                'resolution' => 'nullable|string|max:50',
                'frame_rate' => 'nullable|integer|min:1|max:120',
                // Detection settings validation
                'detection_type' => 'nullable|in:NONE,MOTION,HUMAN,MOTION_HUMAN',
                'detection_enabled' => 'nullable|boolean',
                'detection_sensitivity' => 'nullable|integer|min:1|max:100',
                'recording_duration' => 'nullable|integer|min:30|max:600',
                // Thumbnail settings
                'thumbnail_enabled' => 'nullable|boolean',
            ]);
            $camera->update($request->all());
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['success' => 'Camera updated successfully']);
    }

    public function destroy(Camera $camera): JsonResponse
    {
        if (auth()->id() !== $camera->added_by) {
            return response()->json(['error' => 'Forbidden - You can only delete your own cameras'], 403);
        }
        $camera->delete();
        return response()->json(['success' => 'Camera deleted successfully']);
    }

    public function toggleOnOff(string $id): JsonResponse
    {
        $camera = Camera::findOrFail($id);
        if (auth()->id() !== $camera->added_by) {
            return response()->json(['error' => 'Forbidden - You can only toggle your own cameras'], 403);
        }
        if($camera->is_active) {
            $camera->is_active = false;
            $camera->websocket_url = null;
            // check if there is an active stream in listActiveCameras, take the stream_id and call shutdownStream
            $activeCameras = $this->listActiveCameras();
            $this->shutdownStream($activeCameras->original['data']['cameras'][0]['stream_id'] ?? '');


        } elseif(!$camera->is_active) {
            $camera->is_active = true;
            //new json request to init camera
            $request = new Request(['camera_id' => $camera->id]);
            $this->initCamera($request);
        }
        $camera->save();
        return response()->json(['success' => 'Camera toggled successfully', 'is_active' => $camera->is_active]);

    }

    public function initCamera(Request $request): JsonResponse
    {
        $cameraId = $request->input('camera_id');
        $camera = Camera::find($cameraId);
        if (!$camera) {
            return response()->json(['error' => 'Camera not found'], 404);
        }

        $body = [
            'camera_id' => $camera->id,  // Use actual camera path, not DB id
            'connection_type' => $camera->connection_type,
            // Detection settings
            'detection_type' => $camera->detection_type ?? 'NONE',
            'detection_sensitivity' => $camera->detection_sensitivity ?? 50,
            'recording_duration' => $camera->recording_duration ?? 180,
        ];
        if($camera->connection_type === 'USB') {
            $body['camera_connection_path'] = $camera->usb_path;
        } else {
            $body['camera_connection_path'] = $camera->stream_url;
        }

        // Add authentication for STREAM type
        if ($camera->connection_type === 'STREAM') {
            if ($camera->stream_username) {
                $body['username'] = $camera->stream_username;
            }
            if ($camera->stream_password) {
                $body['password'] = $camera->stream_password;
            }
        }

        try {
            $response = Http::timeout(15)->post('http://localhost:5000/api/camera/init', $body);

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Failed to init camera',
                    'status' => $response->status(),
                    'details' => $response->body()
                ], 500);
            }

            $data = $response->json();

            if (!isset($data['success']) || !$data['success']) {
                return response()->json([
                    'error' => 'Camera initialization unsuccessful',
                    'response' => $data
                ], 500);
            }

            $http = 'http://localhost:5000';
            $websocketUrl = $http . ($data['websocket_url'] ?? '');

            // Update camera with websocket URL and set active
            $camera->is_active = true;
            $camera->websocket_url = $websocketUrl;
            $camera->save();

            return response()->json([
                'success' => true,
                'camera_id' => $camera->id,
                'websocket_url' => $websocketUrl,
                'stream_id' => $data['stream_id'] ?? null,
                'camera_info' => $data['camera_info'] ?? null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Exception while initializing camera',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function listActiveCameras(): JsonResponse
    {
        $active = $this->fetchActiveCameras();
        return response()->json(['data' => $active]);
    }

    private function fetchActiveCameras(): array
    {
        try {
            $resp = Http::timeout(5)->get('http://localhost:5000/api/camera/list');
            if ($resp->successful()) {
                return $resp->json();
            }
        } catch (\Exception $e) {
            // Log error but return empty structure to avoid breaking the flow
            \Log::warning('Failed to fetch active cameras from Python service: ' . $e->getMessage());
        }
        return ['success' => false, 'cameras' => []];
    }

    public function shutdownStream(string $id): JsonResponse
    {
        try {
            // Step 1: Try to identify which camera this stream belongs to (optional but helpful for DB update)
            $cameraToUpdate = null;
            $activeStreams = $this->fetchActiveCameras();

            if (isset($activeStreams['success']) && $activeStreams['success']) {
                foreach ($activeStreams['cameras'] ?? [] as $stream) {
                    if (isset($stream['stream_id']) && $stream['stream_id'] === $id) {
                        // Found the stream, get camera_id to match with database
                        $cameraIdentifier = $stream['camera_id'] ?? null;
                        if ($cameraIdentifier) {
                            $cameraToUpdate = Camera::where('stream_url', $cameraIdentifier)
                                                   ->orWhere('usb_path', $cameraIdentifier)
                                                   ->first();
                        }
                        break;
                    }
                }
            }

            $response = Http::timeout(10)->delete("http://localhost:5000/api/camera/stop/{$id}");
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to stop stream on streaming service',
                    'status' => $response->status(),
                    'details' => $response->body()
                ], $response->status());
            }
            $data = $response->json();

            if ($cameraToUpdate) {
                $cameraToUpdate->is_active = false;
                $cameraToUpdate->websocket_url = null;
                $cameraToUpdate->save();
            }

            return response()->json([
                'success' => true,
                'message' => $data['message'] ?? 'Camera stopped',
                'stream_id' => $id,
                'camera_updated' => $cameraToUpdate ? [
                    'id' => $cameraToUpdate->id,
                    'name' => $cameraToUpdate->name,
                    'is_active' => false
                ] : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Exception while stopping stream',
                'message' => $e->getMessage(),
                'stream_id' => $id
            ], 500);
        }
    }

    public function getCameraInfo(string $id): JsonResponse
    {
        $camera = Camera::findOrFail($id);
        try {
            if (!$camera) {
                return response()->json(['error' => 'Camera not found'], 404);
            }
            if ($camera->connection_type === 'STREAM') {
                $body = [
                    'camera_connection_path' => $camera->stream_url,
                ];
            } else {
                $body = [
                    'camera_connection_path' => $camera->usb_path,
                ];
            }
            $response = Http::timeout(15)->post('http://localhost:5000/api/camera/info', $body);
            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Failed to fetch camera info',
                    'status' => $response->status(),
                    'details' => $response->body()
                ], 500);
            }
            $data = $response->json();

            return response()->json([
                'camera_id' => $camera->id,
                'frame_rate' => $data['data']['frame_rate'] ?? null,
                'resolution' => $data['data']['resolution'] ?? null
            ]);
        }
        catch (Exception $e) {
            return response()->json([
                'error' => 'Exception while fetching camera info',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update detection settings for a camera
     */
    public function updateDetectionSettings(Request $request, string $id): JsonResponse
    {
        $camera = Camera::findOrFail($id);

        if (auth()->id() !== $camera->added_by && !auth()->user()->is_admin) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        try {
            $request->validate([
                'detection_type' => 'required|in:NONE,MOTION,HUMAN,MOTION_HUMAN',
                'detection_sensitivity' => 'nullable|integer|min:1|max:100',
                'recording_duration' => 'nullable|integer|min:30|max:600',
            ]);

            $camera->update([
                'detection_type' => $request->detection_type,
                'detection_enabled' => $request->detection_type !== 'NONE',
                'detection_sensitivity' => $request->detection_sensitivity ?? $camera->detection_sensitivity,
                'recording_duration' => $request->recording_duration ?? $camera->recording_duration,
            ]);

            // If camera is active, update the Python stream settings
            if ($camera->is_active && $camera->websocket_url) {
                // Get stream_id from active cameras
                $activeStreams = $this->fetchActiveCameras();
                $streamId = null;

                if (isset($activeStreams['success']) && $activeStreams['success']) {
                    foreach ($activeStreams['cameras'] ?? [] as $stream) {
                        if ($stream['camera_id'] == $camera->id) {
                            $streamId = $stream['stream_id'];
                            break;
                        }
                    }
                }

                if ($streamId) {
                    Http::timeout(10)->post("http://localhost:5000/api/camera/detection/{$streamId}", [
                        'detection_type' => $request->detection_type,
                        'sensitivity' => $request->detection_sensitivity ?? 50,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Detection settings updated',
                'camera' => $camera->fresh()
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get detection status for a camera
     */
    public function getDetectionStatus(string $id): JsonResponse
    {
        $camera = Camera::findOrFail($id);

        $status = [
            'camera_id' => $camera->id,
            'detection_type' => $camera->detection_type,
            'detection_enabled' => $camera->detection_enabled,
            'detection_sensitivity' => $camera->detection_sensitivity,
            'recording_duration' => $camera->recording_duration,
            'is_active' => $camera->is_active,
        ];

        // Get live status from Python if camera is active
        if ($camera->is_active) {
            $activeStreams = $this->fetchActiveCameras();

            if (isset($activeStreams['success']) && $activeStreams['success']) {
                foreach ($activeStreams['cameras'] ?? [] as $stream) {
                    if ($stream['camera_id'] == $camera->id) {
                        $status['stream_id'] = $stream['stream_id'];
                        $status['live_detection_status'] = $stream['detection_status'] ?? null;
                        break;
                    }
                }
            }
        }

        return response()->json(['success' => true, 'status' => $status]);
    }

    /**
     * Get recordings for a camera
     */
    public function getRecordings(string $id): JsonResponse
    {
        $camera = Camera::findOrFail($id);

        try {
            $response = Http::timeout(10)->get("http://localhost:5000/api/recordings/{$id}");

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'camera_id' => $id,
                    'recordings' => $data['recordings'] ?? []
                ]);
            }

            return response()->json(['success' => true, 'recordings' => []]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get thumbnails for a camera
     */
    public function getThumbnails(string $id): JsonResponse
    {
        $camera = Camera::findOrFail($id);

        try {
            $response = Http::timeout(10)->get("http://localhost:5000/api/thumbnails/{$id}");

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'camera_id' => $id,
                    'thumbnails' => $data['thumbnails'] ?? []
                ]);
            }

            return response()->json(['success' => true, 'thumbnails' => []]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Capture a thumbnail manually
     */
    public function captureThumbnail(string $id): JsonResponse
    {
        $camera = Camera::findOrFail($id);

        if (!$camera->is_active) {
            return response()->json(['error' => 'Camera is not active'], 400);
        }

        try {
            // Get stream_id from active cameras
            $activeStreams = $this->fetchActiveCameras();
            $streamId = null;

            if (isset($activeStreams['success']) && $activeStreams['success']) {
                foreach ($activeStreams['cameras'] ?? [] as $stream) {
                    if ($stream['camera_id'] == $camera->id) {
                        $streamId = $stream['stream_id'];
                        break;
                    }
                }
            }

            if (!$streamId) {
                return response()->json(['error' => 'Stream not found'], 404);
            }

            $response = Http::timeout(10)->post("http://localhost:5000/api/camera/thumbnail/{$streamId}");

            if ($response->successful()) {
                $data = $response->json();

                // Update camera thumbnail URL
                if (isset($data['thumbnail']['file_name'])) {
                    $camera->thumbnail_url = "http://localhost:5000/api/thumbnail/{$id}/{$data['thumbnail']['file_name']}";
                    $camera->save();
                }

                return response()->json([
                    'success' => true,
                    'thumbnail' => $data['thumbnail'] ?? null
                ]);
            }

            return response()->json(['error' => 'Failed to capture thumbnail'], 500);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
