<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shared Camera Stream - Camera Hub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2d3748;
            --primary-light: #4a5568;
            --secondary: #718096;
            --accent: #4299e1;
            --success: #48bb78;
            --danger: #f56565;
            --warning: #ed8936;
            --bg-main: #1a202c;
            --bg-card: #2d3748;
            --text-primary: #ffffff;
            --text-secondary: #a0aec0;
            --border: #4a5568;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .header-brand svg {
            width: 28px;
            height: 28px;
            color: var(--accent);
        }

        .header-brand h1 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .header-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .camera-name {
            font-size: 1rem;
            color: var(--text-secondary);
        }

        .timer-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(245, 101, 101, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--danger);
        }

        .timer-container.warning {
            background: rgba(237, 137, 54, 0.2);
            border-color: var(--warning);
            animation: pulse 2s infinite;
        }

        .timer-container.critical {
            background: rgba(245, 101, 101, 0.3);
            border-color: var(--danger);
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .timer-icon {
            width: 20px;
            height: 20px;
            color: var(--danger);
        }

        .timer-text {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            font-variant-numeric: tabular-nums;
        }

        .timer-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-left: 0.25rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        /* Stream Container */
        .stream-container {
            width: 100%;
            max-width: 1200px;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        .stream-container canvas {
            width: 100%;
            display: block;
        }

        /* Status Overlays */
        .status-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.9);
        }

        .status-overlay.hidden {
            display: none;
        }

        .status-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 1.5rem;
        }

        .status-icon.loading {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .status-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .status-message {
            font-size: 1rem;
            color: var(--text-secondary);
            text-align: center;
            max-width: 400px;
        }

        .status-overlay.error .status-icon {
            color: var(--danger);
        }

        .status-overlay.expired .status-icon {
            color: var(--warning);
        }

        /* Live Indicator */
        .live-indicator {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: var(--danger);
            color: white;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: blink 1s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* Footer */
        .footer {
            padding: 1rem 2rem;
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.85rem;
            background: var(--bg-card);
            border-top: 1px solid var(--border);
        }

        .footer a {
            color: var(--accent);
            text-decoration: none;
        }

        /* Countdown Modal */
        .countdown-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .countdown-modal.active {
            display: flex;
        }

        .countdown-content {
            text-align: center;
            padding: 3rem;
        }

        .countdown-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 2rem;
            color: var(--warning);
        }

        .countdown-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .countdown-message {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .countdown-number {
            font-size: 4rem;
            font-weight: 700;
            color: var(--danger);
            font-variant-numeric: tabular-nums;
        }

        /* Fullscreen Button */
        .fullscreen-btn {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.7);
            border: none;
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            color: white;
            transition: background 0.2s;
        }

        .fullscreen-btn:hover {
            background: rgba(0, 0, 0, 0.9);
        }

        .fullscreen-btn svg {
            width: 24px;
            height: 24px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .header-info {
                flex-direction: column;
                gap: 0.75rem;
            }

            .stream-container {
                border-radius: 0;
            }

            .main-content {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-brand">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <h1>Camera Hub</h1>
        </div>

        <div class="header-info">
            <span class="camera-name" id="cameraName">Loading...</span>
            <div class="timer-container" id="timerContainer">
                <svg class="timer-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="timer-text" id="timerText">--:--</span>
                <span class="timer-label">remaining</span>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="stream-container" id="streamContainer">
            <canvas id="videoCanvas" width="1280" height="720"></canvas>

            <div class="live-indicator" id="liveIndicator" style="display: none;">
                <span class="live-dot"></span>
                LIVE
            </div>

            <button class="fullscreen-btn" id="fullscreenBtn" title="Toggle Fullscreen">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>

            <!-- Loading Overlay -->
            <div class="status-overlay" id="loadingOverlay">
                <svg class="status-icon loading" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <h2 class="status-title">Connecting...</h2>
                <p class="status-message">Please wait while we connect to the camera stream</p>
            </div>

            <!-- Error Overlay -->
            <div class="status-overlay error hidden" id="errorOverlay">
                <svg class="status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h2 class="status-title" id="errorTitle">Stream Unavailable</h2>
                <p class="status-message" id="errorMessage">Unable to connect to the camera stream</p>
                <p class="status-hint" id="errorHint" style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-secondary);"></p>
            </div>

            <!-- Expired Overlay -->
            <div class="status-overlay expired hidden" id="expiredOverlay">
                <svg class="status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="status-title">Session Expired</h2>
                <p class="status-message">Your viewing session has ended. The stream has been automatically closed.</p>
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>Shared camera stream from <a href="{{ url('/') }}">Camera Hub</a></p>
    </footer>

    <!-- Final Countdown Modal -->
    <div class="countdown-modal" id="countdownModal">
        <div class="countdown-content">
            <svg class="countdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h2 class="countdown-title">Session Ending Soon</h2>
            <p class="countdown-message">Stream will close in</p>
            <div class="countdown-number" id="countdownNumber">10</div>
        </div>
    </div>

    <script>
        const shareToken = '{{ $token }}';
        let websocket = null;
        let watchEndTime = null;
        let timerInterval = null;
        let isStreamActive = false;

        // DOM Elements
        const videoCanvas = document.getElementById('videoCanvas');
        const ctx = videoCanvas.getContext('2d');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const errorOverlay = document.getElementById('errorOverlay');
        const expiredOverlay = document.getElementById('expiredOverlay');
        const liveIndicator = document.getElementById('liveIndicator');
        const timerContainer = document.getElementById('timerContainer');
        const timerText = document.getElementById('timerText');
        const cameraName = document.getElementById('cameraName');
        const countdownModal = document.getElementById('countdownModal');
        const countdownNumber = document.getElementById('countdownNumber');
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        const streamContainer = document.getElementById('streamContainer');

        // Initialize
        document.addEventListener('DOMContentLoaded', async function() {
            await validateAndConnect();
            setupFullscreen();
        });

        async function validateAndConnect() {
            try {
                const response = await fetch(`/api/share/validate/${shareToken}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    }
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    let errorMessage = data.error || 'Invalid share link';

                    // Add more context based on status code
                    if (response.status === 404) {
                        errorMessage = 'This share link is invalid or has been deleted';
                    } else if (response.status === 403) {
                        errorMessage = data.error || 'Access denied - link may have expired';
                    } else if (response.status === 503) {
                        errorMessage = 'Camera stream is not currently active. Please contact the administrator.';
                    }

                    showError(errorMessage);
                    return;
                }

                // Set camera name
                cameraName.textContent = data.camera.name;

                // Set watch end time
                if (data.share_info.watch_end_timestamp) {
                    watchEndTime = new Date(data.share_info.watch_end_timestamp);
                } else {
                    // First access - calculate from now
                    watchEndTime = new Date(Date.now() + (data.share_info.watch_duration * 1000));
                }

                // Start timer
                startTimer();

                // Connect to WebSocket stream
                connectToStream(data.camera.websocket_url);

            } catch (error) {
                console.error('Validation error:', error);

                // Check if it's a network error or parse error
                if (error instanceof TypeError && error.message.includes('fetch')) {
                    showError('Network error. Please check your connection and try again.');
                } else if (error instanceof SyntaxError) {
                    showError('Server error. The share link service may be unavailable.');
                } else {
                    showError('Failed to validate share link. Please try again later.');
                }
            }
        }

        function connectToStream(websocketUrl) {
            if (!websocketUrl) {
                showError('Camera stream is not available');
                return;
            }

            try {
                websocket = new WebSocket(websocketUrl);

                websocket.onopen = function() {
                    console.log('WebSocket connected');
                    loadingOverlay.classList.add('hidden');
                    liveIndicator.style.display = 'flex';
                    isStreamActive = true;
                };

                websocket.onmessage = function(event) {
                    if (event.data instanceof Blob) {
                        const img = new Image();
                        img.onload = function() {
                            ctx.drawImage(img, 0, 0, videoCanvas.width, videoCanvas.height);
                            URL.revokeObjectURL(img.src);
                        };
                        img.src = URL.createObjectURL(event.data);
                    }
                };

                websocket.onerror = function(error) {
                    console.error('WebSocket error:', error);
                    showError('Connection error. Please try again.');
                };

                websocket.onclose = function() {
                    console.log('WebSocket closed');
                    isStreamActive = false;
                    if (!expiredOverlay.classList.contains('hidden') === false) {
                        // Don't show error if expired
                        return;
                    }
                    liveIndicator.style.display = 'none';
                };

            } catch (error) {
                console.error('WebSocket connection error:', error);
                showError('Failed to connect to camera stream');
            }
        }

        function startTimer() {
            timerInterval = setInterval(updateTimer, 1000);
            updateTimer();
        }

        function updateTimer() {
            if (!watchEndTime) return;

            const now = new Date();
            const remaining = Math.max(0, Math.floor((watchEndTime - now) / 1000));

            // Format time
            const hours = Math.floor(remaining / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;

            let timeString = '';
            if (hours > 0) {
                timeString = `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            } else {
                timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            }

            timerText.textContent = timeString;

            // Visual warnings
            if (remaining <= 30) {
                timerContainer.classList.add('critical');
                timerContainer.classList.remove('warning');
            } else if (remaining <= 60) {
                timerContainer.classList.add('warning');
                timerContainer.classList.remove('critical');
            }

            // Show countdown modal for last 10 seconds
            if (remaining <= 10 && remaining > 0) {
                countdownModal.classList.add('active');
                countdownNumber.textContent = remaining;
            }

            // Time expired
            if (remaining <= 0) {
                clearInterval(timerInterval);
                endSession();
            }
        }

        function endSession() {
            // Close WebSocket
            if (websocket) {
                websocket.close();
            }

            // Hide countdown modal
            countdownModal.classList.remove('active');

            // Show expired overlay
            loadingOverlay.classList.add('hidden');
            errorOverlay.classList.add('hidden');
            expiredOverlay.classList.remove('hidden');
            liveIndicator.style.display = 'none';

            // Update timer
            timerText.textContent = '0:00';
        }

        function showError(message, hint = '') {
            loadingOverlay.classList.add('hidden');
            expiredOverlay.classList.add('hidden');
            errorOverlay.classList.remove('hidden');
            document.getElementById('errorMessage').textContent = message;

            const errorHint = document.getElementById('errorHint');
            if (hint) {
                errorHint.textContent = hint;
                errorHint.style.display = 'block';
            } else {
                // Auto-generate hint based on message
                if (message.includes('invalid') || message.includes('Invalid')) {
                    errorHint.textContent = 'Please check if the share link is correct or request a new one from the camera owner.';
                } else if (message.includes('expired')) {
                    errorHint.textContent = 'The viewing time for this share link has ended. Please request a new share link.';
                } else if (message.includes('not active') || message.includes('not currently')) {
                    errorHint.textContent = 'The camera may be offline. Please try again later or contact the administrator.';
                } else if (message.includes('Network')) {
                    errorHint.textContent = 'Check your internet connection and refresh the page.';
                } else {
                    errorHint.textContent = '';
                    errorHint.style.display = 'none';
                }

                if (errorHint.textContent) {
                    errorHint.style.display = 'block';
                }
            }
        }

        function setupFullscreen() {
            fullscreenBtn.addEventListener('click', function() {
                if (!document.fullscreenElement) {
                    streamContainer.requestFullscreen().catch(err => {
                        console.log('Fullscreen error:', err);
                    });
                } else {
                    document.exitFullscreen();
                }
            });

            document.addEventListener('fullscreenchange', function() {
                if (document.fullscreenElement) {
                    fullscreenBtn.innerHTML = `
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    `;
                } else {
                    fullscreenBtn.innerHTML = `
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                    `;
                }
            });
        }

        // Clean up on page unload
        window.addEventListener('beforeunload', function() {
            if (websocket) {
                websocket.close();
            }
            if (timerInterval) {
                clearInterval(timerInterval);
            }
        });
    </script>
</body>
</html>
