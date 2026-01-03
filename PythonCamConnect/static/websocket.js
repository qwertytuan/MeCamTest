// --- Global State ---
var wsProtocol = window.location.protocol === "https:" ? "wss://" : "ws://";
var baseUrl = window.location.host;
var activeStreams = {};
var fullscreenCameraId = null;
var currentRecordingsCameraId = null;
var detectionAlertTimeouts = {};

// --- Toast Notification ---
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;

    toast.textContent = message;
    toast.className = `toast ${type} active`;

    setTimeout(() => {
        toast.classList.remove('active');
    }, 3000);
}

// --- Detection Handler ---
function handleDetection(cameraId, data) {
    console.log(`Detection event for camera ${cameraId}:`, data);

    const container = document.getElementById(`container-${cameraId}`);
    const overlay = document.getElementById(`detection-overlay-${cameraId}`);
    const detectionText = document.getElementById(`detection-text-${cameraId}`);
    const recordingIndicator = document.getElementById(`recording-${cameraId}`);

    if (data.type === 'detection') {
        let alertText = 'Motion Detected';
        if (data.detection_type === 'HUMAN' || data.detection_type === 'MOTION_HUMAN') {
            alertText = `Human Detected${data.human_count > 1 ? ` (${data.human_count})` : ''}`;
        }

        if (overlay && detectionText) {
            detectionText.textContent = alertText;
            overlay.classList.add('active');
            container?.classList.add('detection-active');
        }

        // Update fullscreen alert if this camera is in fullscreen
        if (fullscreenCameraId === cameraId) {
            const fsAlert = document.getElementById('fullscreen-detection-alert');
            const fsAlertText = document.getElementById('fullscreen-alert-text');
            if (fsAlert && fsAlertText) {
                fsAlertText.textContent = alertText;
                fsAlert.classList.add('active');
            }
        }

        // Show recording indicator
        if (data.recording_active && recordingIndicator) {
            recordingIndicator.classList.add('active');
        }

        // Clear previous timeout
        if (detectionAlertTimeouts[cameraId]) {
            clearTimeout(detectionAlertTimeouts[cameraId]);
        }

        // Hide alert after 3 seconds
        detectionAlertTimeouts[cameraId] = setTimeout(() => {
            overlay?.classList.remove('active');
            container?.classList.remove('detection-active');
            if (fullscreenCameraId === cameraId) {
                document.getElementById('fullscreen-detection-alert')?.classList.remove('active');
            }
        }, 3000);
    }
}

// --- Capture Frame ---
function captureFrame(cameraId) {
    const canvas = document.getElementById(`video-canvas-${cameraId}`);
    if (!canvas) {
        showToast('No stream available', 'error');
        return;
    }

    try {
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = canvas.width;
        tempCanvas.height = canvas.height;
        const ctx = tempCanvas.getContext('2d');
        ctx.drawImage(canvas, 0, 0);

        tempCanvas.toBlob((blob) => {
            if (blob) {
                const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
                const filename = `camera_${cameraId}_${timestamp}.jpg`;

                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);

                showToast('Frame captured!', 'success');
            }
        }, 'image/jpeg', 0.95);
    } catch (error) {
        console.error('Error capturing frame:', error);
        showToast('Failed to capture frame', 'error');
    }
}

function captureFullscreenFrame() {
    if (fullscreenCameraId !== null) {
        captureFrame(fullscreenCameraId);
    }
}

// --- Fullscreen Functions ---
function openFullscreen(cameraId) {
    if (!activeStreams[cameraId]) {
        showToast('Stream not active', 'warning');
        return;
    }

    fullscreenCameraId = cameraId;
    document.getElementById('fullscreen-title').textContent = `Camera ${cameraId}`;
    document.getElementById('fullscreen-modal').classList.add('active');

    // Copy current frame to fullscreen canvas
    const sourceCanvas = document.getElementById(`video-canvas-${cameraId}`);
    const fsCanvas = document.getElementById('fullscreen-canvas');
    if (sourceCanvas && fsCanvas) {
        fsCanvas.width = sourceCanvas.width;
        fsCanvas.height = sourceCanvas.height;
        const ctx = fsCanvas.getContext('2d');
        ctx.drawImage(sourceCanvas, 0, 0);
    }
}

function closeFullscreen() {
    fullscreenCameraId = null;
    document.getElementById('fullscreen-modal').classList.remove('active');
    document.getElementById('fullscreen-detection-alert').classList.remove('active');
}

// --- Recordings Functions ---
function openRecordings(cameraId) {
    currentRecordingsCameraId = cameraId;
    document.getElementById('recordings-title').textContent = `Camera ${cameraId} - Recordings`;
    document.getElementById('recordings-modal').classList.add('active');
    loadRecordings(cameraId);
}

function viewRecordingsFullscreen() {
    if (fullscreenCameraId !== null) {
        openRecordings(fullscreenCameraId);
    }
}

function closeRecordingsModal() {
    document.getElementById('recordings-modal').classList.remove('active');
    currentRecordingsCameraId = null;
}

async function loadRecordings(cameraId) {
    const list = document.getElementById('recordings-list');
    list.innerHTML = '<div class="loading-state"><div class="spinner"></div><p>Loading...</p></div>';

    try {
        const response = await fetch(`/api/recordings/${cameraId}`);
        const data = await response.json();

        if (data.success && data.recordings && data.recordings.length > 0) {
            list.innerHTML = data.recordings.map(rec => {
                const date = new Date(rec.created_at * 1000);
                const formattedDate = date.toLocaleString();
                const fileSize = formatFileSize(rec.file_size);

                return `
                    <div class="recording-item">
                        <div class="recording-info">
                            <div class="recording-icon">🎬</div>
                            <div class="recording-details">
                                <h4>${rec.filename}</h4>
                                <p>${formattedDate} • ${fileSize}</p>
                            </div>
                        </div>
                        <div class="recording-actions">
                            <button class="control-btn" onclick="playRecording('${rec.download_url}')" title="Play">▶️</button>
                            <button class="control-btn" onclick="downloadRecording('${rec.download_url}', '${rec.filename}')" title="Download">⬇️</button>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            list.innerHTML = '<div class="empty-state"><h3>No Recordings</h3><p>No recordings available for this camera</p></div>';
        }
    } catch (error) {
        console.error('Error loading recordings:', error);
        list.innerHTML = '<div class="empty-state"><p>Failed to load recordings</p></div>';
    }
}

function playRecording(url) {
    document.getElementById('recording-player').src = url;
    document.getElementById('video-modal').classList.add('active');
}

function closeVideoModal() {
    const video = document.getElementById('recording-player');
    video.pause();
    video.src = '';
    document.getElementById('video-modal').classList.remove('active');
}

function downloadRecording(url, filename) {
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    showToast('Download started', 'success');
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// --- Main Player Setup ---
async function setupPlayers() {
    const container = document.getElementById("players-container");

    try {
        const response = await fetch("/api/camera/list");
        if (!response.ok) {
            throw new Error("Failed to fetch camera list");
        }
        const data = await response.json();
        const cameras = data.cameras || [];

        if (cameras.length === 0) {
            container.innerHTML = '<div class="empty-state"><h3>No Active Cameras</h3><p>No cameras are currently streaming. Initialize a camera via the API.</p></div>';
            updateConnectionStatus(false);
            return;
        }

        updateConnectionStatus(true);
        container.innerHTML = '';

        cameras.forEach((camera) => {
            const camId = camera.stream_id;
            const camWidth = camera.width || 640;
            const camHeight = camera.height || 480;
            const wsUrl = `${wsProtocol}${baseUrl}${camera.websocket_url}`;
            const detectionEnabled = camera.detection_enabled;
            const detectionType = camera.detection_type || 'NONE';

            console.log(`Setting up player for Camera ${camId} at ${wsUrl}`);

            const playerDiv = document.createElement("div");
            playerDiv.className = "video-container";
            playerDiv.id = `container-${camId}`;

            playerDiv.innerHTML = `
                <div class="video-header">
                    <h2>Camera ${camera.camera_id || camId}</h2>
                    <span class="camera-badge">${camWidth}x${camHeight}</span>
                </div>
                <div class="video-wrapper" onclick="openFullscreen('${camId}')">
                    <canvas class="video-canvas" id="video-canvas-${camId}" width="${camWidth}" height="${camHeight}"></canvas>

                    <div id="detection-overlay-${camId}" class="detection-overlay">
                        <div class="detection-badge">
                            ⚠️ <span id="detection-text-${camId}">Motion Detected</span>
                        </div>
                    </div>

                    <div id="recording-${camId}" class="recording-indicator">
                        <span class="rec-dot"></span> REC
                    </div>

                    <div class="video-controls">
                        <button class="control-btn" onclick="event.stopPropagation(); openFullscreen('${camId}')" title="Fullscreen">⛶</button>
                        <button class="control-btn" onclick="event.stopPropagation(); captureFrame('${camId}')" title="Capture">📷</button>
                        <button class="control-btn" onclick="event.stopPropagation(); openRecordings('${camera.camera_id || camId}')" title="Recordings">🎬</button>
                    </div>
                </div>
                <div class="video-footer">
                    <button class="action-btn secondary" onclick="openRecordings('${camera.camera_id || camId}')">
                        🎬 Recordings
                    </button>
                    <button class="action-btn primary" onclick="captureFrame('${camId}')">
                        📷 Capture
                    </button>
                </div>
                <div class="stats">
                    <span>Detection: ${detectionEnabled ? detectionType : 'Disabled'}</span>
                    <span id="fps-${camId}">FPS: --</span>
                </div>
            `;

            container.appendChild(playerDiv);

            // Setup WebSocket
            setupWebSocket(camId, wsUrl, camWidth, camHeight);
        });
    } catch (error) {
        console.error("Error setting up players:", error);
        container.innerHTML = '<div class="empty-state"><h3>Connection Error</h3><p>Failed to connect to camera server. Is it running?</p></div>';
        updateConnectionStatus(false);
    }
}

function setupWebSocket(camId, wsUrl, camWidth, camHeight) {
    const canvas = document.getElementById(`video-canvas-${camId}`);
    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    try {
        const ws = new WebSocket(wsUrl);
        ws.binaryType = 'arraybuffer';

        let frameCount = 0;
        let lastFpsTime = Date.now();

        ws.onopen = function() {
            console.log(`WebSocket opened for Camera ${camId}`);
            activeStreams[camId] = { ws, canvas, ctx, frameCount: 0 };
        };

        ws.onmessage = function(event) {
            // Check if it's text (detection data) or binary (frame)
            if (typeof event.data === 'string') {
                try {
                    const data = JSON.parse(event.data);
                    handleDetection(camId, data);
                } catch (e) {
                    console.error('Error parsing detection data:', e);
                }
                return;
            }

            // Binary - JPEG frame
            const blob = new Blob([event.data], { type: 'image/jpeg' });

            createImageBitmap(blob).then(imageBitmap => {
                ctx.drawImage(imageBitmap, 0, 0, camWidth, camHeight);

                // Update fullscreen canvas if active
                if (fullscreenCameraId === camId) {
                    const fsCanvas = document.getElementById('fullscreen-canvas');
                    if (fsCanvas) {
                        const fsCtx = fsCanvas.getContext('2d');
                        if (fsCanvas.width !== imageBitmap.width) {
                            fsCanvas.width = imageBitmap.width;
                            fsCanvas.height = imageBitmap.height;
                        }
                        fsCtx.drawImage(imageBitmap, 0, 0);
                    }
                }

                imageBitmap.close();
                frameCount++;

                // Update FPS every second
                const now = Date.now();
                if (now - lastFpsTime >= 1000) {
                    const fps = Math.round(frameCount / ((now - lastFpsTime) / 1000));
                    const fpsElement = document.getElementById(`fps-${camId}`);
                    if (fpsElement) {
                        fpsElement.textContent = `FPS: ${fps}`;
                    }
                    frameCount = 0;
                    lastFpsTime = now;
                }
            }).catch(error => {
                console.error(`Failed to decode frame for Camera ${camId}:`, error);
            });
        };

        ws.onerror = function(error) {
            console.error(`WebSocket error for Camera ${camId}:`, error);
        };

        ws.onclose = function() {
            console.log(`WebSocket closed for Camera ${camId}`);
            delete activeStreams[camId];

            // Clear canvas
            ctx.fillStyle = '#1a1a2e';
            ctx.fillRect(0, 0, camWidth, camHeight);
            ctx.fillStyle = '#666';
            ctx.font = '16px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('Stream Disconnected', camWidth / 2, camHeight / 2);
        };
    } catch (e) {
        console.error(`Failed to setup WebSocket for Camera ${camId}:`, e);
    }
}

function updateConnectionStatus(connected) {
    const status = document.getElementById('connection-status');
    if (status) {
        if (connected) {
            status.classList.add('connected');
            status.innerHTML = '<span class="status-dot"></span> Connected';
        } else {
            status.classList.remove('connected');
            status.innerHTML = '<span class="status-dot"></span> Disconnected';
        }
    }
}

// --- Keyboard Shortcuts ---
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('video-modal').classList.contains('active')) {
            closeVideoModal();
        } else if (document.getElementById('recordings-modal').classList.contains('active')) {
            closeRecordingsModal();
        } else if (document.getElementById('fullscreen-modal').classList.contains('active')) {
            closeFullscreen();
        }
    }
    if ((e.key === 'c' || e.key === 'C') && fullscreenCameraId !== null) {
        captureFullscreenFrame();
    }
});

// --- Initialize on Page Load ---
document.addEventListener("DOMContentLoaded", setupPlayers);

// --- Cleanup on Page Unload ---
window.addEventListener('beforeunload', () => {
    Object.keys(activeStreams).forEach(camId => {
        if (activeStreams[camId].ws) {
            activeStreams[camId].ws.close();
        }
    });
});
