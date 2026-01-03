"""
Email Alert Service for Camera Detection System
Sends webhook alerts when human is detected
"""

import cv2
import time
import requests
from datetime import datetime
from threading import Lock


# Default webhook URL
DEFAULT_WEBHOOK_URL = "https://tuanserver.myddns.me:5678/webhook-test/6972594a-3fd5-43dc-8c58-d67cb036d4f5"


class EmailAlertService:
    """
    Service to send email alerts via webhook when human detection is triggered.
    Implements rate limiting (3 minutes between alerts per camera).
    """

    def __init__(self, camera_id, camera_name, camera_location,
                 alert_email=None, alert_api_username=None, alert_api_password=None,
                 alert_enabled=False, rate_limit_minutes=3):
        """
        Args:
            camera_id: Unique camera identifier
            camera_name: Human-readable camera name
            camera_location: Camera location description
            alert_email: Email address to send alerts to
            alert_api_username: Basic auth username for the webhook
            alert_api_password: Basic auth password for the webhook
            alert_enabled: Whether alerts are enabled
            rate_limit_minutes: Minimum minutes between alerts (default 3)
        """
        self.camera_id = camera_id
        self.camera_name = camera_name
        self.camera_location = camera_location
        self.alert_email = alert_email
        self.webhook_url = DEFAULT_WEBHOOK_URL
        self.alert_api_username = alert_api_username
        self.alert_api_password = alert_api_password
        self.alert_enabled = alert_enabled
        self.rate_limit_seconds = rate_limit_minutes * 60  # Convert to seconds

        # Rate limiting tracking
        self.last_alert_time = 0
        self._lock = Lock()

        # Alert history for debugging
        self.alert_history = []

    def update_settings(self, alert_email=None, alert_api_username=None, alert_api_password=None,
                       alert_enabled=None, camera_name=None, camera_location=None):
        """Update alert settings dynamically"""
        with self._lock:
            if alert_email is not None:
                self.alert_email = alert_email
            if alert_api_username is not None:
                self.alert_api_username = alert_api_username
            if alert_api_password is not None:
                self.alert_api_password = alert_api_password
            if alert_enabled is not None:
                self.alert_enabled = alert_enabled
            if camera_name is not None:
                self.camera_name = camera_name
            if camera_location is not None:
                self.camera_location = camera_location

    def is_configured(self):
        """Check if the service is properly configured"""
        return (
            self.alert_enabled and
            self.alert_email and
            self.alert_api_username and
            self.alert_api_password
        )

    def can_send_alert(self):
        """Check if enough time has passed since last alert (rate limiting)"""
        current_time = time.time()
        with self._lock:
            time_since_last = current_time - self.last_alert_time
            return time_since_last >= self.rate_limit_seconds

    def _frame_to_binary(self, frame, quality=85):
        """Convert OpenCV frame to binary JPEG"""
        if frame is None:
            return None

        # Encode frame as JPEG
        encode_params = [cv2.IMWRITE_JPEG_QUALITY, quality]
        success, buffer = cv2.imencode('.jpg', frame, encode_params)

        if not success:
            return None

        # Return binary buffer
        return buffer.tobytes()

    def send_alert(self, frame, detection_count=1, detection_details=None):
        """
        Send an email alert via the webhook.

        Args:
            frame: OpenCV BGR frame when detection occurred
            detection_count: Number of humans detected
            detection_details: Additional detection details (bounding boxes, etc.)

        Returns:
            dict: Result with 'success', 'message', and optionally 'response'
        """
        # Check if service is configured
        if not self.is_configured():
            return {
                'success': False,
                'message': 'Alert service not configured or disabled',
                'skipped': True
            }

        # Check rate limiting
        if not self.can_send_alert():
            time_remaining = self.rate_limit_seconds - (time.time() - self.last_alert_time)
            return {
                'success': False,
                'message': f'Rate limited. Next alert available in {int(time_remaining)} seconds',
                'rate_limited': True,
                'time_remaining': int(time_remaining)
            }

        try:
            # Convert frame to binary JPEG
            frame_binary = self._frame_to_binary(frame)
            if not frame_binary:
                return {
                    'success': False,
                    'message': 'Failed to encode frame'
                }

            # Prepare the payload
            timestamp = datetime.now().isoformat()

            # Prepare form data for multipart upload
            form_data = {
                'email': (None, self.alert_email),
                'camera_name': (None, self.camera_name),
                'camera_location': (None, self.camera_location),
                'camera_id': (None, str(self.camera_id)),
                'detection_time': (None, timestamp),
                'detection_count': (None, str(detection_count)),
                'detection_type': (None, 'HUMAN'),
            }

            # Add image as binary file - field name must be 'data' for n8n webhook
            files = {
                'data': (f'detection_{self.camera_id}_{timestamp.replace(":", "-")}.jpg', frame_binary, 'image/jpeg')
            }

            # Send POST request with basic auth to the webhook (multipart/form-data)
            response = requests.post(
                self.webhook_url,
                data={k: v[1] for k, v in form_data.items()},
                files=files,
                auth=(self.alert_api_username, self.alert_api_password),
                timeout=30,
                verify=False  # For self-signed certificates
            )

            # Update last alert time on successful send
            with self._lock:
                self.last_alert_time = time.time()

            # Record alert in history
            alert_record = {
                'timestamp': timestamp,
                'success': response.ok,
                'status_code': response.status_code,
                'detection_count': detection_count
            }
            self.alert_history.append(alert_record)

            # Keep only last 100 alerts in history
            if len(self.alert_history) > 100:
                self.alert_history = self.alert_history[-100:]

            if response.ok:
                print(f"[EmailAlert] Alert sent successfully for camera {self.camera_id} to {self.alert_email}")
                return {
                    'success': True,
                    'message': 'Alert sent successfully',
                    'status_code': response.status_code,
                    'response': response.text if response.content else None
                }
            else:
                print(f"[EmailAlert] Alert failed for camera {self.camera_id}: {response.status_code}")
                return {
                    'success': False,
                    'message': f'Webhook returned error: {response.status_code}',
                    'status_code': response.status_code,
                    'response': response.text
                }

        except requests.exceptions.Timeout:
            print(f"[EmailAlert] Timeout sending alert for camera {self.camera_id}")
            return {
                'success': False,
                'message': 'Request timed out'
            }
        except requests.exceptions.ConnectionError as e:
            print(f"[EmailAlert] Connection error for camera {self.camera_id}: {e}")
            return {
                'success': False,
                'message': f'Connection error: {str(e)}'
            }
        except Exception as e:
            print(f"[EmailAlert] Error sending alert for camera {self.camera_id}: {e}")
            return {
                'success': False,
                'message': f'Error: {str(e)}'
            }

    def get_status(self):
        """Get current alert service status"""
        current_time = time.time()
        time_since_last = current_time - self.last_alert_time

        return {
            'camera_id': self.camera_id,
            'alert_enabled': self.alert_enabled,
            'is_configured': self.is_configured(),
            'alert_email': self.alert_email,
            'webhook_url': self.webhook_url,
            'rate_limit_minutes': self.rate_limit_seconds // 60,
            'can_send_alert': self.can_send_alert(),
            'seconds_until_next_alert': max(0, self.rate_limit_seconds - time_since_last),
            'total_alerts_sent': len([a for a in self.alert_history if a.get('success')]),
            'last_alert_time': datetime.fromtimestamp(self.last_alert_time).isoformat() if self.last_alert_time > 0 else None
        }

    def get_alert_history(self, limit=10):
        """Get recent alert history"""
        return self.alert_history[-limit:]
