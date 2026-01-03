# Python Camera Stream Server

A Flask-based camera streaming server that supports both USB cameras and RTSP streams with WebSocket delivery. Designed for integration with Laravel web applications.

## Features

- **Multiple Connection Types:**
  - USB cameras (`/dev/video0`, `/dev/video1`, etc.)
  - RTSP streams with authentication support
  
- **Dynamic Camera Management:**
  - Initialize cameras on-demand via REST API
  - Stop/start streams programmatically
  - Multiple concurrent camera streams

- **WebSocket Streaming:**
  - Real-time JPEG frame delivery
  - Low latency streaming
  - Multiple clients per camera

- **Laravel Integration:**
  - CORS enabled for cross-origin requests
  - RESTful API design
  - Easy integration with Laravel backend

## Installation

### Prerequisites

- Python 3.7+
- pip
- OpenCV dependencies (for USB cameras)

### Setup

1. **Install dependencies:**

```bash
pip install -r requirements.txt
```

2. **Run the server:**

```bash
python main.py
```

The server will start on `http://0.0.0.0:5000`

## Quick Start

### Initialize a USB Camera

```bash
curl -X POST http://localhost:5000/api/camera/init \
  -H "Content-Type: application/json" \
  -d '{
    "camera_id": "/dev/video0",
    "connection_type": "USB"
  }'
```

### Initialize an RTSP Stream

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

### Connect to WebSocket Stream

```javascript
const ws = new WebSocket('ws://localhost:5000/stream/{stream_id}');
ws.binaryType = 'arraybuffer';

ws.onmessage = (event) => {
    const blob = new Blob([event.data], { type: 'image/jpeg' });
    // Display the frame in your application
};
```

## API Endpoints

### `POST /api/camera/init`
Initialize a new camera stream

**Request Body:**
```json
{
  "camera_id": "/dev/video0 or RTSP URL",
  "connection_type": "USB or STREAM",
  "username": "optional for RTSP",
  "password": "optional for RTSP"
}
```

**Response:**
```json
{
  "success": true,
  "stream_id": "uuid",
  "websocket_url": "/stream/uuid",
  "camera_info": {...}
}
```

### `GET /api/camera/list`
List all active camera streams

### `DELETE /api/camera/stop/{stream_id}`
Stop a camera stream

### `WS /stream/{stream_id}`
WebSocket endpoint for receiving camera frames

## Documentation

- **[Laravel Integration Guide](LARAVEL_INTEGRATION.md)** - Complete guide for integrating with Laravel
- **[API Examples](API_EXAMPLES.md)** - Detailed API usage examples and testing

## Architecture

```
┌─────────────────┐
│  Laravel Backend │
│   (Database)    │
└────────┬────────┘
         │ HTTP POST
         ↓
┌─────────────────┐
│  Python Server  │
│  (Flask + CV2)  │
└────────┬────────┘
         │ WebSocket
         ↓
┌─────────────────┐
│  Frontend       │
│  (Browser/JS)   │
└─────────────────┘
```

## Project Structure

```
PythonCamConnect/
├── main.py                 # Flask application with API endpoints
├── utils/
│   ├── camera_utils.py     # Camera handling and streaming logic
│   └── __init__.py
├── templates/
│   └── index.html          # Demo HTML page
├── static/
│   ├── style.css
│   ├── jsmpeg.min.js
│   └── websocket.js
├── requirements.txt        # Python dependencies
├── README.md              # This file
├── LARAVEL_INTEGRATION.md # Laravel integration guide
└── API_EXAMPLES.md        # API usage examples
```

## Usage with Laravel

1. **Store camera info in Laravel database**
2. **Call Python API to initialize camera**
3. **Get WebSocket URL from response**
4. **Pass WebSocket URL to frontend**
5. **Frontend connects and displays stream**

See [LARAVEL_INTEGRATION.md](LARAVEL_INTEGRATION.md) for detailed implementation.

## Configuration

### Environment Variables

You can configure the server using environment variables:

- `FLASK_HOST` - Server host (default: 0.0.0.0)
- `FLASK_PORT` - Server port (default: 5000)
- `JPEG_QUALITY` - JPEG compression quality 1-100 (default: 80)

### Camera Settings

Camera settings are configurable per camera during initialization. The server automatically detects resolution and frame rate from the camera.

## Troubleshooting

### USB Camera Not Found

- Check camera is connected: `ls /dev/video*`
- Verify permissions: `sudo usermod -a -G video $USER`
- Test with: `v4l2-ctl --list-devices`

### RTSP Connection Failed

- Verify RTSP URL is correct
- Check network connectivity
- Confirm username/password
- Test with VLC or ffplay

### WebSocket Connection Issues

- Ensure CORS is enabled
- Check firewall settings
- Verify WebSocket URL is correct
- Check browser console for errors

## Performance Tips

1. **Adjust JPEG quality** - Lower quality = less bandwidth
2. **Limit concurrent streams** - Each stream uses CPU/memory
3. **Use hardware acceleration** - OpenCV can use GPU if available
4. **Network optimization** - Place Python server close to cameras

## Security Considerations

- **Production:** Use HTTPS/WSS instead of HTTP/WS
- **Authentication:** Add API authentication for production
- **Credentials:** Store camera passwords encrypted in Laravel
- **Network:** Use VPN or private network for camera access

## License

MIT License - Feel free to use in your projects

## Support

For issues and questions, please check:
- [API Examples](API_EXAMPLES.md)
- [Laravel Integration Guide](LARAVEL_INTEGRATION.md)
