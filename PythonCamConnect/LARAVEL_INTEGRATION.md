# Laravel Integration Guide

This guide explains how to integrate the Python Camera Stream Server with your Laravel application.

## Architecture Overview

```
Laravel Backend (PHP)
    ↓ (HTTP POST)
Python Stream Server
    ↓ (WebSocket)
Frontend (JavaScript)
```

## Setup

### 1. Start the Python Server

```bash
# Install dependencies
pip install -r requirements.txt

# Run the server
python main.py
```

The server will start on `http://localhost:5000`

## API Endpoints

### Initialize Camera

**Endpoint:** `POST /api/camera/init`

**Request Body:**
```json
{
  "camera_id": "/dev/video0",
  "connection_type": "USB",
  "username": null,
  "password": null
}
```

Or for RTSP stream:
```json
{
  "camera_id": "192.168.1.100:554/stream1",
  "connection_type": "STREAM",
  "username": "admin",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "stream_id": "550e8400-e29b-41d4-a716-446655440000",
  "websocket_url": "/stream/550e8400-e29b-41d4-a716-446655440000",
  "camera_info": {
    "camera_id": "/dev/video0",
    "connection_type": "USB",
    "source": "0",
    "width": 640,
    "height": 480,
    "fps": 30.0,
    "status": "initialized"
  }
}
```

### List Active Cameras

**Endpoint:** `GET /api/camera/list`

**Response:**
```json
{
  "success": true,
  "cameras": [
    {
      "stream_id": "550e8400-e29b-41d4-a716-446655440000",
      "camera_id": "/dev/video0",
      "connection_type": "USB",
      "width": 640,
      "height": 480,
      "fps": 30.0,
      "status": "active",
      "websocket_url": "/stream/550e8400-e29b-41d4-a716-446655440000"
    }
  ]
}
```

### Stop Camera

**Endpoint:** `DELETE /api/camera/stop/{stream_id}`

**Response:**
```json
{
  "success": true,
  "message": "Camera stopped"
}
```

## Laravel Backend Implementation

### 1. Database Schema

Create a migration for cameras table:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('camera_id'); // /dev/video0 or RTSP URL
            $table->enum('connection_type', ['USB', 'STREAM']);
            $table->string('username')->nullable();
            $table->string('password')->nullable(); // Should be encrypted
            $table->string('stream_id')->nullable(); // UUID from Python server
            $table->string('websocket_url')->nullable();
            $table->enum('status', ['inactive', 'active', 'error'])->default('inactive');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cameras');
    }
};
```

### 2. Camera Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    protected $fillable = [
        'name',
        'camera_id',
        'connection_type',
        'username',
        'password',
        'stream_id',
        'websocket_url',
        'status'
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'password' => 'encrypted',
    ];
}
```

### 3. Camera Service

```php
<?php

namespace App\Services;

use App\Models\Camera;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CameraStreamService
{
    private $pythonServerUrl;

    public function __construct()
    {
        $this->pythonServerUrl = config('services.camera_stream.url', 'http://localhost:5000');
    }

    /**
     * Initialize a camera stream
     */
    public function initializeCamera(Camera $camera)
    {
        try {
            $payload = [
                'camera_id' => $camera->camera_id,
                'connection_type' => $camera->connection_type,
            ];

            // Add credentials for RTSP streams
            if ($camera->connection_type === 'STREAM') {
                $payload['username'] = $camera->username;
                $payload['password'] = $camera->password;
            }

            $response = Http::timeout(10)
                ->post("{$this->pythonServerUrl}/api/camera/init", $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['success']) {
                    // Update camera with stream info
                    $camera->update([
                        'stream_id' => $data['stream_id'],
                        'websocket_url' => $data['websocket_url'],
                        'status' => 'active'
                    ]);

                    return [
                        'success' => true,
                        'data' => $data
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Failed to initialize camera stream'
            ];

        } catch (\Exception $e) {
            Log::error('Camera initialization error: ' . $e->getMessage());
            
            $camera->update(['status' => 'error']);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Stop a camera stream
     */
    public function stopCamera(Camera $camera)
    {
        try {
            if (!$camera->stream_id) {
                return ['success' => true, 'message' => 'Camera not active'];
            }

            $response = Http::timeout(10)
                ->delete("{$this->pythonServerUrl}/api/camera/stop/{$camera->stream_id}");

            $camera->update([
                'stream_id' => null,
                'websocket_url' => null,
                'status' => 'inactive'
            ]);

            return [
                'success' => true,
                'message' => 'Camera stopped'
            ];

        } catch (\Exception $e) {
            Log::error('Camera stop error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get list of active streams from Python server
     */
    public function getActiveStreams()
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->pythonServerUrl}/api/camera/list");

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'cameras' => []];

        } catch (\Exception $e) {
            Log::error('Get active streams error: ' . $e->getMessage());
            return ['success' => false, 'cameras' => []];
        }
    }
}
```

### 4. Camera Controller

```php
<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Services\CameraStreamService;
use Illuminate\Http\Request;

class CameraController extends Controller
{
    private $streamService;

    public function __construct(CameraStreamService $streamService)
    {
        $this->streamService = $streamService;
    }

    /**
     * Display list of cameras
     */
    public function index()
    {
        $cameras = Camera::all();
        return view('cameras.index', compact('cameras'));
    }

    /**
     * Show camera details and stream
     */
    public function show(Camera $camera)
    {
        return view('cameras.show', compact('camera'));
    }

    /**
     * Start camera stream
     */
    public function start(Camera $camera)
    {
        $result = $this->streamService->initializeCamera($camera);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Camera stream started',
                'camera' => $camera->fresh()
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error']
        ], 500);
    }

    /**
     * Stop camera stream
     */
    public function stop(Camera $camera)
    {
        $result = $this->streamService->stopCamera($camera);
        
        return response()->json($result);
    }

    /**
     * Get stream URL for a camera
     */
    public function getStreamUrl(Camera $camera)
    {
        if (!$camera->stream_id || $camera->status !== 'active') {
            return response()->json([
                'success' => false,
                'error' => 'Camera stream not active'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'websocket_url' => config('services.camera_stream.ws_url', 'ws://localhost:5000') . $camera->websocket_url
        ]);
    }
}
```

### 5. Routes

```php
<?php

use App\Http\Controllers\CameraController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/cameras', [CameraController::class, 'index'])->name('cameras.index');
    Route::get('/cameras/{camera}', [CameraController::class, 'show'])->name('cameras.show');
    Route::post('/cameras/{camera}/start', [CameraController::class, 'start'])->name('cameras.start');
    Route::post('/cameras/{camera}/stop', [CameraController::class, 'stop'])->name('cameras.stop');
    Route::get('/cameras/{camera}/stream-url', [CameraController::class, 'getStreamUrl'])->name('cameras.stream-url');
});
```

### 6. Configuration

Add to `config/services.php`:

```php
'camera_stream' => [
    'url' => env('CAMERA_STREAM_URL', 'http://localhost:5000'),
    'ws_url' => env('CAMERA_STREAM_WS_URL', 'ws://localhost:5000'),
],
```

Add to `.env`:

```
CAMERA_STREAM_URL=http://localhost:5000
CAMERA_STREAM_WS_URL=ws://localhost:5000
```

## Frontend Implementation

### Blade Template (cameras/show.blade.php)

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $camera->name }}</h1>
    
    <div class="card">
        <div class="card-body">
            <p><strong>Type:</strong> {{ $camera->connection_type }}</p>
            <p><strong>Camera ID:</strong> {{ $camera->camera_id }}</p>
            <p><strong>Status:</strong> 
                <span class="badge badge-{{ $camera->status === 'active' ? 'success' : 'secondary' }}">
                    {{ ucfirst($camera->status) }}
                </span>
            </p>

            <div class="mt-3">
                @if($camera->status === 'active')
                    <button id="stopBtn" class="btn btn-danger">Stop Stream</button>
                @else
                    <button id="startBtn" class="btn btn-success">Start Stream</button>
                @endif
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">Live Stream</div>
        <div class="card-body">
            <canvas id="streamCanvas" width="640" height="480" style="max-width: 100%; border: 1px solid #ccc;"></canvas>
            <p id="streamStatus" class="mt-2 text-muted">Stream not active</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let ws = null;
let canvas = document.getElementById('streamCanvas');
let ctx = canvas.getContext('2d');
let statusEl = document.getElementById('streamStatus');

// Start stream
document.getElementById('startBtn')?.addEventListener('click', async () => {
    try {
        const response = await fetch('/cameras/{{ $camera->id }}/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            alert('Stream started successfully');
            window.location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        alert('Error starting stream: ' + error.message);
    }
});

// Stop stream
document.getElementById('stopBtn')?.addEventListener('click', async () => {
    if (ws) {
        ws.close();
        ws = null;
    }

    try {
        const response = await fetch('/cameras/{{ $camera->id }}/stop', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();
        
        if (data.success) {
            alert('Stream stopped successfully');
            window.location.reload();
        }
    } catch (error) {
        alert('Error stopping stream: ' + error.message);
    }
});

// Connect to WebSocket stream if camera is active
@if($camera->status === 'active' && $camera->websocket_url)
async function connectStream() {
    try {
        const response = await fetch('/cameras/{{ $camera->id }}/stream-url');
        const data = await response.json();
        
        if (data.success) {
            const wsUrl = data.websocket_url;
            ws = new WebSocket(wsUrl);
            ws.binaryType = 'arraybuffer';

            ws.onopen = () => {
                console.log('WebSocket connected');
                statusEl.textContent = 'Connected - Streaming...';
                statusEl.className = 'mt-2 text-success';
            };

            ws.onmessage = (event) => {
                const blob = new Blob([event.data], { type: 'image/jpeg' });
                const url = URL.createObjectURL(blob);
                const img = new Image();
                
                img.onload = () => {
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    URL.revokeObjectURL(url);
                };
                
                img.src = url;
            };

            ws.onerror = (error) => {
                console.error('WebSocket error:', error);
                statusEl.textContent = 'Connection error';
                statusEl.className = 'mt-2 text-danger';
            };

            ws.onclose = () => {
                console.log('WebSocket closed');
                statusEl.textContent = 'Connection closed';
                statusEl.className = 'mt-2 text-muted';
            };
        }
    } catch (error) {
        console.error('Error connecting to stream:', error);
        statusEl.textContent = 'Failed to connect';
        statusEl.className = 'mt-2 text-danger';
    }
}

connectStream();
@endif
</script>
@endsection
```

## Testing

### Test USB Camera

```bash
curl -X POST http://localhost:5000/api/camera/init \
  -H "Content-Type: application/json" \
  -d '{
    "camera_id": "/dev/video0",
    "connection_type": "USB"
  }'
```

### Test RTSP Stream

```bash
curl -X POST http://localhost:5000/api/camera/init \
  -H "Content-Type: application/json" \
  -d '{
    "camera_id": "192.168.1.100:554/stream1",
    "connection_type": "STREAM",
    "username": "admin",
    "password": "password123"
  }'
```

### Test from Laravel

```php
// In tinker or controller
use App\Models\Camera;
use App\Services\CameraStreamService;

$camera = Camera::create([
    'name' => 'Front Door Camera',
    'camera_id' => '/dev/video0',
    'connection_type' => 'USB'
]);

$service = new CameraStreamService();
$result = $service->initializeCamera($camera);

dd($result);
```

## Production Considerations

1. **Security:**
   - Use HTTPS/WSS in production
   - Implement authentication for Python API
   - Encrypt camera credentials in database
   - Use environment variables for sensitive data

2. **Performance:**
   - Consider using Redis for session storage
   - Implement connection pooling
   - Monitor resource usage
   - Use nginx as reverse proxy

3. **Reliability:**
   - Add health checks
   - Implement automatic reconnection
   - Add logging and monitoring
   - Handle network interruptions gracefully

4. **Scaling:**
   - Use load balancer for multiple Python servers
   - Implement camera affinity (same camera → same server)
   - Consider using message queue for camera management
