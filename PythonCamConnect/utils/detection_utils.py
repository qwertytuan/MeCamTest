"""
Detection Utilities for Camera System
Provides motion detection and human detection functionality
"""

import cv2
import numpy as np
import os
import time
from datetime import datetime
from threading import Thread, Event
from collections import deque
from ultralytics import YOLO

# Load YOLO model for human detection (lazy loading)
_yolo_model = None

def get_yolo_model():
    """Lazy load YOLO model to avoid memory issues on startup"""
    global _yolo_model
    if _yolo_model is None:
        model_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'yolo11n.pt')
        _yolo_model = YOLO(model_path)
    return _yolo_model


class MotionDetector:
    """Motion detection using frame differencing"""

    def __init__(self, sensitivity=50, min_area=500):
        """
        Args:
            sensitivity: 1-100 scale (higher = more sensitive)
            min_area: Minimum contour area to trigger detection
        """
        self.sensitivity = max(1, min(100, sensitivity))
        self.min_area = min_area
        self.baseline_frame = None
        self.threshold_value = self._calculate_threshold()
        self.blur_kernel = (21, 21)

    def _calculate_threshold(self):
        """Convert sensitivity to threshold value (inverse relationship)"""
        # sensitivity 100 -> threshold 10 (very sensitive)
        # sensitivity 1 -> threshold 60 (not sensitive)
        return int(60 - (self.sensitivity * 0.5))

    def update_sensitivity(self, sensitivity):
        """Update sensitivity dynamically"""
        self.sensitivity = max(1, min(100, sensitivity))
        self.threshold_value = self._calculate_threshold()

    def detect(self, frame):
        """
        Detect motion in frame

        Args:
            frame: BGR frame from camera

        Returns:
            tuple: (motion_detected: bool, motion_regions: list of contours)
        """
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        gray = cv2.GaussianBlur(gray, self.blur_kernel, 0)

        if self.baseline_frame is None:
            self.baseline_frame = gray
            return False, []

        # Compute difference
        delta = cv2.absdiff(self.baseline_frame, gray)
        thresh = cv2.threshold(delta, self.threshold_value, 255, cv2.THRESH_BINARY)[1]
        thresh = cv2.dilate(thresh, None, iterations=2)

        # Find contours
        contours, _ = cv2.findContours(thresh.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

        motion_regions = []
        for contour in contours:
            if cv2.contourArea(contour) >= self.min_area:
                motion_regions.append(contour)

        motion_detected = len(motion_regions) > 0

        # Update baseline periodically (adaptive background)
        self.baseline_frame = cv2.addWeighted(self.baseline_frame, 0.95, gray, 0.05, 0)

        return motion_detected, motion_regions

    def reset_baseline(self, frame=None):
        """Reset the baseline frame"""
        if frame is not None:
            self.baseline_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
            self.baseline_frame = cv2.GaussianBlur(self.baseline_frame, self.blur_kernel, 0)
        else:
            self.baseline_frame = None


class HumanDetector:
    """Human detection using YOLO"""

    def __init__(self, confidence_threshold=0.5):
        """
        Args:
            confidence_threshold: Minimum confidence for detection (0-1)
        """
        self.confidence_threshold = confidence_threshold
        self.model = get_yolo_model()

    def detect(self, frame):
        """
        Detect humans in frame

        Args:
            frame: BGR frame from camera

        Returns:
            tuple: (humans_detected: bool, detections: list of dict with bbox, confidence)
        """
        results = self.model(frame, verbose=False)

        humans = []
        for result in results:
            boxes = result.boxes.cpu().numpy()
            for box in boxes:
                cls = int(box.cls[0])
                class_name = self.model.names[cls]

                if class_name.lower() == 'person':
                    conf = float(box.conf[0])
                    if conf >= self.confidence_threshold:
                        x1, y1, x2, y2 = box.xyxy[0].astype(int)
                        humans.append({
                            'bbox': [int(x1), int(y1), int(x2), int(y2)],
                            'confidence': conf,
                            'class': 'person'
                        })

        return len(humans) > 0, humans

    def detect_with_annotation(self, frame):
        """
        Detect humans and draw bounding boxes

        Returns:
            tuple: (annotated_frame, humans_detected, detections)
        """
        humans_detected, detections = self.detect(frame)
        annotated = frame.copy()

        for det in detections:
            x1, y1, x2, y2 = det['bbox']
            conf = det['confidence']
            cv2.rectangle(annotated, (x1, y1), (x2, y2), (0, 255, 0), 2)
            cv2.putText(annotated, f"Person {conf:.2f}", (x1, y1 - 10),
                       cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 255, 0), 2)

        return annotated, humans_detected, detections


class CombinedDetector:
    """Combined motion and human detection"""

    def __init__(self, detection_type='MOTION_HUMAN', sensitivity=50, confidence_threshold=0.5):
        """
        Args:
            detection_type: 'MOTION', 'HUMAN', or 'MOTION_HUMAN'
            sensitivity: Motion detection sensitivity (1-100)
            confidence_threshold: Human detection confidence (0-1)
        """
        self.detection_type = detection_type
        self.motion_detector = MotionDetector(sensitivity) if detection_type in ['MOTION', 'MOTION_HUMAN'] else None
        self.human_detector = HumanDetector(confidence_threshold) if detection_type in ['HUMAN', 'MOTION_HUMAN'] else None

    def detect(self, frame):
        """
        Perform detection based on detection_type

        For MOTION_HUMAN: First detects motion, then checks for humans

        Returns:
            dict: {
                'detected': bool,
                'detection_type': str ('MOTION', 'HUMAN', or 'MOTION_HUMAN'),
                'motion_detected': bool,
                'human_detected': bool,
                'human_detections': list,
                'motion_regions': list
            }
        """
        result = {
            'detected': False,
            'detection_type': None,
            'motion_detected': False,
            'human_detected': False,
            'human_detections': [],
            'motion_regions': []
        }

        if self.detection_type == 'MOTION':
            motion_detected, regions = self.motion_detector.detect(frame)
            result['motion_detected'] = motion_detected
            result['motion_regions'] = regions
            result['detected'] = motion_detected
            result['detection_type'] = 'MOTION' if motion_detected else None

        elif self.detection_type == 'HUMAN':
            human_detected, detections = self.human_detector.detect(frame)
            result['human_detected'] = human_detected
            result['human_detections'] = detections
            result['detected'] = human_detected
            result['detection_type'] = 'HUMAN' if human_detected else None

        elif self.detection_type == 'MOTION_HUMAN':
            # First check for motion
            motion_detected, regions = self.motion_detector.detect(frame)
            result['motion_detected'] = motion_detected
            result['motion_regions'] = regions

            if motion_detected:
                # If motion detected, check for humans
                human_detected, detections = self.human_detector.detect(frame)
                result['human_detected'] = human_detected
                result['human_detections'] = detections

                if human_detected:
                    result['detected'] = True
                    result['detection_type'] = 'MOTION_HUMAN'
                else:
                    # Motion but no human - still record as motion
                    result['detected'] = True
                    result['detection_type'] = 'MOTION'

        return result


class VideoRecorder:
    """Handles video recording when detection is triggered"""

    def __init__(self, storage_path, camera_id, duration=180, fps=30, resolution=(640, 480)):
        """
        Args:
            storage_path: Base path for storing recordings
            camera_id: Camera identifier
            duration: Recording duration in seconds (default 3 minutes)
            fps: Frames per second
            resolution: (width, height) tuple
        """
        self.storage_path = storage_path
        self.camera_id = camera_id
        self.duration = duration
        self.fps = fps
        self.resolution = resolution
        self.is_recording = False
        self.current_writer = None
        self.current_file_path = None
        self.recording_start_time = None
        self.frame_count = 0

        # Ensure storage directory exists
        self.camera_storage_path = os.path.join(storage_path, 'recordings', str(camera_id))
        os.makedirs(self.camera_storage_path, exist_ok=True)

    def start_recording(self, detection_type='MOTION'):
        """Start a new recording"""
        if self.is_recording:
            return self.current_file_path

        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f"camera_{self.camera_id}_{detection_type}_{timestamp}.mp4"
        self.current_file_path = os.path.join(self.camera_storage_path, filename)

        fourcc = cv2.VideoWriter_fourcc(*'mp4v')
        self.current_writer = cv2.VideoWriter(
            self.current_file_path,
            fourcc,
            self.fps,
            self.resolution
        )

        self.is_recording = True
        self.recording_start_time = time.time()
        self.frame_count = 0

        print(f"[VideoRecorder] Started recording: {self.current_file_path}")
        return self.current_file_path

    def write_frame(self, frame):
        """Write a frame to the recording"""
        if not self.is_recording or self.current_writer is None:
            return False

        # Resize frame if needed
        if frame.shape[1] != self.resolution[0] or frame.shape[0] != self.resolution[1]:
            frame = cv2.resize(frame, self.resolution)

        self.current_writer.write(frame)
        self.frame_count += 1

        # Check if duration exceeded
        elapsed = time.time() - self.recording_start_time
        if elapsed >= self.duration:
            self.stop_recording()
            return False

        return True

    def stop_recording(self):
        """Stop the current recording"""
        if not self.is_recording:
            return None

        if self.current_writer is not None:
            self.current_writer.release()
            self.current_writer = None

        self.is_recording = False
        file_path = self.current_file_path

        # Calculate file size
        file_size = os.path.getsize(file_path) if os.path.exists(file_path) else 0
        duration = int(time.time() - self.recording_start_time)

        print(f"[VideoRecorder] Stopped recording: {file_path} ({file_size} bytes, {duration}s)")

        self.current_file_path = None
        self.recording_start_time = None

        return {
            'file_path': file_path,
            'file_name': os.path.basename(file_path),
            'file_size': file_size,
            'duration': duration,
            'frame_count': self.frame_count
        }

    def get_status(self):
        """Get current recording status"""
        if not self.is_recording:
            return {'is_recording': False}

        elapsed = time.time() - self.recording_start_time
        return {
            'is_recording': True,
            'file_path': self.current_file_path,
            'elapsed_seconds': int(elapsed),
            'remaining_seconds': max(0, int(self.duration - elapsed)),
            'frame_count': self.frame_count
        }


class ThumbnailCapture:
    """Handles thumbnail image capture for cameras"""

    def __init__(self, storage_path, camera_id, width=320, height=240):
        """
        Args:
            storage_path: Base path for storing thumbnails
            camera_id: Camera identifier
            width: Thumbnail width
            height: Thumbnail height
        """
        self.storage_path = storage_path
        self.camera_id = camera_id
        self.width = width
        self.height = height

        # Ensure storage directory exists
        self.thumbnail_path = os.path.join(storage_path, 'thumbnails', str(camera_id))
        os.makedirs(self.thumbnail_path, exist_ok=True)

    def capture(self, frame, is_detection=False, detection_type='NONE'):
        """
        Capture a thumbnail from frame

        Args:
            frame: BGR frame
            is_detection: Whether this is a detection-triggered thumbnail
            detection_type: Type of detection ('NONE', 'MOTION', 'HUMAN')

        Returns:
            dict with thumbnail info
        """
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        prefix = f"detection_{detection_type}" if is_detection else "thumbnail"
        filename = f"camera_{self.camera_id}_{prefix}_{timestamp}.jpg"
        file_path = os.path.join(self.thumbnail_path, filename)

        # Resize frame
        thumbnail = cv2.resize(frame, (self.width, self.height))

        # Save thumbnail
        cv2.imwrite(file_path, thumbnail, [cv2.IMWRITE_JPEG_QUALITY, 85])

        file_size = os.path.getsize(file_path) if os.path.exists(file_path) else 0

        return {
            'file_path': file_path,
            'file_name': filename,
            'file_size': file_size,
            'width': self.width,
            'height': self.height,
            'is_detection_thumbnail': is_detection,
            'detection_type': detection_type,
            'captured_at': datetime.now().isoformat()
        }

    def get_latest_thumbnail(self):
        """Get the most recent thumbnail for this camera"""
        if not os.path.exists(self.thumbnail_path):
            return None

        files = [f for f in os.listdir(self.thumbnail_path) if f.endswith('.jpg')]
        if not files:
            return None

        files.sort(reverse=True)
        latest = files[0]
        return os.path.join(self.thumbnail_path, latest)


class DetectionPipeline:
    """
    Complete detection pipeline that integrates motion/human detection,
    video recording, and thumbnail capture
    """

    def __init__(self, camera_id, storage_path, detection_type='MOTION',
                 sensitivity=50, confidence_threshold=0.5,
                 recording_duration=180, fps=30, resolution=(640, 480)):
        """
        Args:
            camera_id: Camera identifier
            storage_path: Base storage path
            detection_type: 'NONE', 'MOTION', 'HUMAN', or 'MOTION_HUMAN'
            sensitivity: Motion detection sensitivity (1-100)
            confidence_threshold: Human detection confidence (0-1)
            recording_duration: Recording duration in seconds
            fps: Frames per second for recording
            resolution: (width, height) tuple
        """
        self.camera_id = camera_id
        self.storage_path = storage_path
        self.detection_type = detection_type
        self.is_enabled = detection_type != 'NONE'

        # Initialize components
        self.detector = CombinedDetector(detection_type, sensitivity, confidence_threshold) if self.is_enabled else None
        self.recorder = VideoRecorder(storage_path, camera_id, recording_duration, fps, resolution)
        self.thumbnail = ThumbnailCapture(storage_path, camera_id)

        # Detection state
        self.last_detection_time = 0
        self.detection_cooldown = 5  # seconds between detection triggers
        self.recording_triggered = False

        # Event storage
        self.detection_events = []

    def process_frame(self, frame):
        """
        Process a frame through the detection pipeline

        Args:
            frame: BGR frame from camera

        Returns:
            dict with processing results
        """
        result = {
            'detection_triggered': False,
            'detection_type': None,
            'recording_started': False,
            'recording_stopped': False,
            'thumbnail_captured': False,
            'recording_info': None,
            'thumbnail_info': None,
            'detection_details': None
        }

        if not self.is_enabled:
            return result

        # Run detection
        detection = self.detector.detect(frame)

        current_time = time.time()

        if detection['detected']:
            result['detection_triggered'] = True
            result['detection_type'] = detection['detection_type']
            result['detection_details'] = detection

            # Check cooldown
            if current_time - self.last_detection_time >= self.detection_cooldown:
                self.last_detection_time = current_time

                # Capture thumbnail on detection
                thumbnail_info = self.thumbnail.capture(
                    frame,
                    is_detection=True,
                    detection_type=detection['detection_type']
                )
                result['thumbnail_captured'] = True
                result['thumbnail_info'] = thumbnail_info

                # Start recording if not already recording
                if not self.recorder.is_recording:
                    self.recorder.start_recording(detection['detection_type'])
                    result['recording_started'] = True
                    self.recording_triggered = True

                    # Store detection event
                    self.detection_events.append({
                        'detection_type': detection['detection_type'],
                        'timestamp': datetime.now().isoformat(),
                        'human_count': len(detection.get('human_detections', [])),
                        'thumbnail_path': thumbnail_info['file_path']
                    })

        # Write frame to recording if active
        if self.recorder.is_recording:
            if not self.recorder.write_frame(frame):
                # Recording stopped (duration exceeded)
                result['recording_stopped'] = True
                result['recording_info'] = self.recorder.get_status()
                self.recording_triggered = False

        return result

    def stop(self):
        """Stop the pipeline and cleanup"""
        recording_info = None
        if self.recorder.is_recording:
            recording_info = self.recorder.stop_recording()
        return recording_info

    def update_settings(self, detection_type=None, sensitivity=None, confidence_threshold=None):
        """Update detection settings"""
        if detection_type is not None:
            self.detection_type = detection_type
            self.is_enabled = detection_type != 'NONE'
            if self.is_enabled:
                self.detector = CombinedDetector(
                    detection_type,
                    sensitivity or 50,
                    confidence_threshold or 0.5
                )
            else:
                self.detector = None

        if sensitivity is not None and self.detector and self.detector.motion_detector:
            self.detector.motion_detector.update_sensitivity(sensitivity)

        if confidence_threshold is not None and self.detector and self.detector.human_detector:
            self.detector.human_detector.confidence_threshold = confidence_threshold

    def get_status(self):
        """Get current pipeline status"""
        return {
            'camera_id': self.camera_id,
            'detection_type': self.detection_type,
            'is_enabled': self.is_enabled,
            'recording_status': self.recorder.get_status(),
            'detection_events_count': len(self.detection_events),
            'last_detection_time': self.last_detection_time
        }

