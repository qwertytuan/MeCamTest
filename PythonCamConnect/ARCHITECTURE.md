# System Architecture

## Overview

This document describes the complete architecture of the Camera Stream Server and its integration with Laravel.

## Components

### 1. Python Stream Server (Flask)
- **Port:** 5000 (configurable)
- **Protocol:** HTTP/WebSocket
- **Framework:** Flask with Flask-Sock
- **Purpose:** Manage cameras and stream video

### 2. Laravel Backend (PHP)
- **Database:** MySQL/PostgreSQL
- **Purpose:** Store camera configurations, manage authentication
- **Communication:** HTTP REST API to Python server

### 3. Frontend (JavaScript)
- **Technology:** Vanilla JS or Vue/React
- **Purpose:** Display video streams via WebSocket
- **Protocol:** WebSocket (binary, JPEG frames)

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                        Frontend Layer                        │
│  ┌────────────────────────────────────────────────────┐    │
│  │              Browser / JavaScript                   │    │
│  │  • HTML5 Canvas for video display                  │    │
│  │  • WebSocket client for frame reception            │    │
│  │  • User interface controls                         │    │
│  └─────────────────┬──────────────────────────────────┘    │
└────────────────────┼───────────────────────────────────────┘
                     │
                     │ HTTP (API calls)
                     │ WebSocket (video stream)
                     │
┌────────────────────┼───────────────────────────────────────┐
│                    ↓                                         │
│                Laravel Backend Layer                         │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Database (MySQL/PostgreSQL)                         │  │
│  │    cameras table:                                    │  │
│  │      - id, name, camera_id                          │  │
│  │      - connection_type (USB/STREAM)                 │  │
│  │      - username, password (encrypted)               │  │
│  │      - stream_id, websocket_url                     │  │
│  │      - status                                       │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Services                                            │  │
│  │    - CameraStreamService                            │  │
│  │      • initializeCamera()                           │  │
│  │      • stopCamera()                                 │  │
│  │      • getActiveStreams()                           │  │
│  └────────────────┬─────────────────────────────────────┘  │
└─────────────────────┼──────────────────────────────────────┘
                      │
                      │ HTTP POST/GET/DELETE
                      │ JSON payloads
                      │
┌─────────────────────┼──────────────────────────────────────┐
│                     ↓                                        │
│              Python Stream Server                            │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Flask Application (main.py)                         │  │
│  │    Endpoints:                                        │  │
│  │      • POST /api/camera/init                        │  │
│  │      • GET /api/camera/list                         │  │
│  │      • DELETE /api/camera/stop/{stream_id}          │  │
│  │      • WS /stream/{stream_id}                       │  │
│  └────────────────┬─────────────────────────────────────┘  │
│                   │                                          │
│  ┌────────────────┴─────────────────────────────────────┐  │
│  │  Camera Utils (utils/camera_utils.py)               │  │
│  │    Functions:                                        │  │
│  │      • parse_camera_source()                        │  │
│  │      • initialize_camera()                          │  │
│  │      • capture_and_broadcast()                      │  │
│  └────────────────┬─────────────────────────────────────┘  │
│                   │                                          │
│  ┌────────────────┴─────────────────────────────────────┐  │
│  │  OpenCV (cv2)                                        │  │
│  │    • VideoCapture for USB/RTSP                      │  │
│  │    • Frame capture and encoding                     │  │
│  └────────────────┬─────────────────────────────────────┘  │
└────────────────────┼──────────────────────────────────────┘
                     │
                     │ USB / RTSP
                     ↓
┌──────────────────────────────────────────────────────────┐
│                    Camera Layer                           │
│  ┌────────────────────┐      ┌─────────────────────┐    │
│  │   USB Cameras      │      │   IP Cameras        │    │
│  │   /dev/video*      │      │   RTSP Streams      │    │
│  └────────────────────┘      └─────────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

## Data Flow

### Initialization Flow

```
1. User Action (Frontend)
   │
   ↓
2. Laravel Controller
   │ Query database for camera config
   ↓
3. CameraStreamService
   │ Prepare request payload
   ↓
4. HTTP POST → Python Server
   │ {camera_id, connection_type, username, password}
   ↓
5. initialize_camera()
   │ Test camera connection
   ↓
6. Create capture thread
   │ Start streaming
   ↓
7. Return Response
   │ {stream_id, websocket_url, camera_info}
   ↓
8. Laravel saves stream_id
   │ Update database
   ↓
9. Return to Frontend
   │ {websocket_url}
   ↓
10. Frontend connects WebSocket
```

### Streaming Flow

```
┌─────────────┐
│   Camera    │
└──────┬──────┘
       │ Capture frames (USB/RTSP)
       ↓
┌─────────────────────┐
│  capture_and_       │
│  broadcast()        │
│  (Thread)           │
└──────┬──────────────┘
       │ Encode JPEG
       ↓
┌─────────────────────┐
│  WebSocket          │
│  Broadcasting       │
└──────┬──────────────┘
       │ Send binary JPEG
       ↓
┌─────────────────────┐
│  Connected Clients  │
│  (Multiple)         │
└──────┬──────────────┘
       │ Receive frames
       ↓
┌─────────────────────┐
│  Canvas Display     │
│  (Browser)          │
└─────────────────────┘
```

## Request/Response Examples

### Initialize USB Camera

**Request:**
```http
POST http://localhost:5000/api/camera/init
Content-Type: application/json

{
  "camera_id": "/dev/video0",
  "connection_type": "USB"
}
```

**Response:**
```json
{
  "success": true,
  "stream_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
  "websocket_url": "/stream/a1b2c3d4-e5f6-7890-abcd-ef1234567890",
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

### Initialize RTSP Stream

**Request:**
```http
POST http://localhost:5000/api/camera/init
Content-Type: application/json

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
  "stream_id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
  "websocket_url": "/stream/b2c3d4e5-f6a7-8901-bcde-f12345678901",
  "camera_info": {
    "camera_id": "192.168.1.100:554/stream1",
    "connection_type": "STREAM",
    "source": "rtsp://admin:password123@192.168.1.100:554/stream1",
    "width": 1920,
    "height": 1080,
    "fps": 25.0,
    "status": "initialized"
  }
}
```

## State Management

### CAMERA_PIPELINES Dictionary

```python
CAMERA_PIPELINES = {
    'stream_id_uuid': {
        'clients': set(),           # Set of WebSocket connections
        'lock': threading.Lock(),   # Thread-safe access
        'stop_event': Event(),      # Signal to stop thread
        'thread': Thread(),         # Capture thread
        'info': {                   # Camera information
            'camera_id': '/dev/video0',
            'connection_type': 'USB',
            'width': 640,
            'height': 480,
            'fps': 30.0
        },
        'status': 'active'          # active, stopped
    }
}
```

## Thread Management

### Capture Thread Lifecycle

```
1. Initialize Camera
   ↓
2. Create stop_event (threading.Event)
   ↓
3. Start capture_and_broadcast() in thread
   ↓
4. Thread runs in loop:
   - Capture frame from camera
   - Encode as JPEG
   - Broadcast to all connected clients
   - Check stop_event
   ↓
5. On stop request:
   - Set stop_event
   - Thread exits loop
   - Camera released
   - Resources cleaned up
```

## Security Model

### Development Mode
- HTTP connections
- No API authentication
- CORS enabled (all origins)
- Plain-text credentials

### Production Mode (Recommended)
```
┌─────────────────┐
│    Frontend     │
│    (HTTPS)      │
└────────┬────────┘
         │ TLS
         ↓
┌─────────────────┐
│    Laravel      │
│  (API Gateway)  │
│ • Authentication│
│ • Authorization │
└────────┬────────┘
         │ Internal Network
         │ Optional VPN
         ↓
┌─────────────────┐
│  Python Server  │
│   (WSS/HTTPS)   │
│ • API Key Auth  │
└────────┬────────┘
         │ Private Network
         ↓
┌─────────────────┐
│    Cameras      │
└─────────────────┘
```

## Performance Characteristics

### Latency
- **USB Cameras:** ~33ms (30 FPS)
- **RTSP Streams:** 100-500ms (network dependent)
- **WebSocket Overhead:** <10ms

### Bandwidth (per stream)
- **640x480 @ 30 FPS, Quality 80:** ~2-4 Mbps
- **1920x1080 @ 25 FPS, Quality 80:** ~8-15 Mbps

### Resource Usage (per stream)
- **CPU:** 5-15% (1 core)
- **Memory:** 50-100 MB
- **Network:** See bandwidth above

### Scalability
- **Single Server:** 5-10 concurrent streams (hardware dependent)
- **Load Balanced:** 50+ streams (multiple Python servers)

## Deployment Options

### Option 1: Single Server (Development)
```
┌────────────────────┐
│   Same Machine     │
│  ┌──────────────┐  │
│  │   Laravel    │  │
│  │   :80/:443   │  │
│  └──────────────┘  │
│  ┌──────────────┐  │
│  │   Python     │  │
│  │   :5000      │  │
│  └──────────────┘  │
└────────────────────┘
```

### Option 2: Separated Services (Production)
```
┌──────────────┐     ┌──────────────┐
│   Laravel    │────→│   Python     │
│   Server     │     │   Server     │
│   :80/:443   │     │   :5000      │
└──────────────┘     └──────┬───────┘
                            │
                     ┌──────┴───────┐
                     │   Cameras    │
                     └──────────────┘
```

### Option 3: Load Balanced (High Availability)
```
                 ┌──────────────┐
                 │ Load Balancer│
                 └──────┬───────┘
                        │
         ┌──────────────┼──────────────┐
         │              │              │
    ┌────▼────┐    ┌───▼─────┐   ┌───▼─────┐
    │ Python  │    │ Python  │   │ Python  │
    │ Server 1│    │ Server 2│   │ Server 3│
    └────┬────┘    └───┬─────┘   └───┬─────┘
         │             │             │
         └─────────────┴─────────────┘
                       │
                ┌──────┴───────┐
                │   Cameras    │
                └──────────────┘
```

## Error Handling

### Python Server
- Connection failures → Retry with exponential backoff
- Frame read errors → Attempt reconnection (max 5 retries)
- WebSocket errors → Disconnect client, continue streaming
- Thread crashes → Log error, mark stream as stopped

### Laravel Backend
- Python server down → Return error to frontend
- Timeout → 10 second timeout on API calls
- Database errors → Transaction rollback

### Frontend
- WebSocket disconnect → Automatic reconnection (3 attempts)
- Frame decode error → Skip frame, continue
- Network issues → Show error message, retry button

## Monitoring

### Key Metrics
- Active stream count
- Connected client count per stream
- Frame rate per stream
- CPU/Memory usage
- Network bandwidth
- Error rate

### Logging
- Camera initialization/stop events
- Client connections/disconnections
- Error events with stack traces
- Performance metrics (frame rate, latency)

## Future Enhancements

### Phase 1
- [ ] Recording to disk
- [ ] Snapshot capture
- [ ] Motion detection
- [ ] Admin dashboard

### Phase 2
- [ ] H.264 encoding
- [ ] WebRTC support
- [ ] Multi-resolution streaming
- [ ] AI object detection

### Phase 3
- [ ] Cloud storage integration
- [ ] Mobile app support
- [ ] Advanced analytics
- [ ] Kubernetes deployment

## Conclusion

This architecture provides:
- ✅ Scalable streaming solution
- ✅ Easy Laravel integration
- ✅ Support for multiple camera types
- ✅ Real-time video delivery
- ✅ Production-ready foundation

The modular design allows for incremental improvements and scaling as requirements grow.
