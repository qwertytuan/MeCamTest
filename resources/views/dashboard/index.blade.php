@extends('layouts.app')

@section('title', 'My Cameras - Camera Hub')
@section('styles')
<style>
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .dashboard-title h1 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }

    .dashboard-title p {
        color: var(--text-secondary);
        font-size: 0.95rem;
    }

    .filter-controls {
        display: flex;
        gap: 1rem;
        align-items: center;
    }

    .search-box {
        position: relative;
        width: 300px;
    }

    .search-box input {
        width: 100%;
        padding: 0.625rem 1rem 0.625rem 2.5rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 0.9rem;
    }

    .search-box svg {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        color: var(--text-secondary);
    }

    .camera-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .camera-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s;
        box-shadow: var(--shadow-sm);
    }

    .camera-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .camera-preview {
        position: relative;
        width: 100%;
        height: 240px;
        background: #1a202c;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .camera-preview canvas {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .camera-preview.no-stream {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    }

    .camera-preview-placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        color: #718096;
    }

    .camera-preview-placeholder svg {
        width: 48px;
        height: 48px;
        opacity: 0.5;
    }

    .camera-status {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        display: flex;
        gap: 0.5rem;
    }

    .status-badge {
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        backdrop-filter: blur(10px);
    }

    .status-badge.active {
        background: rgba(72, 187, 120, 0.9);
        color: white;
    }

    .status-badge.inactive {
        background: rgba(113, 128, 150, 0.9);
        color: white;
    }

    .camera-info {
        padding: 1.25rem;
    }

    .camera-name {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .camera-details {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    .camera-detail-item {
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .camera-detail-item svg {
        width: 16px;
        height: 16px;
    }

    .camera-location {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .camera-location svg {
        width: 16px;
        height: 16px;
    }

    .camera-actions {
        display: flex;
        gap: 0.5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
    }

    .play-btn {
        flex: 1;
        background: var(--accent);
        color: white;
        border: none;
        padding: 0.75rem;
        border-radius: 0 0 0 12px;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: background 0.3s;
    }

    .play-btn:hover:not(:disabled) {
        background: #2563eb;
    }

    .play-btn:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--bg-card);
        border-radius: 12px;
        border: 2px dashed var(--border);
    }

    .empty-state svg {
        width: 64px;
        height: 64px;
        color: var(--text-secondary);
        margin-bottom: 1.5rem;
    }

    .empty-state h3 {
        font-size: 1.25rem;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: var(--text-secondary);
        margin-bottom: 1.5rem;
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .loading-content {
        background: var(--bg-card);
        padding: 2rem;
        border-radius: 12px;
        text-align: center;
    }

    .loading-spinner {
        border: 4px solid var(--border);
        border-top: 4px solid var(--accent);
        border-radius: 50%;
        width: 48px;
        height: 48px;
        animation: spin 1s linear infinite;
        margin: 0 auto 1rem;
    }

    @media (max-width: 768px) {
        .camera-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .filter-controls {
            width: 100%;
            flex-direction: column;
        }

        .search-box {
            width: 100%;
        }
    }
    </style>
@endsection

@section('content')
<div class="container">
    <div class="dashboard-header">
        <div class="dashboard-title">
            <h1>My Cameras</h1>
            <p>View and manage your camera streams</p>
        </div>

        <div class="filter-controls">
            <div class="search-box">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="searchInput" placeholder="Search cameras...">
            </div>

            <select id="statusFilter" class="form-input" style="width: 150px;">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    <div id="alertContainer"></div>

    <div id="cameraGrid" class="camera-grid">
        <!-- Cameras will be loaded here -->
    </div>

    <div id="emptyState" class="empty-state" style="display: none;">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <h3>No Cameras Available</h3>
        <p>You don't have access to any cameras yet.</p>
    </div>
</div>

<div id="loadingOverlay" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>Loading cameras...</p>
    </div>
</div>
@endsection
@section('scripts')
<script>
    let cameras = [];
    let activeStreams = {};

    document.addEventListener('DOMContentLoaded', function() {
        loadCameras();

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', filterCameras);
        document.getElementById('statusFilter').addEventListener('change', filterCameras);
    });

    async function loadCameras() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.style.display = 'flex';

        try {
            const { response, data } = await apiCall('/cameras');

            if (response.ok) {
                cameras = data.data || data;
                renderCameras(cameras);
            } else {
                showAlert('Failed to load cameras', 'error');
            }
        } catch (error) {
            showAlert('Error loading cameras', 'error');
        } finally {
            loadingOverlay.style.display = 'none';
        }
    }

    function renderCameras(camerasToRender) {
        console.log('Rendering cameras:', camerasToRender);
        const cameraGrid = document.getElementById('cameraGrid');
        const emptyState = document.getElementById('emptyState');

        if (camerasToRender.length === 0) {
            cameraGrid.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }

        cameraGrid.style.display = 'grid';
        emptyState.style.display = 'none';

        // Generate HTML for all cameras
        cameraGrid.innerHTML = camerasToRender.map(camera => `
            <div class="camera-card">
                <div class="camera-preview ${!camera.is_active ? 'no-stream' : ''}">
                    <canvas id="canvas-${camera.id}" width="640" height="480"></canvas>
                    ${!camera.is_active ? `
                        <div class="camera-preview-placeholder">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 48px; height: 48px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <p>Camera Offline</p>
                        </div>
                    ` : ''}
                </div>

                <div class="camera-info">
                    <div class="camera-status">
                        <h3 class="camera-name">${camera.name}</h3>
                        <span class="status-badge ${camera.is_active ? 'active' : 'inactive'}">
                            ${camera.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </div>

                    <p class="camera-location">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 14px; height: 14px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        ${camera.location || 'Unknown location'}
                    </p>

                    <div class="camera-details">
                        <div class="camera-detail-item">
                            <span>Resolution:</span>
                            <strong>${camera.resolution || 'N/A'}</strong>
                        </div>
                        <div class="camera-detail-item">
                            <span>FPS:</span>
                            <strong>${camera.frame_rate || 'N/A'}</strong>
                        </div>
                    </div>
                </div>

                <div class="camera-actions">
                    <button class="play-btn" id="play-btn-${camera.id}" onclick="toggleStream(${camera.id}, this)" ${!camera.is_active || !camera.websocket_url ? 'disabled' : ''}>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Play Stream
                    </button>
                </div>
            </div>
        `).join('');
    }

    function toggleStream(cameraId, button) {
        // Check if stream is already active
        if (activeStreams[cameraId]) {
            // Stop stream
            const streamState = activeStreams[cameraId];
            if (streamState.ws) {
                streamState.ws.close();
            }
            delete activeStreams[cameraId];

            // Update button
            button.innerHTML = `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Play Stream
            `;

            // Clear canvas
            const canvas = document.getElementById(`canvas-${cameraId}`);
            if (canvas) {
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
            return;
        }

        // Start stream
        const camera = cameras.find(c => c.id === cameraId);
        if (!camera || !camera.websocket_url) {
            showAlert('Stream URL not available', 'error');
            return;
        }

        const canvas = document.getElementById(`canvas-${cameraId}`);
        if (!canvas) {
            showAlert('Canvas not found', 'error');
            return;
        }

        const ctx = canvas.getContext('2d');
        const streamUrl = camera.websocket_url;
        console.log(`Connecting to JPEG stream for camera ${cameraId}:`, streamUrl);

        try {
            const ws = new WebSocket(streamUrl);
            ws.binaryType = 'arraybuffer';

            let frameCount = 0;
            let lastFrameTime = Date.now();

            // Track stream state
            const streamState = {
                ws: ws,
                canvas: canvas,
                ctx: ctx,
                frameCount: 0
            };

            ws.onopen = () => {
                console.log(`Connected to JPEG stream for camera ${cameraId}`);
                showAlert('Stream connected successfully', 'success');
            };

            ws.onmessage = (event) => {
                try {
                    // Use createImageBitmap for better memory management (no Blob URL needed)
                    const blob = new Blob([event.data], { type: 'image/jpeg' });

                    createImageBitmap(blob).then(imageBitmap => {
                        // Set canvas size on first frame
                        if (canvas.width === 0 || canvas.height === 0) {
                            canvas.width = imageBitmap.width;
                            canvas.height = imageBitmap.height;
                        }

                        // Draw imageBitmap to canvas
                        ctx.drawImage(imageBitmap, 0, 0, canvas.width, canvas.height);

                        // Immediately close bitmap to free memory
                        imageBitmap.close();

                        frameCount++;

                        // Log FPS every 100 frames
                        if (frameCount % 100 === 0) {
                            const now = Date.now();
                            const fps = (100 / ((now - lastFrameTime) / 1000)).toFixed(2);
                            console.log(`Camera ${cameraId} - FPS: ${fps}, Frames: ${frameCount}`);
                            lastFrameTime = now;
                        }
                    }).catch(error => {
                        console.error(`Failed to create ImageBitmap for camera ${cameraId}:`, error);
                    });

                    // Blob will be garbage collected automatically
                } catch (error) {
                    console.error(`Error processing JPEG data for camera ${cameraId}:`, error);
                }
            };

            ws.onerror = (error) => {
                console.error(`WebSocket error for camera ${cameraId}:`, error);
                showAlert('Stream connection error', 'error');
            };

            ws.onclose = () => {
                console.log(`Connection closed for camera ${cameraId}`);

                // Clear canvas
                if (ctx && canvas) {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }

                delete activeStreams[cameraId];

                // Update button
                button.innerHTML = `
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Play Stream
                `;
            };

            activeStreams[cameraId] = streamState;

            // Update button to "Stop"
            button.innerHTML = `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Stop Stream
            `;
        } catch (error) {
            showAlert('Failed to start stream: ' + error.message, 'error');
            console.error('Error starting stream:', error);
        }
    }


    function filterCameras() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;

        let filtered = cameras.filter(camera => {
            const matchesSearch = camera.name.toLowerCase().includes(searchTerm) ||
                                (camera.location && camera.location.toLowerCase().includes(searchTerm));

            const matchesStatus = !statusFilter ||
                                (statusFilter === 'active' && camera.is_active) ||
                                (statusFilter === 'inactive' && !camera.is_active);

            return matchesSearch && matchesStatus;
        });

        renderCameras(filtered);
    }

    function viewCameraDetails(cameraId) {
        const camera = cameras.find(c => c.id === cameraId);
        if (camera) {
            alert(`Camera Details:\n\nName: ${camera.name}\nLocation: ${camera.location || 'N/A'}\nResolution: ${camera.resolution || 'N/A'}\nFrame Rate: ${camera.frame_rate || 'N/A'}\nStatus: ${camera.is_active ? 'Active' : 'Inactive'}\nSource: ${camera.rtsp_url || 'N/A'}`);
        }
    }

    function showAlert(message, type = 'error') {
        const alertContainer = document.getElementById('alertContainer');
        alertContainer.innerHTML = `
        // modal alert
        <div class="alert ${type === 'success' ? 'alert-success' : 'alert-error'}" role="alert" style="position: fixed; top: 1rem; right: 1rem; z-index: 10000; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: var(--shadow-md); background-color: ${type === 'success' ? '#38a169' : '#e53e3e'}; color: white;">
            ${message}
        </div>
        `;

        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 10000);
    }

    // Clean up on page unload
    window.addEventListener('beforeunload', () => {
        console.log('Cleaning up all active streams before page unload');
        Object.keys(activeStreams).forEach(cameraId => {
            const stream = activeStreams[cameraId];

            // Close WebSocket
            if (stream.ws) {
                stream.ws.close();
            }


            // Clear canvas
            if (stream.canvas && stream.ctx) {
                stream.ctx.clearRect(0, 0, stream.canvas.width, stream.canvas.height);
            }
        });

        // Clear all streams
        activeStreams = {};
    });

</script>
@endsection
