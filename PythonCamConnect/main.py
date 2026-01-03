import threading
import os
from flask import Flask, render_template, jsonify, request, send_file
from flask_sock import Sock
from flask_cors import CORS
from utils.camera_utils import capture_and_broadcast, initialize_camera, parse_camera_source, get_camera_info
from utils.detection_utils import DetectionPipeline, ThumbnailCapture, VideoRecorder
import uuid


app = Flask(__name__)
CORS(app)  # Enable CORS for Laravel integration
sock = Sock(app)  # Initialize Flask-Sock

# --- Global State ---
# This dict will hold all info for active pipelines
# Example: { 'camera_uuid': {'clients': {<ws1>, <ws2>}, 'lock': <Lock>, 'thread': <Thread>, 'stop_event': <Event>, 'info': {...}} }
CAMERA_PIPELINES = {}
# This list will be populated at startup
DETECTED_CAMERAS = []

# Storage path for recordings and thumbnails
STORAGE_PATH = os.path.join(os.path.dirname(__file__), 'storage')
os.makedirs(STORAGE_PATH, exist_ok=True)

# 1. Main HTML Page
@app.route('/')
def index():
    return render_template('index.html')

# 2. API Endpoint to list cameras (legacy)
@app.route('/api/cameras')
def api_cameras():
    return jsonify(DETECTED_CAMERAS)

# 3. API Endpoint to list all active camera streams
@app.route('/api/camera/list', methods=['GET'])
def list_active_cameras():
    """
    Returns a list of all active camera streams with their info.
    """
    active_cameras = []
    for camera_key, pipeline in CAMERA_PIPELINES.items():
        if pipeline.get('status') != 'stopped':
            camera_info = pipeline.get('info', {})
            detection_pipeline = pipeline.get('detection_pipeline')
            active_cameras.append({
                'stream_id': camera_key,
                'camera_id': camera_info.get('camera_id'),
                'camera_connection_path': camera_info.get('camera_connection_path'),
                'connection_type': camera_info.get('connection_type'),
                'width': camera_info.get('width'),
                'height': camera_info.get('height'),
                'fps': camera_info.get('fps'),
                'status': pipeline.get('status', 'active'),
                'websocket_url': f"/stream/{camera_key}",
                'detection_enabled': detection_pipeline is not None,
                'detection_type': pipeline.get('detection_type', 'NONE'),
                'detection_status': detection_pipeline.get_status() if detection_pipeline else None
            })

    return jsonify({
        'success': True,
        'cameras': active_cameras
    })

# 4. API Endpoint to initialize a camera
@app.route('/api/camera/init', methods=['POST'])
def init_camera():
    """
    Initialize a camera and start streaming.

    Request body (JSON):
    {
        "camera_id": "unique-id",
        "camera_connection_path": "/dev/video0" or "192.168.1.100:554/stream",
        "connection_type": "USB" or "STREAM",
        "username": "admin" (optional, for RTSP),
        "password": "password123" (optional, for RTSP),
        "detection_type": "NONE" | "MOTION" | "HUMAN" | "MOTION_HUMAN" (optional),
        "detection_sensitivity": 50 (optional, 1-100),
        "recording_duration": 180 (optional, seconds)
    }

    Returns:
    {
        "success": true,
        "stream_id": "unique-id",
        "websocket_url": "/stream/unique-id",
        "camera_info": {...}
    }
    """
    try:
        data = request.get_json()

        if not data:
            return jsonify({'success': False, 'error': 'No JSON data provided'}), 400

        camera_id = data.get('camera_id')
        camera_connection_path = data.get('camera_connection_path')
        connection_type = data.get('connection_type', 'USB')
        username = data.get('username')
        password = data.get('password')

        # Detection settings
        detection_type = data.get('detection_type', 'NONE')
        detection_sensitivity = data.get('detection_sensitivity', 50)
        recording_duration = data.get('recording_duration', 180)

        if not camera_id:
            return jsonify({'success': False, 'error': 'camera_id is required'}), 400

        if not camera_connection_path:
            return jsonify({'success': False, 'error': 'camera_connection_path is required'}), 400

        if connection_type.upper() not in ['USB', 'STREAM']:
            return jsonify({'success': False, 'error': 'connection_type must be USB or STREAM'}), 400

        if detection_type.upper() not in ['NONE', 'MOTION', 'HUMAN', 'MOTION_HUMAN']:
            return jsonify({'success': False, 'error': 'detection_type must be NONE, MOTION, HUMAN, or MOTION_HUMAN'}), 400

        # Check if camera is already initialized
        for cam_key, pipeline in CAMERA_PIPELINES.items():
            if pipeline.get('info', {}).get('camera_id') == camera_id and pipeline.get('status') != 'stopped':
                return jsonify({
                    'success': True,
                    'message': 'Camera already initialized',
                    'stream_id': cam_key,
                    'websocket_url': f"/stream/{cam_key}",
                    'camera_info': pipeline.get('info'),
                    'detection_enabled': pipeline.get('detection_pipeline') is not None
                })

        # Initialize camera
        camera_info = initialize_camera(camera_id, connection_type, camera_connection_path, username, password)

        if not camera_info:
            return jsonify({'success': False, 'error': 'Failed to initialize camera'}), 500

        # Generate unique stream ID
        stream_id = str(uuid.uuid4())

        # Create detection pipeline if detection is enabled
        detection_pipeline = None
        if detection_type.upper() != 'NONE':
            detection_pipeline = DetectionPipeline(
                camera_id=camera_id,
                storage_path=STORAGE_PATH,
                detection_type=detection_type.upper(),
                sensitivity=detection_sensitivity,
                recording_duration=recording_duration,
                fps=int(camera_info.get('frame_rate', 30)),
                resolution=(camera_info.get('width', 640), camera_info.get('height', 480))
            )

        # Create pipeline
        stop_event = threading.Event()
        CAMERA_PIPELINES[stream_id] = {
            'clients': set(),
            'lock': threading.Lock(),
            'stop_event': stop_event,
            'info': camera_info,
            'status': 'active',
            'detection_pipeline': detection_pipeline,
            'detection_type': detection_type.upper()
        }

        # Parse source for capture thread
        source = parse_camera_source(connection_type, camera_connection_path, username, password)

        # Start capture thread with detection support
        from utils.camera_utils import capture_and_broadcast_with_detection
        capture_thread = threading.Thread(
            target=capture_and_broadcast_with_detection,
            args=(stream_id, source, camera_info['width'], camera_info['height'], CAMERA_PIPELINES, stop_event, detection_pipeline),
            daemon=True
        )
        capture_thread.start()
        CAMERA_PIPELINES[stream_id]['thread'] = capture_thread

        print(f"[API] Camera initialized: {stream_id} -> {camera_connection_path} (ID: {camera_id}, Detection: {detection_type})")

        return jsonify({
            'success': True,
            'stream_id': stream_id,
            'websocket_url': f"/stream/{stream_id}",
            'camera_info': camera_info,
            'detection_enabled': detection_type.upper() != 'NONE',
            'detection_type': detection_type.upper()
        }), 201

    except Exception as e:
        print(f"[API] Error initializing camera: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

# 5. API Endpoint to stop a camera stream
@app.route('/api/camera/stop/<stream_id>', methods=['POST', 'DELETE'])
def stop_camera(stream_id):
    """
    Stop a camera stream and release resources.
    """
    if stream_id not in CAMERA_PIPELINES:
        return jsonify({'success': False, 'error': 'Stream not found'}), 404

    try:
        pipeline = CAMERA_PIPELINES[stream_id]
        recording_info = None

        # Stop detection pipeline if active
        if 'detection_pipeline' in pipeline and pipeline['detection_pipeline']:
            recording_info = pipeline['detection_pipeline'].stop()

        # Signal stop
        if 'stop_event' in pipeline:
            pipeline['stop_event'].set()

        # Close all client connections
        with pipeline['lock']:
            for client in list(pipeline['clients']):
                try:
                    client.close()
                except Exception:
                    pass
            pipeline['clients'].clear()

        # Wait for thread to finish (with timeout)
        if 'thread' in pipeline and pipeline['thread'].is_alive():
            pipeline['thread'].join(timeout=5)

        # Remove from pipelines
        del CAMERA_PIPELINES[stream_id]

        print(f"[API] Camera stopped: {stream_id}")

        return jsonify({
            'success': True,
            'message': 'Camera stopped',
            'final_recording': recording_info
        })

    except Exception as e:
        print(f"[API] Error stopping camera: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500

# 6. WebSocket Endpoint (Dynamic per camera with UUID)
@sock.route('/stream/<stream_id>')
def stream(ws, stream_id):
    """
    Handles new WebSocket connections for a specific camera stream.
    """
    if stream_id not in CAMERA_PIPELINES:
        print(f"Client tried to connect to non-existent stream {stream_id}.")
        ws.close()
        return

    print(f"[Stream {stream_id}]: New client connected.")
    pipeline = CAMERA_PIPELINES[stream_id]

    try:
        # Add client to the set for this camera
        with pipeline['lock']:
            pipeline['clients'].add(ws)

        # Wait for disconnect
        while True:
            message = ws.receive()
            if message is None:
                break
    except Exception as e:
        print(f"WebSocket error for client on stream {stream_id}: {e}")
    finally:
        # Client disconnected, remove from set
        print(f"[Stream {stream_id}]: Client disconnected.")
        with pipeline['lock']:
            pipeline['clients'].discard(ws)


# 7. API Endpoint to get camera info
@app.route('/api/camera/info', methods=['POST'])
def api_get_camera_info():
    """
    Get camera resolution and frame rate.
    """
    try:
        data = request.get_json()
        if not data:
            return jsonify({'success': False, 'error': 'No JSON data provided'}), 400

        camera_connection_path = data.get('camera_connection_path')
        if not camera_connection_path:
            return jsonify({'success': False, 'error': 'camera_connection_path is required'}), 400

        info = get_camera_info(camera_connection_path)
        if info:
            return jsonify({'success': True, 'data': info})
        else:
            return jsonify({'success': False, 'error': 'Failed to get camera info'}), 500
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


# 8. API Endpoint to update detection settings
@app.route('/api/camera/detection/<stream_id>', methods=['POST'])
def update_detection_settings(stream_id):
    """
    Update detection settings for a camera stream.

    Request body (JSON):
    {
        "detection_type": "NONE" | "MOTION" | "HUMAN" | "MOTION_HUMAN",
        "sensitivity": 50 (optional, 1-100),
        "confidence_threshold": 0.5 (optional, 0-1)
    }
    """
    if stream_id not in CAMERA_PIPELINES:
        return jsonify({'success': False, 'error': 'Stream not found'}), 404

    try:
        data = request.get_json()
        if not data:
            return jsonify({'success': False, 'error': 'No JSON data provided'}), 400

        pipeline = CAMERA_PIPELINES[stream_id]
        detection_type = data.get('detection_type', pipeline.get('detection_type', 'NONE')).upper()
        sensitivity = data.get('sensitivity', 50)
        confidence_threshold = data.get('confidence_threshold', 0.5)

        if detection_type not in ['NONE', 'MOTION', 'HUMAN', 'MOTION_HUMAN']:
            return jsonify({'success': False, 'error': 'Invalid detection_type'}), 400

        # Update or create detection pipeline
        if detection_type == 'NONE':
            # Disable detection
            if pipeline.get('detection_pipeline'):
                pipeline['detection_pipeline'].stop()
                pipeline['detection_pipeline'] = None
        else:
            camera_info = pipeline.get('info', {})
            if pipeline.get('detection_pipeline'):
                # Update existing pipeline
                pipeline['detection_pipeline'].update_settings(
                    detection_type=detection_type,
                    sensitivity=sensitivity,
                    confidence_threshold=confidence_threshold
                )
            else:
                # Create new detection pipeline
                pipeline['detection_pipeline'] = DetectionPipeline(
                    camera_id=camera_info.get('camera_id'),
                    storage_path=STORAGE_PATH,
                    detection_type=detection_type,
                    sensitivity=sensitivity,
                    confidence_threshold=confidence_threshold,
                    fps=int(camera_info.get('frame_rate', 30)),
                    resolution=(camera_info.get('width', 640), camera_info.get('height', 480))
                )

        pipeline['detection_type'] = detection_type

        return jsonify({
            'success': True,
            'message': 'Detection settings updated',
            'detection_type': detection_type,
            'detection_enabled': detection_type != 'NONE'
        })

    except Exception as e:
        print(f"[API] Error updating detection settings: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500


# 9. API Endpoint to get detection status
@app.route('/api/camera/detection/<stream_id>/status', methods=['GET'])
def get_detection_status(stream_id):
    """
    Get detection status for a camera stream.
    """
    if stream_id not in CAMERA_PIPELINES:
        return jsonify({'success': False, 'error': 'Stream not found'}), 404

    try:
        pipeline = CAMERA_PIPELINES[stream_id]
        detection_pipeline = pipeline.get('detection_pipeline')

        if detection_pipeline:
            status = detection_pipeline.get_status()
            return jsonify({'success': True, 'status': status})
        else:
            return jsonify({
                'success': True,
                'status': {
                    'detection_type': 'NONE',
                    'is_enabled': False,
                    'recording_status': {'is_recording': False}
                }
            })

    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


# 10. API Endpoint to capture thumbnail manually
@app.route('/api/camera/thumbnail/<stream_id>', methods=['POST'])
def capture_thumbnail(stream_id):
    """
    Capture a thumbnail from the current stream.
    """
    if stream_id not in CAMERA_PIPELINES:
        return jsonify({'success': False, 'error': 'Stream not found'}), 404

    try:
        pipeline = CAMERA_PIPELINES[stream_id]
        camera_info = pipeline.get('info', {})
        camera_id = camera_info.get('camera_id')

        # Get current frame from pipeline (we need to add this to the pipeline)
        current_frame = pipeline.get('current_frame')
        if current_frame is None:
            return jsonify({'success': False, 'error': 'No frame available'}), 500

        # Create thumbnail capture utility
        thumbnail_capture = ThumbnailCapture(STORAGE_PATH, camera_id)
        thumbnail_info = thumbnail_capture.capture(current_frame, is_detection=False)

        return jsonify({
            'success': True,
            'thumbnail': thumbnail_info
        })

    except Exception as e:
        print(f"[API] Error capturing thumbnail: {e}")
        return jsonify({'success': False, 'error': str(e)}), 500


# 11. API Endpoint to get latest thumbnail
@app.route('/api/camera/thumbnail/<stream_id>/latest', methods=['GET'])
def get_latest_thumbnail(stream_id):
    """
    Get the latest thumbnail for a camera.
    """
    if stream_id not in CAMERA_PIPELINES:
        return jsonify({'success': False, 'error': 'Stream not found'}), 404

    try:
        pipeline = CAMERA_PIPELINES[stream_id]
        camera_info = pipeline.get('info', {})
        camera_id = camera_info.get('camera_id')

        thumbnail_capture = ThumbnailCapture(STORAGE_PATH, camera_id)
        latest_path = thumbnail_capture.get_latest_thumbnail()

        if latest_path and os.path.exists(latest_path):
            return send_file(latest_path, mimetype='image/jpeg')
        else:
            return jsonify({'success': False, 'error': 'No thumbnail available'}), 404

    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


# 12. API Endpoint to list recordings for a camera
@app.route('/api/recordings/<camera_id>', methods=['GET'])
def list_recordings(camera_id):
    """
    List all recordings for a specific camera.
    """
    try:
        recordings_path = os.path.join(STORAGE_PATH, 'recordings', str(camera_id))

        if not os.path.exists(recordings_path):
            return jsonify({'success': True, 'recordings': []})

        recordings = []
        for filename in os.listdir(recordings_path):
            if filename.endswith('.mp4'):
                file_path = os.path.join(recordings_path, filename)
                file_stat = os.stat(file_path)
                recordings.append({
                    'filename': filename,
                    'file_path': file_path,
                    'file_size': file_stat.st_size,
                    'created_at': file_stat.st_mtime,
                    'download_url': f'/api/recording/{camera_id}/{filename}'
                })

        # Sort by creation time (newest first)
        recordings.sort(key=lambda x: x['created_at'], reverse=True)

        return jsonify({'success': True, 'recordings': recordings})

    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


# 13. API Endpoint to download a recording
@app.route('/api/recording/<camera_id>/<filename>', methods=['GET'])
def download_recording(camera_id, filename):
    """
    Download or stream a specific recording file.
    Use ?download=true to force download, otherwise streams inline.
    Videos are transcoded to H.264 for browser compatibility.
    """
    import subprocess
    import tempfile

    try:
        file_path = os.path.join(STORAGE_PATH, 'recordings', str(camera_id), filename)

        if not os.path.exists(file_path):
            return jsonify({'success': False, 'error': 'Recording not found'}), 404

        # Check if download is requested (original file)
        force_download = request.args.get('download', 'false').lower() == 'true'

        if force_download:
            # Return original file for download
            return send_file(
                file_path,
                mimetype='video/mp4',
                as_attachment=True,
                download_name=filename
            )

        # For streaming, transcode to H.264 if needed
        # Check if we have a cached H.264 version
        cache_dir = os.path.join(STORAGE_PATH, 'recordings', str(camera_id), '.cache')
        os.makedirs(cache_dir, exist_ok=True)
        cached_file = os.path.join(cache_dir, f"h264_{filename}")

        if not os.path.exists(cached_file):
            # Transcode to H.264 using FFmpeg
            try:
                result = subprocess.run([
                    'ffmpeg', '-y',
                    '-i', file_path,
                    '-c:v', 'libx264',
                    '-preset', 'fast',
                    '-crf', '23',
                    '-c:a', 'aac',
                    '-movflags', '+faststart',
                    cached_file
                ], capture_output=True, timeout=120)

                if result.returncode != 0:
                    print(f"FFmpeg error: {result.stderr.decode()}")
                    # Fall back to original file
                    return send_file(file_path, mimetype='video/mp4')
            except FileNotFoundError:
                print("FFmpeg not found, serving original file")
                return send_file(file_path, mimetype='video/mp4')
            except subprocess.TimeoutExpired:
                print("FFmpeg timeout, serving original file")
                return send_file(file_path, mimetype='video/mp4')

        return send_file(
            cached_file,
            mimetype='video/mp4',
            as_attachment=False
        )

    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


# 13b. API Endpoint to delete a recording
@app.route('/api/recording/<camera_id>/<filename>', methods=['DELETE'])
def delete_recording(camera_id, filename):
    """
    Delete a specific recording file.
    """
    try:
        file_path = os.path.join(STORAGE_PATH, 'recordings', str(camera_id), filename)

        if not os.path.exists(file_path):
            return jsonify({'success': False, 'error': 'Recording not found'}), 404

        os.remove(file_path)
        return jsonify({'success': True, 'message': 'Recording deleted successfully'})

    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


# 14. API Endpoint to list thumbnails for a camera
@app.route('/api/thumbnails/<camera_id>', methods=['GET'])
def list_thumbnails(camera_id):
    """
    List all thumbnails for a specific camera.
    """
    try:
        thumbnails_path = os.path.join(STORAGE_PATH, 'thumbnails', str(camera_id))

        if not os.path.exists(thumbnails_path):
            return jsonify({'success': True, 'thumbnails': []})

        thumbnails = []
        for filename in os.listdir(thumbnails_path):
            if filename.endswith('.jpg'):
                file_path = os.path.join(thumbnails_path, filename)
                file_stat = os.stat(file_path)
                thumbnails.append({
                    'filename': filename,
                    'file_size': file_stat.st_size,
                    'created_at': file_stat.st_mtime,
                    'url': f'/api/thumbnail/{camera_id}/{filename}',
                    'is_detection': 'detection' in filename.lower()
                })

        # Sort by creation time (newest first)
        thumbnails.sort(key=lambda x: x['created_at'], reverse=True)

        return jsonify({'success': True, 'thumbnails': thumbnails})

    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


# 15. API Endpoint to get a specific thumbnail
@app.route('/api/thumbnail/<camera_id>/<filename>', methods=['GET'])
def get_thumbnail(camera_id, filename):
    """
    Get a specific thumbnail image.
    """
    try:
        file_path = os.path.join(STORAGE_PATH, 'thumbnails', str(camera_id), filename)

        if not os.path.exists(file_path):
            return jsonify({'success': False, 'error': 'Thumbnail not found'}), 404

        return send_file(file_path, mimetype='image/jpeg')

    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


if __name__ == '__main__':
    print("\n=== Camera Stream Server ===")
    print("API Endpoints:")
    print("  POST   /api/camera/init     - Initialize a camera")
    print("  GET    /api/camera/list     - List active cameras")
    print("  DELETE /api/camera/stop/<stream_id> - Stop a camera")
    print("  POST   /api/camera/detection/<stream_id> - Update detection settings")
    print("  GET    /api/camera/detection/<stream_id>/status - Get detection status")
    print("  POST   /api/camera/thumbnail/<stream_id> - Capture thumbnail")
    print("  GET    /api/camera/thumbnail/<stream_id>/latest - Get latest thumbnail")
    print("  GET    /api/recordings/<camera_id> - List recordings")
    print("  GET    /api/recording/<filename> - Download recording")
    print("  WS     /stream/<stream_id> - WebSocket stream")
    print(f"\nStorage Path: {STORAGE_PATH}")
    print("\nServer starting on http://0.0.0.0:5000")
    print("============================\n")

    try:
        # Start the Flask server
        app.run(debug=False, host='0.0.0.0', port=5000, threaded=True)

    except (KeyboardInterrupt, SystemExit):
        print("\nServer shutting down...")
    finally:
        # Clean up all active streams
        print("Cleaning up active streams...")
        for stream_id, pipeline in list(CAMERA_PIPELINES.items()):
            if 'detection_pipeline' in pipeline and pipeline['detection_pipeline']:
                pipeline['detection_pipeline'].stop()
            if 'stop_event' in pipeline:
                pipeline['stop_event'].set()
            if 'thread' in pipeline and pipeline['thread'].is_alive():
                pipeline['thread'].join(timeout=2)
        print("Goodbye.")



