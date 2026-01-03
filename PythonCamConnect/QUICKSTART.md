# Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Install Dependencies

```bash
pip install -r requirements.txt
```

### Step 2: Start the Server

```bash
python main.py
```

You should see:
```
=== Camera Stream Server ===
API Endpoints:
  POST   /api/camera/init     - Initialize a camera
  GET    /api/camera/list     - List active cameras
  DELETE /api/camera/stop/<stream_id> - Stop a camera
  WS     /stream/<stream_id> - WebSocket stream

Server starting on http://0.0.0.0:5000
============================
```

### Step 3: Test with Web Interface

Open `test.html` in your browser and:
1. Select "USB Camera" or "RTSP Stream"
2. Enter camera ID (e.g., `/dev/video0`)
3. Click "Initialize Camera"
4. Watch the live stream!

### Step 4: Test with cURL

**USB Camera:**
```bash
curl -X POST http://localhost:5000/api/camera/init \
  -H "Content-Type: application/json" \
  -d '{"camera_id": "/dev/video0", "connection_type": "USB"}'
```

**RTSP Stream:**
```bash
curl -X POST http://localhost:5000/api/camera/init \
  -H "Content-Type: application/json" \
  -d '{
    "camera_id": "192.168.1.100:554/stream1",
    "connection_type": "STREAM",
    "username": "admin",
    "password": "pass123"
  }'
```

### Step 5: Integrate with Laravel

See [`LARAVEL_INTEGRATION.md`](LARAVEL_INTEGRATION.md) for complete Laravel backend implementation.

## 📖 Quick Reference

### Initialize Camera
```bash
POST /api/camera/init
```

### List Active Cameras
```bash
GET /api/camera/list
```

### Stop Camera
```bash
DELETE /api/camera/stop/{stream_id}
```

### WebSocket Stream
```javascript
const ws = new WebSocket('ws://localhost:5000/stream/{stream_id}');
```

## 🔧 Configuration

Edit `.env` (copy from `.env.example`):
```bash
FLASK_PORT=5000
JPEG_QUALITY=80
```

## 📚 Full Documentation

- **[README.md](README.md)** - Complete documentation
- **[LARAVEL_INTEGRATION.md](LARAVEL_INTEGRATION.md)** - Laravel integration
- **[API_EXAMPLES.md](API_EXAMPLES.md)** - API examples
- **[SUMMARY.md](SUMMARY.md)** - Implementation summary

## ❓ Troubleshooting

### Camera Not Found
```bash
# Check available cameras
ls /dev/video*

# Add user to video group
sudo usermod -a -G video $USER
```

### Module Not Found
```bash
# Reinstall dependencies
pip install --upgrade -r requirements.txt
```

### Port Already in Use
```bash
# Change port in .env
FLASK_PORT=5001
```

## 🎯 Next Steps

1. ✅ Test with your cameras
2. ✅ Review Laravel integration guide
3. ✅ Customize for your needs
4. ✅ Deploy to production

Happy streaming! 🎥
