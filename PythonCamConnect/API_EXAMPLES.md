# API Examples

## Quick Start

### 1. Initialize USB Camera

```bash
curl -X POST http://localhost:5000/api/camera/init \
  -H "Content-Type: application/json" \
  -d '{
    "camera_id": "/dev/video0",
    "connection_type": "USB"
  }'
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

### 2. Initialize RTSP Camera with Authentication

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

### 3. List Active Cameras

```bash
curl http://localhost:5000/api/camera/list
```

**Response:**
```json
{
  "success": true,
  "cameras": [
    {
      "stream_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "camera_id": "/dev/video0",
      "connection_type": "USB",
      "width": 640,
      "height": 480,
      "fps": 30.0,
      "status": "active",
      "websocket_url": "/stream/a1b2c3d4-e5f6-7890-abcd-ef1234567890"
    },
    {
      "stream_id": "b2c3d4e5-f6a7-8901-bcde-f12345678901",
      "camera_id": "192.168.1.100:554/stream1",
      "connection_type": "STREAM",
      "width": 1920,
      "height": 1080,
      "fps": 25.0,
      "status": "active",
      "websocket_url": "/stream/b2c3d4e5-f6a7-8901-bcde-f12345678901"
    }
  ]
}
```

### 4. Stop Camera Stream

```bash
curl -X DELETE http://localhost:5000/api/camera/stop/a1b2c3d4-e5f6-7890-abcd-ef1234567890
```

**Response:**
```json
{
  "success": true,
  "message": "Camera stopped"
}
```

## WebSocket Connection

### JavaScript Example

```javascript
// Connect to WebSocket stream
const streamId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
const ws = new WebSocket(`ws://localhost:5000/stream/${streamId}`);
ws.binaryType = 'arraybuffer';

const canvas = document.getElementById('canvas');
const ctx = canvas.getContext('2d');

ws.onopen = () => {
    console.log('Connected to stream');
};

ws.onmessage = (event) => {
    // Receive JPEG frame
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
};

ws.onclose = () => {
    console.log('Connection closed');
};
```

### Python Example (Client)

```python
import websocket
import cv2
import numpy as np

def on_message(ws, message):
    # Convert bytes to numpy array
    nparr = np.frombuffer(message, np.uint8)
    # Decode image
    img = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
    # Display
    cv2.imshow('Stream', img)
    cv2.waitKey(1)

def on_error(ws, error):
    print(f"Error: {error}")

def on_close(ws, close_status_code, close_msg):
    print("Connection closed")

def on_open(ws):
    print("Connected to stream")

if __name__ == "__main__":
    stream_id = "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
    ws_url = f"ws://localhost:5000/stream/{stream_id}"
    
    ws = websocket.WebSocketApp(ws_url,
                                on_open=on_open,
                                on_message=on_message,
                                on_error=on_error,
                                on_close=on_close)
    
    ws.run_forever()
```

## Common Use Cases

### Multiple USB Cameras

```bash
# Camera 1
curl -X POST http://localhost:5000/api/camera/init \
  -H "Content-Type: application/json" \
  -d '{
    "camera_id": "/dev/video0",
    "connection_type": "USB"
  }'

# Camera 2
curl -X POST http://localhost:5000/api/camera/init \
  -H "Content-Type: application/json" \
  -d '{
    "camera_id": "/dev/video1",
    "connection_type": "USB"
  }'
```

### RTSP Camera Without Authentication

```bash
curl -X POST http://localhost:5000/api/camera/init \
  -H "Content-Type: application/json" \
  -d '{
    "camera_id": "rtsp://192.168.1.100:554/stream1",
    "connection_type": "STREAM"
  }'
```

### Full RTSP URL with Authentication

```bash
curl -X POST http://localhost:5000/api/camera/init \
  -H "Content-Type: application/json" \
  -d '{
    "camera_id": "rtsp://admin:pass123@192.168.1.100:554/stream1",
    "connection_type": "STREAM"
  }'
```

## Error Responses

### Camera Not Found

```json
{
  "success": false,
  "error": "Failed to initialize camera"
}
```

### Missing Required Field

```json
{
  "success": false,
  "error": "camera_id is required"
}
```

### Invalid Connection Type

```json
{
  "success": false,
  "error": "connection_type must be USB or STREAM"
}
```

### Stream Not Found

```json
{
  "success": false,
  "error": "Stream not found"
}
```

## Testing with Postman

1. **Initialize Camera**
   - Method: POST
   - URL: `http://localhost:5000/api/camera/init`
   - Headers: `Content-Type: application/json`
   - Body (raw JSON):
     ```json
     {
       "camera_id": "/dev/video0",
       "connection_type": "USB"
     }
     ```

2. **List Cameras**
   - Method: GET
   - URL: `http://localhost:5000/api/camera/list`

3. **Stop Camera**
   - Method: DELETE
   - URL: `http://localhost:5000/api/camera/stop/{stream_id}`

## Browser Testing

Create a simple HTML file:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Camera Stream Test</title>
</head>
<body>
    <h1>Camera Stream</h1>
    <button onclick="initCamera()">Initialize Camera</button>
    <button onclick="stopCamera()">Stop Camera</button>
    <br><br>
    <canvas id="canvas" width="640" height="480" style="border: 1px solid black;"></canvas>
    
    <script>
        let ws = null;
        let streamId = null;

        async function initCamera() {
            const response = await fetch('http://localhost:5000/api/camera/init', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    camera_id: '/dev/video0',
                    connection_type: 'USB'
                })
            });
            
            const data = await response.json();
            if (data.success) {
                streamId = data.stream_id;
                console.log('Camera initialized:', data);
                connectWebSocket(streamId);
            }
        }

        function connectWebSocket(streamId) {
            ws = new WebSocket(`ws://localhost:5000/stream/${streamId}`);
            ws.binaryType = 'arraybuffer';
            
            const canvas = document.getElementById('canvas');
            const ctx = canvas.getContext('2d');
            
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
        }

        async function stopCamera() {
            if (ws) ws.close();
            if (streamId) {
                await fetch(`http://localhost:5000/api/camera/stop/${streamId}`, {
                    method: 'DELETE'
                });
                streamId = null;
            }
        }
    </script>
</body>
</html>
```
