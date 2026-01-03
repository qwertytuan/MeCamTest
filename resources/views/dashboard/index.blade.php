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
        grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
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

    .camera-card.detection-active {
        border-color: #f56565;
        box-shadow: 0 0 20px rgba(245, 101, 101, 0.3);
    }

    .camera-preview {
        position: relative;
        width: 100%;
        height: 280px;
        background: #1a202c;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        cursor: pointer;
    }

    .camera-preview canvas {
        width: 100%;
        height: 100%;
        object-fit: contain;
        position: relative;
        z-index: 2;
    }

    .camera-preview.streaming canvas {
        z-index: 10;
    }

    .camera-preview.no-stream {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    }

    .camera-thumbnail {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0;
        transition: opacity 0.3s;
        z-index: 1;
    }

    .camera-thumbnail.loaded {
        opacity: 1;
    }

    .camera-preview.streaming .camera-thumbnail,
    .camera-preview.streaming .camera-thumbnail-placeholder {
        opacity: 0;
        z-index: 0;
    }

    .camera-thumbnail-placeholder {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: #718096;
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        z-index: 1;
    }

    .camera-thumbnail-placeholder svg {
        width: 48px;
        height: 48px;
        opacity: 0.5;
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

    /* Detection Alert Overlay */
    .detection-alert-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 0, 0, 0.15);
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 1rem;
        z-index: 15;
    }

    .detection-alert-overlay.active {
        opacity: 1;
        animation: pulse-border 1s ease-in-out infinite;
    }

    @keyframes pulse-border {
        0%, 100% { background: rgba(255, 0, 0, 0.1); }
        50% { background: rgba(255, 0, 0, 0.25); }
    }

    .detection-badge {
        background: rgba(220, 38, 38, 0.95);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        animation: bounce 0.5s ease-in-out;
    }

    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    .detection-badge svg {
        width: 18px;
        height: 18px;
    }

    /* Recording indicator */
    .recording-indicator {
        position: absolute;
        top: 0.75rem;
        left: 0.75rem;
        background: rgba(220, 38, 38, 0.9);
        color: white;
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.375rem;
        opacity: 0;
        transition: opacity 0.3s;
        z-index: 16;
    }

    .recording-indicator.active {
        opacity: 1;
    }

    .recording-indicator .rec-dot {
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

    .camera-status {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        display: flex;
        gap: 0.5rem;
        z-index: 16;
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

    /* Camera Controls Toolbar */
    .camera-controls-toolbar {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
        padding: 2rem 0.75rem 0.75rem;
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        opacity: 0;
        transition: opacity 0.3s;
        z-index: 16;
    }

    .camera-preview:hover .camera-controls-toolbar {
        opacity: 1;
    }

    .camera-controls-toolbar.always-visible {
        opacity: 1;
    }

    .control-btn {
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        backdrop-filter: blur(10px);
    }

    .control-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    .control-btn:active {
        transform: scale(0.95);
    }

    .control-btn svg {
        width: 18px;
        height: 18px;
    }

    .control-btn.danger:hover {
        background: rgba(220, 38, 38, 0.8);
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
        border-radius: 8px;
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

    .recordings-btn {
        flex: 1;
        background: var(--bg-main);
        color: var(--text-primary);
        border: 1px solid var(--border);
        padding: 0.75rem;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: all 0.3s;
    }

    .recordings-btn:hover {
        background: var(--border);
    }

    /* Fullscreen Modal */
    .fullscreen-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.95);
        z-index: 10000;
        display: none;
        flex-direction: column;
    }

    .fullscreen-modal.active {
        display: flex;
    }

    .fullscreen-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        background: rgba(0, 0, 0, 0.8);
        color: white;
    }

    .fullscreen-header h3 {
        font-size: 1.25rem;
        font-weight: 600;
    }

    .fullscreen-header-controls {
        display: flex;
        gap: 0.75rem;
    }

    .fullscreen-content {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .fullscreen-content canvas {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .fullscreen-controls {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
        padding: 3rem 2rem 1.5rem;
        display: flex;
        justify-content: center;
        gap: 1rem;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .fullscreen-content:hover .fullscreen-controls {
        opacity: 1;
    }

    .fullscreen-btn {
        padding: 0.75rem 1.25rem;
        border: none;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.2s;
        backdrop-filter: blur(10px);
    }

    .fullscreen-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.05);
    }

    .fullscreen-btn svg {
        width: 20px;
        height: 20px;
    }

    /* Detection Alert in Fullscreen */
    .fullscreen-detection-alert {
        position: absolute;
        top: 1rem;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(220, 38, 38, 0.95);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        display: none;
        align-items: center;
        gap: 0.75rem;
        animation: slideDown 0.3s ease-out;
    }

    .fullscreen-detection-alert.active {
        display: flex;
    }

    @keyframes slideDown {
        from { transform: translateX(-50%) translateY(-20px); opacity: 0; }
        to { transform: translateX(-50%) translateY(0); opacity: 1; }
    }

    /* Recordings Modal */
    .recordings-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .recordings-modal.active {
        display: flex;
    }

    .recordings-modal-content {
        background: var(--bg-card);
        border-radius: 12px;
        width: 100%;
        max-width: 800px;
        max-height: 80vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .recordings-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border);
    }

    .recordings-modal-header h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--text-primary);
    }

    .recordings-modal-body {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }

    .recordings-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .recording-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        background: var(--bg-main);
        border-radius: 8px;
        border: 1px solid var(--border);
        transition: all 0.2s;
    }

    .recording-item:hover {
        border-color: var(--accent);
    }

    .recording-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .recording-icon {
        width: 48px;
        height: 48px;
        background: var(--accent);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .recording-icon svg {
        width: 24px;
        height: 24px;
    }

    .recording-details h4 {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }

    .recording-details p {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    .recording-actions {
        display: flex;
        gap: 0.5rem;
    }

    /* Video Player Modal */
    .video-player-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.9);
        z-index: 10001;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .video-player-modal.active {
        display: flex;
    }

    .video-player-content {
        background: #000;
        border-radius: 12px;
        overflow: hidden;
        max-width: 90vw;
        max-height: 90vh;
    }

    .video-player-content video {
        max-width: 100%;
        max-height: 80vh;
    }

    .video-player-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.8);
        color: white;
    }

    /* Toast Notification */
    .toast-notification {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: var(--bg-card);
        border-radius: 8px;
        padding: 1rem 1.5rem;
        box-shadow: var(--shadow-lg);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transform: translateX(150%);
        transition: transform 0.3s ease-out;
        z-index: 10002;
        border-left: 4px solid var(--accent);
    }

    .toast-notification.active {
        transform: translateX(0);
    }

    .toast-notification.success {
        border-left-color: #48bb78;
    }

    .toast-notification.warning {
        border-left-color: #ed8936;
    }

    .toast-notification.error {
        border-left-color: #f56565;
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

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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

        .fullscreen-controls {
            flex-wrap: wrap;
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

<!-- Fullscreen Modal -->
<div id="fullscreenModal" class="fullscreen-modal">
    <div class="fullscreen-header">
        <h3 id="fullscreenTitle">Camera Stream</h3>
        <div class="fullscreen-header-controls">
            <button class="control-btn" onclick="captureFrame()" title="Capture Frame">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>
            <button class="control-btn danger" onclick="closeFullscreen()" title="Close">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
    <div class="fullscreen-content" ondblclick="closeFullscreen()">
        <canvas id="fullscreenCanvas"></canvas>
        <div id="fullscreenDetectionAlert" class="fullscreen-detection-alert">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span id="fullscreenAlertText">Detection Alert</span>
        </div>
        <div class="fullscreen-controls">
            <button class="fullscreen-btn" onclick="captureFrame()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Capture Frame
            </button>
            <button class="fullscreen-btn" onclick="openRecordingsFromFullscreen()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                View Recordings
            </button>
            <button class="fullscreen-btn" onclick="closeFullscreen()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Exit Fullscreen
            </button>
        </div>
    </div>
</div>

<!-- Recordings Modal -->
<div id="recordingsModal" class="recordings-modal">
    <div class="recordings-modal-content">
        <div class="recordings-modal-header">
            <h3 id="recordingsModalTitle">Camera Recordings</h3>
            <button class="control-btn" onclick="closeRecordingsModal()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="recordings-modal-body">
            <div id="recordingsList" class="recordings-list">
                <!-- Recordings will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Video Player Modal -->
<div id="videoPlayerModal" class="video-player-modal">
    <div class="video-player-content">
        <div class="video-player-header">
            <span id="videoPlayerTitle">Recording Playback</span>
            <button class="control-btn" onclick="closeVideoPlayer()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <video id="recordingVideo" controls autoplay>
            Your browser does not support the video tag.
        </video>
    </div>
</div>

<!-- Toast Notification -->
<div id="toastNotification" class="toast-notification">
    <svg id="toastIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 24px; height: 24px;">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span id="toastMessage">Notification</span>
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
    let fullscreenCameraId = null;
    let currentRecordingsCameraId = null;
    let detectionAlertTimeout = null;

    // Python camera server base URL
    const PYTHON_SERVER_URL = 'http://localhost:5000';

    document.addEventListener('DOMContentLoaded', function() {
        loadCameras();

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', filterCameras);
        document.getElementById('statusFilter').addEventListener('change', filterCameras);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('videoPlayerModal').classList.contains('active')) {
                    closeVideoPlayer();
                } else if (document.getElementById('recordingsModal').classList.contains('active')) {
                    closeRecordingsModal();
                } else if (document.getElementById('fullscreenModal').classList.contains('active')) {
                    closeFullscreen();
                }
            }
            // Capture frame with 'C' key in fullscreen
            if (e.key === 'c' || e.key === 'C') {
                if (document.getElementById('fullscreenModal').classList.contains('active')) {
                    captureFrame();
                }
            }
        });
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
                showToast('Failed to load cameras', 'error');
            }
        } catch (error) {
            showToast('Error loading cameras', 'error');
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
            <div class="camera-card" id="camera-card-${camera.id}">
                <div class="camera-preview ${!camera.is_active ? 'no-stream' : ''}" onclick="openFullscreen(${camera.id})">
                    <canvas id="canvas-${camera.id}" width="640" height="480"></canvas>

                    <!-- Thumbnail Image - shows when stream not active -->
                    ${camera.thumbnail_url ? `
                        <img id="thumbnail-${camera.id}"
                             class="camera-thumbnail"
                             src="${camera.thumbnail_url}"
                             alt="${camera.name} thumbnail"
                             onerror="this.style.display='none'; document.getElementById('thumbnail-placeholder-${camera.id}').style.display='flex';"
                             onload="this.classList.add('loaded');">
                        <div id="thumbnail-placeholder-${camera.id}" class="camera-thumbnail-placeholder" style="display: none;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>No Preview</span>
                        </div>
                    ` : `
                        <div id="thumbnail-placeholder-${camera.id}" class="camera-thumbnail-placeholder">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>No Preview</span>
                        </div>
                    `}

                    ${!camera.is_active ? `
                        <div class="camera-preview-placeholder">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 48px; height: 48px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <p>Camera Offline</p>
                        </div>
                    ` : ''}

                    <!-- Detection Alert Overlay -->
                    <div id="detection-overlay-${camera.id}" class="detection-alert-overlay">
                        <div class="detection-badge">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span id="detection-text-${camera.id}">Motion Detected</span>
                        </div>
                    </div>

                    <!-- Recording Indicator -->
                    <div id="recording-indicator-${camera.id}" class="recording-indicator">
                        <span class="rec-dot"></span>
                        REC
                    </div>

                    <div class="camera-status">
                        <span class="status-badge ${camera.is_active ? 'active' : 'inactive'}">
                            ${camera.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </div>

                    <!-- Camera Controls Toolbar -->
                    <div class="camera-controls-toolbar" id="controls-${camera.id}">
                        <button class="control-btn" onclick="event.stopPropagation(); openFullscreen(${camera.id})" title="Fullscreen">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                            </svg>
                        </button>
                        <button class="control-btn" onclick="event.stopPropagation(); captureFrameForCamera(${camera.id})" title="Capture Frame">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                        <button class="control-btn" onclick="event.stopPropagation(); openRecordings(${camera.id})" title="View Recordings">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="camera-info">
                    <div class="camera-status" style="position: static; margin-bottom: 0.5rem;">
                        <h3 class="camera-name">${camera.name}</h3>
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
                    <button class="recordings-btn" onclick="openRecordings(${camera.id})">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        Recordings
                    </button>
                </div>
            </div>
        `).join('');
    }

    function toggleStream(cameraId, button) {
        // Check if stream is already active
        if (activeStreams[cameraId]) {
            stopStream(cameraId, button);
            return;
        }

        // Start stream
        const camera = cameras.find(c => c.id === cameraId);
        if (!camera || !camera.websocket_url) {
            showToast('Stream URL not available', 'error');
            return;
        }

        const canvas = document.getElementById(`canvas-${cameraId}`);
        if (!canvas) {
            showToast('Canvas not found', 'error');
            return;
        }

        const ctx = canvas.getContext('2d');
        const streamUrl = camera.websocket_url;
        console.log(`Connecting to stream for camera ${cameraId}:`, streamUrl);

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
                frameCount: 0,
                cameraId: cameraId,
                camera: camera
            };

            ws.onopen = () => {
                console.log(`Connected to stream for camera ${cameraId}`);
                showToast('Stream connected successfully', 'success');
            };

            ws.onmessage = (event) => {
                try {
                    // Check if it's a text message (detection metadata)
                    if (typeof event.data === 'string') {
                        handleDetectionMessage(cameraId, JSON.parse(event.data));
                        return;
                    }

                    // Binary data - JPEG frame
                    const blob = new Blob([event.data], { type: 'image/jpeg' });

                    createImageBitmap(blob).then(imageBitmap => {
                        // Set canvas size on first frame
                        if (canvas.width !== imageBitmap.width || canvas.height !== imageBitmap.height) {
                            canvas.width = imageBitmap.width;
                            canvas.height = imageBitmap.height;
                        }

                        // Draw imageBitmap to canvas
                        ctx.drawImage(imageBitmap, 0, 0, canvas.width, canvas.height);

                        // Also update fullscreen canvas if this camera is in fullscreen
                        if (fullscreenCameraId === cameraId) {
                            const fullscreenCanvas = document.getElementById('fullscreenCanvas');
                            const fullscreenCtx = fullscreenCanvas.getContext('2d');
                            if (fullscreenCanvas.width !== imageBitmap.width) {
                                fullscreenCanvas.width = imageBitmap.width;
                                fullscreenCanvas.height = imageBitmap.height;
                            }
                            fullscreenCtx.drawImage(imageBitmap, 0, 0, fullscreenCanvas.width, fullscreenCanvas.height);
                        }

                        // Immediately close bitmap to free memory
                        imageBitmap.close();

                        frameCount++;
                        streamState.frameCount = frameCount;

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

                } catch (error) {
                    console.error(`Error processing data for camera ${cameraId}:`, error);
                }
            };

            ws.onerror = (error) => {
                console.error(`WebSocket error for camera ${cameraId}:`, error);
                showToast('Stream connection error', 'error');
            };

            ws.onclose = () => {
                console.log(`Connection closed for camera ${cameraId}`);
                stopStream(cameraId, button);
            };

            activeStreams[cameraId] = streamState;

            // Update button to "Stop"
            button.innerHTML = `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Stop Stream
            `;
            button.classList.add('active');

            // Add streaming class to preview
            const preview = document.getElementById(`camera-card-${cameraId}`)?.querySelector('.camera-preview');
            if (preview) {
                preview.classList.add('streaming');
            }

            // Show controls toolbar
            const controlsToolbar = document.getElementById(`controls-${cameraId}`);
            if (controlsToolbar) {
                controlsToolbar.classList.add('always-visible');
            }

        } catch (error) {
            showToast('Failed to start stream: ' + error.message, 'error');
            console.error('Error starting stream:', error);
        }
    }

    function stopStream(cameraId, button) {
        const streamState = activeStreams[cameraId];
        if (streamState) {
            if (streamState.ws) {
                streamState.ws.close();
            }
            delete activeStreams[cameraId];
        }

        // Update button
        if (button) {
            button.innerHTML = `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Play Stream
            `;
            button.classList.remove('active');
        }

        // Clear canvas
        const canvas = document.getElementById(`canvas-${cameraId}`);
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        // Remove streaming class from preview
        const preview = document.getElementById(`camera-card-${cameraId}`)?.querySelector('.camera-preview');
        if (preview) {
            preview.classList.remove('streaming');
        }

        // Hide controls toolbar
        const controlsToolbar = document.getElementById(`controls-${cameraId}`);
        if (controlsToolbar) {
            controlsToolbar.classList.remove('always-visible');
        }

        // Remove detection overlay
        const detectionOverlay = document.getElementById(`detection-overlay-${cameraId}`);
        if (detectionOverlay) {
            detectionOverlay.classList.remove('active');
        }

        // Remove recording indicator
        const recordingIndicator = document.getElementById(`recording-indicator-${cameraId}`);
        if (recordingIndicator) {
            recordingIndicator.classList.remove('active');
        }
    }

    function handleDetectionMessage(cameraId, data) {
        console.log(`Detection event for camera ${cameraId}:`, data);

        if (data.type === 'detection') {
            // Show detection alert overlay
            const detectionOverlay = document.getElementById(`detection-overlay-${cameraId}`);
            const detectionText = document.getElementById(`detection-text-${cameraId}`);
            const cameraCard = document.getElementById(`camera-card-${cameraId}`);

            if (detectionOverlay && detectionText) {
                let alertText = 'Motion Detected';
                if (data.detection_type === 'HUMAN' || data.detection_type === 'MOTION_HUMAN') {
                    alertText = `Human Detected${data.human_count > 1 ? ` (${data.human_count})` : ''}`;
                } else if (data.detection_type === 'MOTION') {
                    alertText = 'Motion Detected';
                }

                detectionText.textContent = alertText;
                detectionOverlay.classList.add('active');
                cameraCard?.classList.add('detection-active');

                // Update fullscreen alert if in fullscreen mode
                if (fullscreenCameraId === cameraId) {
                    const fullscreenAlert = document.getElementById('fullscreenDetectionAlert');
                    const fullscreenAlertText = document.getElementById('fullscreenAlertText');
                    if (fullscreenAlert && fullscreenAlertText) {
                        fullscreenAlertText.textContent = alertText;
                        fullscreenAlert.classList.add('active');
                    }
                }

                // Clear previous timeout
                if (detectionAlertTimeout) {
                    clearTimeout(detectionAlertTimeout);
                }

                // Hide alert after 3 seconds
                detectionAlertTimeout = setTimeout(() => {
                    detectionOverlay.classList.remove('active');
                    cameraCard?.classList.remove('detection-active');
                    if (fullscreenCameraId === cameraId) {
                        document.getElementById('fullscreenDetectionAlert')?.classList.remove('active');
                    }
                }, 3000);
            }

            // Show recording indicator
            if (data.recording_active) {
                const recordingIndicator = document.getElementById(`recording-indicator-${cameraId}`);
                if (recordingIndicator) {
                    recordingIndicator.classList.add('active');
                }
            }

            // Play alert sound (optional)
            // playAlertSound();
        }
    }

    // Fullscreen functions
    function openFullscreen(cameraId) {
        if (!activeStreams[cameraId]) {
            showToast('Please start the stream first', 'warning');
            return;
        }

        fullscreenCameraId = cameraId;
        const camera = cameras.find(c => c.id === cameraId);

        document.getElementById('fullscreenTitle').textContent = camera?.name || 'Camera Stream';
        document.getElementById('fullscreenModal').classList.add('active');

        // Copy current frame to fullscreen canvas
        const sourceCanvas = document.getElementById(`canvas-${cameraId}`);
        const fullscreenCanvas = document.getElementById('fullscreenCanvas');
        if (sourceCanvas && fullscreenCanvas) {
            fullscreenCanvas.width = sourceCanvas.width;
            fullscreenCanvas.height = sourceCanvas.height;
            const ctx = fullscreenCanvas.getContext('2d');
            ctx.drawImage(sourceCanvas, 0, 0);
        }
    }

    function closeFullscreen() {
        fullscreenCameraId = null;
        document.getElementById('fullscreenModal').classList.remove('active');
        document.getElementById('fullscreenDetectionAlert').classList.remove('active');
    }

    // Capture frame functions
    function captureFrame() {
        if (!fullscreenCameraId) return;
        captureFrameForCamera(fullscreenCameraId);
    }

    function captureFrameForCamera(cameraId) {
        const canvas = document.getElementById(`canvas-${cameraId}`);
        if (!canvas) {
            showToast('No stream available to capture', 'error');
            return;
        }

        try {
            // Create a temporary canvas to ensure we capture the current frame
            const tempCanvas = document.createElement('canvas');
            tempCanvas.width = canvas.width;
            tempCanvas.height = canvas.height;
            const tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(canvas, 0, 0);

            // Convert to blob and download
            tempCanvas.toBlob((blob) => {
                if (blob) {
                    const camera = cameras.find(c => c.id === cameraId);
                    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
                    const filename = `${camera?.name || 'camera'}_${timestamp}.jpg`;

                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);

                    showToast('Frame captured and saved!', 'success');

                    // Also save as thumbnail on server
                    saveThumbnailToServer(cameraId);
                }
            }, 'image/jpeg', 0.95);
        } catch (error) {
            console.error('Error capturing frame:', error);
            showToast('Failed to capture frame', 'error');
        }
    }

    async function saveThumbnailToServer(cameraId) {
        try {
            const { response, data } = await apiCall(`/cameras/${cameraId}/thumbnail/capture`, {
                method: 'POST'
            });

            if (response.ok && data.success) {
                // Update the camera thumbnail in local cache
                const camera = cameras.find(c => c.id === cameraId);
                if (camera && data.thumbnail) {
                    const thumbnailUrl = `${PYTHON_SERVER_URL}/api/thumbnail/${cameraId}/${data.thumbnail.file_name}`;
                    camera.thumbnail_url = thumbnailUrl;

                    // Update thumbnail image if exists
                    const thumbnailImg = document.getElementById(`thumbnail-${cameraId}`);
                    if (thumbnailImg) {
                        thumbnailImg.src = thumbnailUrl;
                        thumbnailImg.classList.remove('loaded');
                        thumbnailImg.onload = () => thumbnailImg.classList.add('loaded');
                    }

                    showToast('Thumbnail saved to server', 'success');
                }
            }
        } catch (error) {
            console.error('Error saving thumbnail to server:', error);
        }
    }

    // Recordings functions
    function openRecordings(cameraId) {
        currentRecordingsCameraId = cameraId;
        const camera = cameras.find(c => c.id === cameraId);
        document.getElementById('recordingsModalTitle').textContent = `${camera?.name || 'Camera'} - Recordings`;
        document.getElementById('recordingsModal').classList.add('active');
        loadRecordings(cameraId);
    }

    function openRecordingsFromFullscreen() {
        if (fullscreenCameraId) {
            openRecordings(fullscreenCameraId);
        }
    }

    function closeRecordingsModal() {
        document.getElementById('recordingsModal').classList.remove('active');
        currentRecordingsCameraId = null;
    }

    async function loadRecordings(cameraId) {
        const recordingsList = document.getElementById('recordingsList');
        recordingsList.innerHTML = '<div class="loading-spinner" style="margin: 2rem auto;"></div>';

        try {
            // Fetch recordings from Python server
            const response = await fetch(`${PYTHON_SERVER_URL}/api/recordings/${cameraId}`);
            const data = await response.json();

            if (data.success && data.recordings && data.recordings.length > 0) {
                recordingsList.innerHTML = data.recordings.map(recording => {
                    const date = new Date(recording.created_at * 1000);
                    const formattedDate = date.toLocaleString();
                    const fileSize = formatFileSize(recording.file_size);

                    return `
                        <div class="recording-item">
                            <div class="recording-info">
                                <div class="recording-icon">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="recording-details">
                                    <h4>${recording.filename}</h4>
                                    <p>${formattedDate} • ${fileSize}</p>
                                </div>
                            </div>
                            <div class="recording-actions">
                                <button class="control-btn" onclick="playRecording('${recording.download_url}')" title="Play">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </button>
                                <button class="control-btn" onclick="downloadRecording('${recording.download_url}', '${recording.filename}')" title="Download">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                recordingsList.innerHTML = `
                    <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 48px; height: 48px; margin-bottom: 1rem; opacity: 0.5;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <p>No recordings available for this camera</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading recordings:', error);
            recordingsList.innerHTML = `
                <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                    <p>Failed to load recordings. Make sure the camera server is running.</p>
                </div>
            `;
        }
    }

    function playRecording(url) {
        const fullUrl = `${PYTHON_SERVER_URL}${url}`;
        document.getElementById('recordingVideo').src = fullUrl;
        document.getElementById('videoPlayerTitle').textContent = 'Recording Playback';
        document.getElementById('videoPlayerModal').classList.add('active');
    }

    function closeVideoPlayer() {
        const video = document.getElementById('recordingVideo');
        video.pause();
        video.src = '';
        document.getElementById('videoPlayerModal').classList.remove('active');
    }

    function downloadRecording(url, filename) {
        const fullUrl = `${PYTHON_SERVER_URL}${url}`;
        const a = document.createElement('a');
        a.href = fullUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        showToast('Download started', 'success');
    }

    // Utility functions
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toastNotification');
        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');

        toastMessage.textContent = message;
        toast.className = `toast-notification ${type}`;

        // Update icon based on type
        if (type === 'success') {
            toastIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        } else if (type === 'error') {
            toastIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        } else if (type === 'warning') {
            toastIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>';
        }

        toast.classList.add('active');

        setTimeout(() => {
            toast.classList.remove('active');
        }, 3000);
    }

    function showAlert(message, type = 'error') {
        showToast(message, type);
    }

    // Clean up on page unload
    window.addEventListener('beforeunload', () => {
        console.log('Cleaning up all active streams before page unload');
        Object.keys(activeStreams).forEach(cameraId => {
            const stream = activeStreams[cameraId];
            if (stream.ws) {
                stream.ws.close();
            }
            if (stream.canvas && stream.ctx) {
                stream.ctx.clearRect(0, 0, stream.canvas.width, stream.canvas.height);
            }
        });
        activeStreams = {};
    });

</script>
@endsection
