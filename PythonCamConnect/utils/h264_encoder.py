"""
H.264 Video Encoder for WebCodecs API
Provides hardware-accelerated video encoding with minimal latency
"""
import av
import numpy as np
import threading


class H264Encoder:
    """
    H.264 encoder optimized for low-latency streaming with WebCodecs API
    """

    def __init__(self, width=1920, height=1080, fps=30, bitrate=2000000):
        """
        Initialize H.264 encoder

        Args:
            width: Frame width
            height: Frame height
            fps: Frames per second
            bitrate: Target bitrate in bits/second (2Mbps default)
        """
        self.width = width
        self.height = height
        self.fps = fps
        self.bitrate = bitrate
        self.container = None
        self.stream = None
        self.frame_count = 0
        self.lock = threading.Lock()
        self.initialized = False

    def initialize(self):
        """Initialize the H.264 encoder with optimized settings"""
        try:
            with self.lock:
                # Create in-memory container for H.264
                self.container = av.open('pipe:', mode='w', format='h264')

                # Add video stream with H.264 codec
                self.stream = self.container.add_stream('h264', rate=self.fps)
                self.stream.width = self.width
                self.stream.height = self.height
                self.stream.pix_fmt = 'yuv420p'

                # Optimize for low latency streaming
                self.stream.options = {
                    'preset': 'ultrafast',     # Fastest encoding
                    'tune': 'zerolatency',     # Minimize latency
                    'crf': '23',               # Quality (lower = better, 18-28 recommended)
                    'profile': 'baseline',     # Baseline profile for compatibility
                    'level': '3.0',            # Level 3.0
                    'g': str(self.fps),        # GOP size = FPS (1 keyframe per second)
                    'keyint_min': str(self.fps), # Minimum GOP size
                }

                # Set bitrate
                self.stream.bit_rate = self.bitrate

                self.initialized = True
                print(f"[H264Encoder] Initialized: {self.width}x{self.height} @ {self.fps} FPS")
                return True

        except Exception as e:
            print(f"[H264Encoder] Initialization error: {e}")
            return False

    def encode_frame(self, frame_bgr):
        """
        Encode a single frame to H.264

        Args:
            frame_bgr: OpenCV frame in BGR format (numpy array)

        Returns:
            List of encoded packets (bytes) or empty list on error
        """
        if not self.initialized:
            print("[H264Encoder] Error: Encoder not initialized")
            return []

        try:
            with self.lock:
                # Convert BGR (OpenCV) to RGB
                frame_rgb = frame_bgr[:, :, ::-1]

                # Create PyAV frame
                av_frame = av.VideoFrame.from_ndarray(frame_rgb, format='rgb24')
                av_frame.pts = self.frame_count
                self.frame_count += 1

                # Encode frame
                packets = []
                for packet in self.stream.encode(av_frame):
                    packets.append(bytes(packet))

                return packets

        except Exception as e:
            print(f"[H264Encoder] Encoding error: {e}")
            return []

    def flush(self):
        """Flush any remaining packets"""
        if not self.initialized:
            return []

        try:
            with self.lock:
                packets = []
                for packet in self.stream.encode():
                    packets.append(bytes(packet))
                return packets
        except Exception as e:
            print(f"[H264Encoder] Flush error: {e}")
            return []

    def close(self):
        """Close the encoder and release resources"""
        try:
            with self.lock:
                if self.container:
                    # Flush remaining packets
                    self.flush()
                    self.container.close()
                    self.container = None
                    self.stream = None
                    self.initialized = False
                    print("[H264Encoder] Closed")
        except Exception as e:
            print(f"[H264Encoder] Close error: {e}")

    def get_codec_info(self):
        """Get codec information for WebCodecs configuration"""
        return {
            'codec': 'avc1.42E01E',  # H.264 Baseline Profile Level 3.0
            'width': self.width,
            'height': self.height,
            'fps': self.fps
        }

