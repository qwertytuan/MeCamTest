import cv2
import time
import json

def parse_camera_source(connection_type, camera_connection_path, username=None, password=None):
    """
    Parse camera source based on connection type.

    Args:
        connection_type: 'USB' or 'STREAM'
        camera_connection_path: For USB: device path like '/dev/video0' or index like '0'
                               For STREAM: RTSP URL
        username: Username for RTSP authentication (optional)
        password: Password for RTSP authentication (optional)

    Returns:
        Camera source string or index for OpenCV
    """
    if connection_type.upper() == 'USB':
        # Handle both /dev/video0 format and numeric index
        if isinstance(camera_connection_path, str) and camera_connection_path.startswith('/dev/video'):
            # Extract number from /dev/video0, /dev/video1, etc.
            try:
                index = int(camera_connection_path.split('/dev/video')[1])
                return index
            except (ValueError, IndexError):
                return camera_connection_path
        else:
            # Try to convert to int if it's a numeric string
            try:
                return int(camera_connection_path)
            except (ValueError, TypeError):
                return camera_connection_path

    elif connection_type.upper() == 'STREAM':
        # Handle network streams (RTSP, RTMP, HTTP, etc.)
        protocols = ('rtsp://', 'rtmp://', 'http://', 'https://', 'udp://', 'tcp://')
        has_protocol = camera_connection_path.lower().startswith(protocols)

        if username and password:
            # Insert credentials into URL if possible
            # Format: protocol://username:password@host:port/path
            if has_protocol:
                if '@' in camera_connection_path:
                    return camera_connection_path

                # Find the matching protocol to inject credentials after it
                for p in protocols:
                    if camera_connection_path.lower().startswith(p):
                        return f"{camera_connection_path[:len(p)]}{username}:{password}@{camera_connection_path[len(p):]}"
            else:
                # Assume it needs rtsp:// prefix if no protocol is present
                return f"rtsp://{username}:{password}@{camera_connection_path}"
        else:
            # No authentication
            if has_protocol:
                return camera_connection_path

            # Default to RTSP if no protocol specified
            return f"rtsp://{camera_connection_path}"

    return camera_connection_path


def initialize_camera(camera_id, connection_type, camera_connection_path, username=None, password=None):
    """
    Initialize a camera based on connection type.

    Args:
        camera_id: Unique identifier for the camera
        connection_type: 'USB' or 'STREAM'
        camera_connection_path: Camera path (device path or RTSP URL)
        username: Username for RTSP authentication (optional)
        password: Password for RTSP authentication (optional)

    Returns:
        dict with camera info and status, or None if failed
    """
    print(f"[Initialize]: ID={camera_id}, Type={connection_type}, Path={camera_connection_path}")

    try:
        source = parse_camera_source(connection_type, camera_connection_path, username, password)
        cap = cv2.VideoCapture(source)

        if not cap.isOpened():
            print(f"[Initialize]: Failed to open camera {camera_connection_path}")
            return None

        # Get properties
        width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
        height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
        fps = cap.get(cv2.CAP_PROP_FPS)

        # Normalize FPS (some backends return 0 or None)
        if not fps or fps <= 0:
            fps = 30.0

        # Release the test capture
        cap.release()
        res = str(width) + str(height)
        camera_info = {
            "camera_id": camera_id,
            "connection_type": connection_type.upper(),
            "camera_connection_path": camera_connection_path,
            "source": str(source),
            "width" : int(width),
            "height": int(height),
            "frame_rate": float(fps),
            "status": "initialized"
        }

        print(f"[Initialize]: Success - {width}x{height} @ {fps} FPS")
        return camera_info

    except Exception as e:
        print(f"[Initialize]: Error - {str(e)}")
        return None


def get_camera_info(camera_connection_path):
    """
    Get resolution and frame rate of a camera.

    Args:
        camera_connection_path: Camera path (device path like '/dev/video0', index '0', or RTSP URL)

    Returns:
        dict with resolution (width, height) and frame_rate, or None if failed
    """
    # Determine source for OpenCV
    source = camera_connection_path
    if isinstance(source, str):
        if source.isdigit():
            source = int(source)
        elif source.startswith('/dev/video'):
            try:
                source = int(source.split('/dev/video')[1])
            except (ValueError, IndexError):
                pass

    try:
        cap = cv2.VideoCapture(source)
        if not cap.isOpened():
            return None

        width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
        height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
        fps = cap.get(cv2.CAP_PROP_FPS)

        cap.release()

        return {
            "resolution": f"{width}x{height}",
            "frame_rate": fps
        }
    except Exception:
        return None


def detect_cameras(max_cameras=10):
    """
    Tries to open video captures for indices 0 to max_cameras-1.
    Returns a list of dicts for each camera that successfully opens.
    """
    detected = []
    print("--- Detecting Cameras ---")
    for i in range(max_cameras):
        cap = cv2.VideoCapture(i)
        if cap.isOpened():
            # Get properties
            width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
            height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
            fps = cap.get(cv2.CAP_PROP_FPS)
            # Normalize FPS (some backends return 0 or None)
            if not fps or fps <= 0:
                fps = 30.0
            # Log discovered camera info
            print(f"  [+] Camera {i}: Found ({width}x{height} @ {fps} FPS)")
            # Append camera metadata
            detected.append({
                "id": i,
                "width": int(width),
                "height": int(height),
                "fps": float(fps)
            })
            # Release the capture handle after reading properties
            cap.release()
        else:
            print(f"  [-] Camera {i}: Not found.")
    print("---------------------------\n")
    return detected


def capture_and_broadcast(camera_key, source, width, height, camera_pipelines, stop_event):
    """
    Captures frames from a specific camera and broadcasts JPEG encoded frames to WebSocket clients.

    Args:
        camera_key: Unique key for this camera in CAMERA_PIPELINES
        source: Camera source (index for USB, URL for RTSP)
        width: Desired frame width
        height: Desired frame height
        camera_pipelines: Dict containing all camera pipeline info
        stop_event: Threading event to signal when to stop
    """
    print(f"[Camera {camera_key}]: Starting JPEG capture thread...")
    cap = cv2.VideoCapture(source)

    # Set resolution for USB cameras (may not work for RTSP)
    if isinstance(source, int):
        cap.set(cv2.CAP_PROP_FRAME_WIDTH, width)
        cap.set(cv2.CAP_PROP_FRAME_HEIGHT, height)

    # Set buffer size to reduce latency
    cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)

    # Get actual FPS
    fps = cap.get(cv2.CAP_PROP_FPS)
    if not fps or fps <= 0:
        fps = 30.0

    retry_count = 0
    max_retries = 5
    frame_count = 0

    # JPEG encoding parameters for quality/size balance
    jpeg_quality = 85
    encode_params = [cv2.IMWRITE_JPEG_QUALITY, jpeg_quality]

    try:
        while not stop_event.is_set():
            ret, frame = cap.read()
            if not ret:
                retry_count += 1
                print(f"[Camera {camera_key}]: Error: Failed to capture frame (retry {retry_count}/{max_retries})")

                if retry_count >= max_retries:
                    print(f"[Camera {camera_key}]: Max retries reached. Stopping.")
                    break

                time.sleep(1)
                # Try to reconnect
                cap.release()
                cap = cv2.VideoCapture(source)
                continue

            # Reset retry count on successful frame
            retry_count = 0
            frame_count += 1

            # Resize frame if needed
            if frame.shape[1] != width or frame.shape[0] != height:
                frame = cv2.resize(frame, (width, height))

            # Encode frame as JPEG
            ret, jpeg_frame = cv2.imencode('.jpg', frame, encode_params)
            if not ret:
                print(f"[Camera {camera_key}]: Failed to encode frame as JPEG")
                continue

            jpeg_bytes = jpeg_frame.tobytes()

            # Broadcast JPEG frame to all clients
            if camera_key in camera_pipelines:
                pipeline = camera_pipelines[camera_key]
                with pipeline['lock']:
                    for client in list(pipeline['clients']):
                        try:
                            client.send(jpeg_bytes)
                        except Exception as e:
                            print(f"[Camera {camera_key}]: Error sending to client: {e}")
                            pipeline['clients'].discard(client)

            # Log every 100 frames
            if frame_count % 100 == 0:
                print(f"[Camera {camera_key}]: Encoded {frame_count} frames (JPEG)")

            # Small delay to control frame rate (dynamic based on FPS)
            time.sleep(1.0 / fps)

    except Exception as e:
        print(f"[Camera {camera_key}]: Capture error: {e}")
    finally:
        print(f"[Camera {camera_key}]: Stopping JPEG capture thread.")
        cap.release()

        # Mark as stopped
        if camera_key in camera_pipelines:
            camera_pipelines[camera_key]['status'] = 'stopped'


def detect_motion(camera_connection_path, connection_type='USB', username=None, password=None, min_area=500, timeout=10):
    """
    Detects motion from a camera feed and returns the raw frame where motion was detected.

    Args:
        camera_connection_path: Camera path or index.
        connection_type: 'USB' or 'STREAM'.
        username: (Optional) RTSP username.
        password: (Optional) RTSP password.
        min_area: Minimum contour area to be considered motion.
        timeout: Max time in seconds to wait for motion.

    Returns:
        The raw frame (numpy array) if motion detected, else None.
    """
    source = parse_camera_source(connection_type, camera_connection_path, username, password)
    cap = cv2.VideoCapture(source)

    if not cap.isOpened():
        print(f"[MotionDetect]: Failed to open camera {source}")
        return None

    # Read the first frame to establish a baseline
    ret, baseline_frame = cap.read()
    if not ret:
        print("[MotionDetect]: Failed to read initial frame")
        cap.release()
        return None

    baseline_gray = cv2.cvtColor(baseline_frame, cv2.COLOR_BGR2GRAY)
    baseline_gray = cv2.GaussianBlur(baseline_gray, (21, 21), 0)

    start_time = time.time()
    detected_frame = None

    print(f"[MotionDetect]: Monitoring for motion (timeout={timeout}s)...")

    while (time.time() - start_time) < timeout:
        ret, frame = cap.read()
        if not ret:
            break

        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        gray = cv2.GaussianBlur(gray, (21, 21), 0)

        # Compute difference between current frame and baseline
        delta = cv2.absdiff(baseline_gray, gray)
        thresh = cv2.threshold(delta, 25, 255, cv2.THRESH_BINARY)[1]
        thresh = cv2.dilate(thresh, None, iterations=2)

        # Find contours
        contours, _ = cv2.findContours(thresh.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

        motion_found = False
        for c in contours:
            if cv2.contourArea(c) < min_area:
                continue
            motion_found = True
            break

        if motion_found:
            detected_frame = frame
            print("[MotionDetect]: Motion detected!")
            break

    cap.release()
    return detected_frame


def capture_and_broadcast_with_detection(camera_key, source, width, height, camera_pipelines, stop_event, detection_pipeline=None):
    """
    Captures frames from a specific camera, runs detection, and broadcasts JPEG encoded frames to WebSocket clients.

    Args:
        camera_key: Unique key for this camera in CAMERA_PIPELINES
        source: Camera source (index for USB, URL for RTSP)
        width: Desired frame width
        height: Desired frame height
        camera_pipelines: Dict containing all camera pipeline info
        stop_event: Threading event to signal when to stop
        detection_pipeline: Optional DetectionPipeline instance for motion/human detection
    """
    print(f"[Camera {camera_key}]: Starting capture thread with detection support...")
    cap = cv2.VideoCapture(source)

    # Set resolution for USB cameras (may not work for RTSP)
    if isinstance(source, int):
        cap.set(cv2.CAP_PROP_FRAME_WIDTH, width)
        cap.set(cv2.CAP_PROP_FRAME_HEIGHT, height)

    # Set buffer size to reduce latency
    cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)

    # Get actual FPS
    fps = cap.get(cv2.CAP_PROP_FPS)
    if not fps or fps <= 0:
        fps = 30.0

    retry_count = 0
    max_retries = 5
    frame_count = 0
    detection_frame_interval = 5  # Run detection every N frames for performance

    # JPEG encoding parameters for quality/size balance
    jpeg_quality = 85
    encode_params = [cv2.IMWRITE_JPEG_QUALITY, jpeg_quality]

    try:
        while not stop_event.is_set():
            ret, frame = cap.read()
            if not ret:
                retry_count += 1
                print(f"[Camera {camera_key}]: Error: Failed to capture frame (retry {retry_count}/{max_retries})")

                if retry_count >= max_retries:
                    print(f"[Camera {camera_key}]: Max retries reached. Stopping.")
                    break

                time.sleep(1)
                # Try to reconnect
                cap.release()
                cap = cv2.VideoCapture(source)
                continue

            # Reset retry count on successful frame
            retry_count = 0
            frame_count += 1

            # Resize frame if needed
            if frame.shape[1] != width or frame.shape[0] != height:
                frame = cv2.resize(frame, (width, height))

            # Store current frame for thumbnail capture
            if camera_key in camera_pipelines:
                camera_pipelines[camera_key]['current_frame'] = frame.copy()

            # Run detection pipeline if enabled (every N frames for performance)
            detection_result = None
            display_frame = frame.copy()

            if detection_pipeline and frame_count % detection_frame_interval == 0:
                try:
                    detection_result = detection_pipeline.process_frame(frame)

                    # Store latest detection result in pipeline for API access
                    if camera_key in camera_pipelines:
                        camera_pipelines[camera_key]['latest_detection'] = detection_result

                    if detection_result['detection_triggered']:
                        print(f"[Camera {camera_key}]: Detection triggered - {detection_result['detection_type']}")

                        if detection_result['recording_started']:
                            print(f"[Camera {camera_key}]: Recording started")

                        if detection_result['thumbnail_captured']:
                            print(f"[Camera {camera_key}]: Detection thumbnail saved")

                    # Draw detection boxes on the display frame
                    if detection_result.get('detection_details'):
                        details = detection_result['detection_details']

                        # Draw human detection boxes
                        if details.get('human_detections'):
                            for det in details['human_detections']:
                                bbox = det.get('bbox', [])
                                if len(bbox) == 4:
                                    x1, y1, x2, y2 = bbox
                                    conf = det.get('confidence', 0)
                                    # Draw red box for human detection
                                    cv2.rectangle(display_frame, (x1, y1), (x2, y2), (0, 0, 255), 2)
                                    label = f"Person {conf:.0%}"
                                    cv2.putText(display_frame, label, (x1, y1 - 10),
                                               cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 255), 2)

                        # Draw motion regions
                        if details.get('motion_regions') and details.get('motion_detected'):
                            for contour in details['motion_regions']:
                                x, y, w, h = cv2.boundingRect(contour)
                                # Draw green box for motion
                                cv2.rectangle(display_frame, (x, y), (x + w, y + h), (0, 255, 0), 2)

                except Exception as e:
                    print(f"[Camera {camera_key}]: Detection error: {e}")

            # Encode frame as JPEG (use display_frame with boxes if detection is on)
            ret, jpeg_frame = cv2.imencode('.jpg', display_frame, encode_params)
            if not ret:
                print(f"[Camera {camera_key}]: Failed to encode frame as JPEG")
                continue

            jpeg_bytes = jpeg_frame.tobytes()

            # Prepare detection metadata as JSON
            detection_meta = None
            if detection_result and detection_result.get('detection_triggered'):
                detection_meta = {
                    'type': 'detection',
                    'detection_type': detection_result.get('detection_type'),
                    'human_count': len(detection_result.get('detection_details', {}).get('human_detections', [])),
                    'motion_detected': detection_result.get('detection_details', {}).get('motion_detected', False),
                    'recording_active': detection_pipeline.recorder.is_recording if detection_pipeline else False,
                    'timestamp': time.time()
                }

            # Broadcast JPEG frame to all clients
            if camera_key in camera_pipelines:
                pipeline = camera_pipelines[camera_key]
                with pipeline['lock']:
                    for client in list(pipeline['clients']):
                        try:
                            # Send frame data
                            client.send(jpeg_bytes)
                            # Send detection metadata if available (as text message)
                            if detection_meta:
                                client.send(json.dumps(detection_meta))
                        except Exception as e:
                            print(f"[Camera {camera_key}]: Error sending to client: {e}")
                            pipeline['clients'].discard(client)

            # Log every 100 frames
            if frame_count % 100 == 0:
                detection_status = "enabled" if detection_pipeline else "disabled"
                print(f"[Camera {camera_key}]: Encoded {frame_count} frames (Detection: {detection_status})")

            # Small delay to control frame rate (dynamic based on FPS)
            time.sleep(1.0 / fps)

    except Exception as e:
        print(f"[Camera {camera_key}]: Capture error: {e}")
    finally:
        print(f"[Camera {camera_key}]: Stopping capture thread.")
        cap.release()

        # Stop detection pipeline recording if active
        if detection_pipeline:
            detection_pipeline.stop()

        # Mark as stopped
        if camera_key in camera_pipelines:
            camera_pipelines[camera_key]['status'] = 'stopped'


