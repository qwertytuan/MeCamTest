@extends('layouts.app')

@section('title', 'Recordings - Camera Hub')

@section('styles')
<style>
    .recordings-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .recordings-title h1 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.25rem;
    }

    .recordings-title p {
        color: var(--text-secondary);
        font-size: 0.95rem;
    }

    .filter-section {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .filter-group label {
        font-size: 0.8rem;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .filter-select, .filter-input {
        padding: 0.5rem 1rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 0.9rem;
        min-width: 180px;
        background: var(--bg-card);
    }

    .filter-select:focus, .filter-input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-icon svg {
        width: 24px;
        height: 24px;
        color: white;
    }

    .stat-icon.blue { background: var(--accent); }
    .stat-icon.green { background: var(--success); }
    .stat-icon.orange { background: var(--warning); }
    .stat-icon.red { background: var(--danger); }

    .stat-info h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .stat-info p {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    /* Recordings Grid */
    .recordings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
    }

    .recording-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s;
    }

    .recording-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .recording-thumbnail {
        position: relative;
        height: 200px;
        background: #1a202c;
        cursor: pointer;
    }

    .recording-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .recording-thumbnail video {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #000;
    }

    .thumbnail-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: #718096;
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
    }

    .thumbnail-placeholder svg {
        width: 48px;
        height: 48px;
        opacity: 0.5;
    }

    .play-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .recording-thumbnail:hover .play-overlay {
        opacity: 1;
    }

    .play-button {
        width: 64px;
        height: 64px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s;
    }

    .play-button svg {
        width: 28px;
        height: 28px;
        color: var(--primary);
        margin-left: 4px;
    }

    .recording-thumbnail:hover .play-button {
        transform: scale(1.1);
    }

    .duration-badge {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .detection-type-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        padding: 0.25rem 0.75rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .detection-type-badge.motion {
        background: rgba(237, 137, 54, 0.9);
        color: white;
    }

    .detection-type-badge.human {
        background: rgba(245, 101, 101, 0.9);
        color: white;
    }

    .detection-type-badge.motion_human {
        background: rgba(128, 90, 213, 0.9);
        color: white;
    }

    .recording-info {
        padding: 1rem;
    }

    .recording-meta {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }

    .recording-camera {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 1rem;
    }

    .recording-date {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    .recording-details {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    .recording-details span {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .recording-details svg {
        width: 14px;
        height: 14px;
    }

    .recording-actions {
        display: flex;
        gap: 0.5rem;
    }

    .recording-actions .btn {
        flex: 1;
    }

    /* Video Modal */
    .video-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.9);
        z-index: 2000;
        align-items: center;
        justify-content: center;
    }

    .video-modal.active {
        display: flex;
    }

    .video-modal-content {
        position: relative;
        width: 90%;
        max-width: 1200px;
        max-height: 90vh;
    }

    .video-modal-close {
        position: absolute;
        top: -40px;
        right: 0;
        background: none;
        border: none;
        color: white;
        font-size: 2rem;
        cursor: pointer;
        padding: 0.5rem;
        z-index: 10;
    }

    .video-modal-close:hover {
        color: var(--accent);
    }

    .video-modal video {
        width: 100%;
        max-height: 80vh;
        border-radius: 8px;
    }

    .video-modal-info {
        color: white;
        padding: 1rem 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .video-modal-title {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .video-modal-actions {
        display: flex;
        gap: 0.5rem;
    }

    /* Loading State */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        z-index: 1500;
    }

    .loading-overlay.hidden {
        display: none;
    }

    .loading-spinner {
        width: 48px;
        height: 48px;
        border: 4px solid var(--border);
        border-top: 4px solid var(--accent);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
    }

    .empty-state svg {
        width: 80px;
        height: 80px;
        color: var(--text-secondary);
        opacity: 0.5;
        margin-bottom: 1rem;
    }

    .empty-state h2 {
        font-size: 1.5rem;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: var(--text-secondary);
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
    }

    .pagination button {
        padding: 0.5rem 1rem;
        border: 1px solid var(--border);
        background: var(--bg-card);
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .pagination button:hover:not(:disabled) {
        background: var(--bg-main);
    }

    .pagination button.active {
        background: var(--accent);
        color: white;
        border-color: var(--accent);
    }

    .pagination button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .recordings-header {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-section {
            flex-direction: column;
        }

        .filter-select, .filter-input {
            width: 100%;
        }

        .recordings-grid {
            grid-template-columns: 1fr;
        }

        .video-modal-content {
            width: 95%;
        }

        .video-modal-info {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <p>Loading recordings...</p>
    </div>

    <!-- Header -->
    <div class="recordings-header">
        <div class="recordings-title">
            <h1>📹 Recordings</h1>
            <p>View and download camera recordings triggered by motion or human detection</p>
        </div>
        <div class="filter-section">
            <div class="filter-group">
                <label>Camera</label>
                <select class="filter-select" id="cameraFilter">
                    <option value="">All Cameras</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Detection Type</label>
                <select class="filter-select" id="detectionFilter">
                    <option value="">All Types</option>
                    <option value="MOTION">Motion</option>
                    <option value="HUMAN">Human</option>
                    <option value="MOTION_HUMAN">Motion + Human</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Date</label>
                <input type="date" class="filter-input" id="dateFilter">
            </div>
            <button class="btn btn-primary" onclick="loadRecordings()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
                Search
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid" id="statsGrid">
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/>
                    <line x1="7" y1="2" x2="7" y2="22"/>
                    <line x1="17" y1="2" x2="17" y2="22"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                    <line x1="2" y1="7" x2="7" y2="7"/>
                    <line x1="2" y1="17" x2="7" y2="17"/>
                    <line x1="17" y1="17" x2="22" y2="17"/>
                    <line x1="17" y1="7" x2="22" y2="7"/>
                </svg>
            </div>
            <div class="stat-info">
                <h3 id="totalRecordings">0</h3>
                <p>Total Recordings</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div class="stat-info">
                <h3 id="motionRecordings">0</h3>
                <p>Motion Detected</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="stat-info">
                <h3 id="humanRecordings">0</h3>
                <p>Human Detected</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
            </div>
            <div class="stat-info">
                <h3 id="totalSize">0 MB</h3>
                <p>Total Storage</p>
            </div>
        </div>
    </div>

    <!-- Recordings Grid -->
    <div class="recordings-grid" id="recordingsGrid">
        <!-- Recordings will be populated here -->
    </div>

    <!-- Empty State -->
    <div class="empty-state" id="emptyState" style="display: none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"/>
            <line x1="7" y1="2" x2="7" y2="22"/>
            <line x1="17" y1="2" x2="17" y2="22"/>
            <line x1="2" y1="12" x2="22" y2="12"/>
        </svg>
        <h2>No recordings found</h2>
        <p>Recordings will appear here when detection events trigger automatic recording.</p>
    </div>

    <!-- Pagination -->
    <div class="pagination" id="pagination" style="display: none;">
        <!-- Pagination will be populated here -->
    </div>
</div>

<!-- Video Modal -->
<div class="video-modal" id="videoModal">
    <div class="video-modal-content">
        <button class="video-modal-close" onclick="closeVideoModal()">×</button>
        <video id="modalVideo" controls autoplay playsinline>
            Your browser does not support the video tag.
        </video>
        <div class="video-modal-info">
            <div>
                <div class="video-modal-title" id="modalTitle">Recording</div>
                <div id="modalDetails" style="font-size: 0.9rem; opacity: 0.8;"></div>
            </div>
            <div class="video-modal-actions">
                <a href="#" class="btn btn-primary" id="modalDownloadBtn" download>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download
                </a>
                <button class="btn btn-danger" id="modalDeleteBtn" onclick="deleteRecording()" style="display: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        <line x1="10" y1="11" x2="10" y2="17"/>
                        <line x1="14" y1="11" x2="14" y2="17"/>
                    </svg>
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const PYTHON_API = 'http://localhost:5000';
    const LARAVEL_API = '/api';
    let cameras = [];
    let allRecordings = [];
    let currentPage = 1;
    const recordingsPerPage = 12;

    document.addEventListener('DOMContentLoaded', function() {
        // Check authentication
        const token = localStorage.getItem('token');
        if (!token) {
            window.location.href = '{{ url("/login") }}';
            return;
        }

        // Update navbar for recordings page
        updateNavbarForRecordings();

        // Load cameras for filter dropdown
        loadCameras();
    });

    function updateNavbarForRecordings() {
        const navbarMenu = document.getElementById('navbarMenu');
        const user = JSON.parse(localStorage.getItem('user') || 'null');
        if (!user) return;

        const isAdmin = user.is_admin;
        const currentPath = window.location.pathname;

        navbarMenu.innerHTML = `
            <a href="{{ url('/dashboard') }}" class="${currentPath === '/dashboard' ? 'active' : ''}">
                Cameras
            </a>
            <a href="{{ url('/recordings') }}" class="${currentPath === '/recordings' ? 'active' : ''}">
                Recordings
            </a>
            ${isAdmin ? `
            <a href="{{ url('/admin/cameras') }}" class="${currentPath === '/admin/cameras' ? 'active' : ''}">
                Manage Cameras
            </a>
            ` : ''}
            <a href="{{ url('/profile') }}" class="${currentPath === '/profile' ? 'active' : ''}">
                Profile
            </a>
            <div class="user-info">
                <div class="user-avatar" id="navbarAvatar">${user.name.charAt(0).toUpperCase()}</div>
                <span class="user-name">${user.name}</span>
                ${isAdmin ? '<span class="admin-badge">Admin</span>' : ''}
            </div>
            <button onclick="logout()" class="btn btn-secondary btn-sm">Logout</button>
        `;

        if (user.avatar_url) {
            loadNavbarAvatar(user.avatar_url, user.name);
        }
    }

    async function loadCameras() {
        try {
            const { data } = await apiCall('/cameras');
            cameras = data.data || [];

            const cameraFilter = document.getElementById('cameraFilter');
            cameras.forEach(camera => {
                const option = document.createElement('option');
                option.value = camera.id;
                option.textContent = `${camera.name} - ${camera.location}`;
                cameraFilter.appendChild(option);
            });

            // Load recordings after cameras are loaded
            loadRecordings();
        } catch (error) {
            console.error('Error loading cameras:', error);
            showError('Failed to load cameras');
            hideLoading();
        }
    }

    async function loadRecordings() {
        showLoading();
        allRecordings = [];

        const cameraFilter = document.getElementById('cameraFilter').value;
        const detectionFilter = document.getElementById('detectionFilter').value;
        const dateFilter = document.getElementById('dateFilter').value;

        try {
            // Use the unified recordings endpoint
            const { data } = await apiCall('/cameras/recordings');

            if (data.success && data.recordings) {
                allRecordings = data.recordings;

                // Apply camera filter
                if (cameraFilter) {
                    allRecordings = allRecordings.filter(r => r.camera_id == cameraFilter);
                }

                // Apply detection filter
                if (detectionFilter) {
                    allRecordings = allRecordings.filter(r =>
                        r.detection_type === detectionFilter ||
                        (r.filename && r.filename.toLowerCase().includes(detectionFilter.toLowerCase()))
                    );
                }

                // Apply date filter
                if (dateFilter) {
                    const filterDate = new Date(dateFilter).toDateString();
                    allRecordings = allRecordings.filter(r => {
                        const recordingDate = new Date(r.created_at * 1000).toDateString();
                        return recordingDate === filterDate;
                    });
                }

                // Sort by date (newest first)
                allRecordings.sort((a, b) => b.created_at - a.created_at);

                // Update stats
                updateStats(allRecordings);

                // Render recordings
                currentPage = 1;
                renderRecordings(allRecordings);
            } else {
                updateStats([]);
                renderRecordings([]);
            }

        } catch (error) {
            console.error('Error loading recordings:', error);
            showError('Failed to load recordings');
            updateStats([]);
            renderRecordings([]);
        } finally {
            hideLoading();
        }
    }

    function updateStats(recordings) {
        document.getElementById('totalRecordings').textContent = recordings.length;

        const motionCount = recordings.filter(r =>
            r.detection_type === 'MOTION' ||
            (r.filename && r.filename.toLowerCase().includes('motion'))
        ).length;

        const humanCount = recordings.filter(r =>
            r.detection_type === 'HUMAN' ||
            r.detection_type === 'MOTION_HUMAN' ||
            (r.filename && r.filename.toLowerCase().includes('human'))
        ).length;

        document.getElementById('motionRecordings').textContent = motionCount;
        document.getElementById('humanRecordings').textContent = humanCount;

        const totalSize = recordings.reduce((sum, r) => sum + (r.file_size || 0), 0);
        document.getElementById('totalSize').textContent = formatFileSize(totalSize);
    }

    function renderRecordings(recordings) {
        const grid = document.getElementById('recordingsGrid');
        const emptyState = document.getElementById('emptyState');
        const pagination = document.getElementById('pagination');

        if (recordings.length === 0) {
            grid.innerHTML = '';
            emptyState.style.display = 'block';
            pagination.style.display = 'none';
            return;
        }

        emptyState.style.display = 'none';

        // Paginate
        const startIndex = (currentPage - 1) * recordingsPerPage;
        const paginatedRecordings = recordings.slice(startIndex, startIndex + recordingsPerPage);

        grid.innerHTML = paginatedRecordings.map(recording => createRecordingCard(recording)).join('');

        // Render pagination
        const totalPages = Math.ceil(recordings.length / recordingsPerPage);
        if (totalPages > 1) {
            renderPagination(totalPages, recordings);
            pagination.style.display = 'flex';
        } else {
            pagination.style.display = 'none';
        }
    }

    function createRecordingCard(recording) {
        const date = new Date(recording.created_at * 1000);
        const formattedDate = date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        const detectionType = getDetectionType(recording);
        const detectionBadge = detectionType ?
            `<span class="detection-type-badge ${detectionType.toLowerCase()}">${detectionType}</span>` : '';

        const duration = recording.duration || estimateDuration(recording.filename);
        const durationFormatted = formatDuration(duration);

        // Use Python API directly for streaming (CORS enabled)
        const streamUrl = `${PYTHON_API}/api/recording/${recording.camera_id}/${recording.filename}`;
        const downloadUrl = `${PYTHON_API}/api/recording/${recording.camera_id}/${recording.filename}?download=true`;

        return `
            <div class="recording-card">
                <div class="recording-thumbnail" onclick="openVideoModal('${streamUrl}', '${recording.camera_name || ''}', '${formattedDate}', '${downloadUrl}', ${recording.camera_id}, '${recording.filename}')">
                    <div class="thumbnail-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                        <span>Click to play</span>
                    </div>
                    <div class="play-overlay">
                        <div class="play-button">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <polygon points="5 3 19 12 5 21 5 3"/>
                            </svg>
                        </div>
                    </div>
                    ${detectionBadge}
                    ${durationFormatted ? `<span class="duration-badge">${durationFormatted}</span>` : ''}
                </div>
                <div class="recording-info">
                    <div class="recording-meta">
                        <span class="recording-camera">${recording.camera_name || 'Camera ' + recording.camera_id}</span>
                        <span class="recording-date">${formattedDate}</span>
                    </div>
                    <div class="recording-details">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            ${formatFileSize(recording.file_size)}
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            ${recording.camera_location || 'Unknown'}
                        </span>
                    </div>
                    <div class="recording-actions">
                        <button class="btn btn-secondary btn-sm" onclick="event.stopPropagation(); openVideoModal('${streamUrl}', '${recording.camera_name || ''}', '${formattedDate}', '${downloadUrl}', ${recording.camera_id}, '${recording.filename}')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="5 3 19 12 5 21 5 3"/>
                            </svg>
                            View
                        </button>
                        <a href="${downloadUrl}" class="btn btn-primary btn-sm" download onclick="event.stopPropagation();">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Download
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    function getDetectionType(recording) {
        if (recording.detection_type) {
            return recording.detection_type;
        }

        const filename = (recording.filename || '').toLowerCase();
        if (filename.includes('motion_human') || filename.includes('motion-human')) {
            return 'MOTION_HUMAN';
        } else if (filename.includes('human')) {
            return 'HUMAN';
        } else if (filename.includes('motion')) {
            return 'MOTION';
        }
        return null;
    }

    function estimateDuration(filename) {
        // Try to extract duration from filename if available
        const match = filename && filename.match(/(\d+)s/);
        return match ? parseInt(match[1]) : null;
    }

    function formatDuration(seconds) {
        if (!seconds) return '';
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return mins > 0 ? `${mins}:${secs.toString().padStart(2, '0')}` : `0:${secs.toString().padStart(2, '0')}`;
    }

    function formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i];
    }

    function renderPagination(totalPages, recordings) {
        const pagination = document.getElementById('pagination');
        let html = '';

        html += `<button ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1}, ${JSON.stringify(recordings).replace(/"/g, '&quot;')})">Previous</button>`;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                html += `<button class="${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                html += `<span style="padding: 0.5rem;">...</span>`;
            }
        }

        html += `<button ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">Next</button>`;

        pagination.innerHTML = html;
    }

    function goToPage(page) {
        currentPage = page;
        loadRecordings();
    }

    function changePage(page) {
        currentPage = page;
        loadRecordings();
    }

    function openVideoModal(streamUrl, cameraName, date, downloadUrl, cameraId, filename) {
        const modal = document.getElementById('videoModal');
        const video = document.getElementById('modalVideo');
        const title = document.getElementById('modalTitle');
        const details = document.getElementById('modalDetails');
        const downloadBtn = document.getElementById('modalDownloadBtn');
        const deleteBtn = document.getElementById('modalDeleteBtn');

        // Reset and load video properly
        video.pause();
        video.removeAttribute('src');
        video.load();

        // Set the new source
        video.src = streamUrl;
        video.type = 'video/mp4';

        title.textContent = cameraName || 'Recording';
        details.textContent = date || '';
        downloadBtn.href = downloadUrl;

        // Store current recording info for delete
        modal.dataset.cameraId = cameraId;
        modal.dataset.filename = filename;

        // Show delete button only for admin
        const user = JSON.parse(localStorage.getItem('user') || 'null');
        if (user && user.is_admin) {
            deleteBtn.style.display = 'inline-flex';
        } else {
            deleteBtn.style.display = 'none';
        }

        modal.classList.add('active');

        // Load and play video
        video.load();
        video.play().catch(e => {
            console.log('Auto-play prevented:', e);
            // Video will still be playable via controls
        });

        // Close on escape key
        document.addEventListener('keydown', handleEscapeKey);
    }

    function closeVideoModal() {
        const modal = document.getElementById('videoModal');
        const video = document.getElementById('modalVideo');

        video.pause();
        video.removeAttribute('src');
        video.load();
        modal.classList.remove('active');

        document.removeEventListener('keydown', handleEscapeKey);
    }

    function handleEscapeKey(e) {
        if (e.key === 'Escape') {
            closeVideoModal();
        }
    }

    // Close modal when clicking outside
    document.getElementById('videoModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeVideoModal();
        }
    });

    function showLoading() {
        document.getElementById('loadingOverlay').classList.remove('hidden');
    }

    function hideLoading() {
        document.getElementById('loadingOverlay').classList.add('hidden');
    }

    function showError(message) {
        alert(message);
    }

    async function deleteRecording() {
        const modal = document.getElementById('videoModal');
        const cameraId = modal.dataset.cameraId;
        const filename = modal.dataset.filename;

        if (!cameraId || !filename) {
            showError('Recording information not found');
            return;
        }

        if (!confirm('Are you sure you want to delete this recording? This action cannot be undone.')) {
            return;
        }

        try {
            const { response, data } = await apiCall(`/admin/cameras/${cameraId}/recordings/${filename}`, {
                method: 'DELETE'
            });

            if (response.ok && data.success) {
                closeVideoModal();
                loadRecordings(); // Refresh the list
                alert('Recording deleted successfully');
            } else {
                showError(data.error || 'Failed to delete recording');
            }
        } catch (error) {
            console.error('Error deleting recording:', error);
            showError('Failed to delete recording');
        }
    }
</script>
@endsection
