@extends('layouts.app')

@section('title', 'Manage Cameras - Camera Hub')

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

    .action-buttons {
        display: flex;
        gap: 0.75rem;
    }

    .camera-table-container {
        background: var(--bg-card);
        border-radius: 12px;
        border: 1px solid var(--border);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .table-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-search {
        position: relative;
        width: 300px;
    }

    .table-search input {
        width: 100%;
        padding: 0.5rem 1rem 0.5rem 2.5rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 0.9rem;
    }

    .table-search svg {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        width: 16px;
        height: 16px;
        color: var(--text-secondary);
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead {
        background: var(--bg-main);
    }

    th {
        text-align: left;
        padding: 1rem 1.5rem;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    td {
        padding: 1.25rem 1.5rem;
        border-top: 1px solid var(--border);
        font-size: 0.9rem;
    }

    tbody tr {
        transition: background 0.2s;
    }

    tbody tr:hover {
        background: var(--bg-main);
    }

    .camera-name-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .camera-icon {
        width: 40px;
        height: 40px;
        background: var(--bg-main);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
    }

    .camera-icon svg {
        width: 20px;
        height: 20px;
    }

    .camera-name-info h4 {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.125rem;
    }

    .camera-name-info p {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }

    /* Toggle Switch Styles */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 26px;
    }

    .toggle-switch input[type="checkbox"] {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(145deg, #f56565, #e53e3e);
        border-radius: 34px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 3px;
        bottom: 3px;
        background-color: #ffffff;
        border-radius: 50%;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    /* Checked state - GREEN */
    .toggle-switch input[type="checkbox"]:checked + .toggle-slider {
        background: linear-gradient(145deg, #48bb78, #38a169);
    }

    .toggle-switch input[type="checkbox"]:checked + .toggle-slider:before {
        transform: translateX(24px);
    }

    /* Hover effects */
    .toggle-slider:hover {
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
    }

    .toggle-switch input[type="checkbox"]:checked + .toggle-slider:hover {
        background: linear-gradient(145deg, #38a169, #2f855a);
    }

    /* Disabled state */
    .toggle-switch input[type="checkbox"]:disabled + .toggle-slider {
        cursor: not-allowed;
        opacity: 0.5;
        background: #cbd5e0;
    }

    .action-btn-group {
        display: flex;
        gap: 0.5rem;
    }

    .icon-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: none;
        color: var(--text-secondary);
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .icon-btn:hover {
        background: var(--bg-main);
        color: var(--text-primary);
    }

    .icon-btn svg {
        width: 18px;
        height: 18px;
    }

    /* Modal */
    .modal {
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
        padding: 1rem;
    }

    .modal-content {
        background: var(--bg-card);
        border-radius: 12px;
        width: 100%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: var(--shadow-lg);
    }

    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h2 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--text-secondary);
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }

    .modal-close:hover {
        background: var(--bg-main);
    }

    .modal-body {
        padding: 1.5rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .modal-footer {
        padding: 1.5rem;
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
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
    }

    /* Share Access Section */
    .share-access-section {
        margin-top: 2rem;
        padding: 1.5rem;
        background: var(--bg-main);
        border-radius: 8px;
        border: 1px dashed var(--border);
    }

    .share-access-section h3 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .share-access-section p {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 1rem;
    }

    .share-access-placeholder {
        padding: 2rem;
        text-align: center;
        background: var(--bg-card);
        border-radius: 6px;
        border: 1px solid var(--border);
    }

    /* Loading Spinner */
    .spinner {
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top: 2px solid white;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        animation: spin 1s linear infinite;
        display: inline-block;
        vertical-align: middle;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .dashboard-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .table-header {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }

        .table-search {
            width: 100%;
        }

        .camera-table-container {
            overflow-x: auto;
        }

        table {
            min-width: 900px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="dashboard-header">
        <div class="dashboard-title">
            <h1>Manage Cameras</h1>
            <p>Add, edit, and control camera streams</p>
        </div>

        <div class="action-buttons">
            <button onclick="refreshCameras()" class="btn btn-secondary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
            <button onclick="openAddCameraModal()" class="btn btn-primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Camera
            </button>
        </div>
    </div>

    <div id="alertContainer"></div>

    <div class="camera-table-container">
        <div class="table-header">
            <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text-primary);">All Cameras</h3>
            <div class="table-search">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="searchInput" placeholder="Search cameras..." oninput="filterCameras()">
            </div>
        </div>

        <div id="tableContainer">
            <!-- Table will be rendered here -->
        </div>
    </div>

    <!-- Share Access Placeholder -->
    <div class="share-access-section">
        <h3>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
            Camera Access Sharing
        </h3>
        <p>Share camera access with other users (Coming Soon)</p>
        <div class="share-access-placeholder">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 48px; height: 48px; color: var(--text-secondary); margin: 0 auto 1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <p style="color: var(--text-secondary); margin: 0;">This feature will allow you to grant access to specific cameras for other users</p>
        </div>
    </div>
</div>

<!-- Add/Edit Camera Modal -->
<div id="cameraModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Camera</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="cameraForm">
            <div class="modal-body">
                <input type="hidden" id="cameraId">

                <div class="form-group">
                    <label class="form-label" for="cameraName">Camera Name *</label>
                    <input type="text" id="cameraName" class="form-input" placeholder="e.g., Front Door Camera" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="connectionType">Connection Type *</label>
                        <select id="connectionType" class="form-input" required onchange="toggleConnectionFields()">
                            <option value="USB">USB Camera</option>
                            <option value="STREAM">RTSP Stream</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="location">Location *</label>
                        <input type="text" id="location" class="form-input" placeholder="e.g., Building A - Entrance" required>
                    </div>
                </div>

                <!-- USB Path Field -->
                <div class="form-group" id="usbPathGroup">
                    <label class="form-label" for="usbPath">USB Device Path *</label>
                    <input type="text" id="usbPath" class="form-input" placeholder="e.g., /dev/video0 or 0">
                    <small style="color: var(--text-secondary); font-size: 0.85rem;">USB camera device path</small>
                </div>

                <!-- RTSP URL Field -->
                <div class="form-group" id="streamUrlGroup" style="display: none;">
                    <label class="form-label" for="streamUrl">RTSP Stream URL *</label>
                    <input type="url" id="streamUrl" class="form-input" placeholder="rtsp://192.168.1.100:554/stream1">
                    <small style="color: var(--text-secondary); font-size: 0.85rem;">Full RTSP stream URL</small>
                </div>

                <!-- RTSP Authentication (Optional) -->
                <div id="streamAuthGroup" style="display: none;">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="streamUsername">Username (Optional)</label>
                            <input type="text" id="streamUsername" class="form-input" placeholder="Username">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="streamPassword">Password (Optional)</label>
                            <input type="password" id="streamPassword" class="form-input" placeholder="Password">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description *</label>
                    <textarea id="description" class="form-input" rows="3" placeholder="Camera description" required></textarea>
                </div>

                <!-- Detection Settings Section -->
                <div style="margin-top: 1.5rem; padding: 1rem; background: var(--bg-main); border-radius: 8px; border: 1px solid var(--border);">
                    <h4 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Detection Settings
                    </h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="detectionType">Detection Type</label>
                            <select id="detectionType" class="form-input" onchange="toggleDetectionSettings()">
                                <option value="NONE">No Detection</option>
                                <option value="MOTION">Motion Detection</option>
                                <option value="HUMAN">Human Detection (AI)</option>
                                <option value="MOTION_HUMAN">Motion + Human Detection</option>
                            </select>
                            <small style="color: var(--text-secondary); font-size: 0.8rem;">
                                Motion: Fast, detects any movement<br>
                                Human: AI-powered, detects people only<br>
                                Motion+Human: Detects motion first, then verifies human presence
                            </small>
                        </div>

                        <div class="form-group" id="sensitivityGroup" style="display: none;">
                            <label class="form-label" for="detectionSensitivity">Sensitivity</label>
                            <input type="range" id="detectionSensitivity" class="form-input" min="1" max="100" value="50" oninput="updateSensitivityLabel()">
                            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: var(--text-secondary);">
                                <span>Low</span>
                                <span id="sensitivityValue">50</span>
                                <span>High</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-row" id="recordingSettingsGroup" style="display: none;">
                        <div class="form-group">
                            <label class="form-label" for="recordingDuration">Recording Duration (seconds)</label>
                            <input type="number" id="recordingDuration" class="form-input" min="30" max="600" value="180" placeholder="180">
                            <small style="color: var(--text-secondary); font-size: 0.8rem;">Duration of video recording when detection is triggered (30-600 seconds)</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" id="thumbnailEnabled" checked style="margin-right: 0.5rem;">
                                Enable Thumbnail Capture
                            </label>
                            <small style="color: var(--text-secondary); font-size: 0.8rem;">Automatically capture thumbnail images when detection is triggered</small>
                        </div>
                    </div>
                </div>

                <!-- Auto-detect Camera Info Button -->
                <div class="form-group">
                    <button type="button" class="btn btn-secondary" onclick="detectCameraInfo()" id="detectBtn" style="width: 100%;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Auto-Detect Camera Info
                    </button>
                </div>

                <div id="cameraInfoSection" style="display: none; margin-top: 0.5rem; padding: 1rem; background: var(--bg-main); border-radius: 6px; border: 1px solid var(--border);">
                    <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.75rem; font-weight: 600;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px; display: inline; vertical-align: middle; margin-right: 0.25rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Camera Information (Auto-detected):
                    </p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="padding: 0.75rem; background: var(--bg-card); border-radius: 6px;">
                            <strong style="color: var(--text-primary); font-size: 0.85rem; display: block; margin-bottom: 0.25rem;">Resolution:</strong>
                            <span id="detectedResolution" style="color: var(--text-secondary); font-size: 0.95rem; font-weight: 500;">-</span>
                        </div>
                        <div style="padding: 0.75rem; background: var(--bg-card); border-radius: 6px;">
                            <strong style="color: var(--text-primary); font-size: 0.85rem; display: block; margin-bottom: 0.25rem;">Frame Rate:</strong>
                            <span id="detectedFrameRate" style="color: var(--text-secondary); font-size: 0.95rem; font-weight: 500;">-</span>
                        </div>
                    </div>
                    <input type="hidden" id="resolution" name="resolution">
                    <input type="hidden" id="frameRate" name="frame_rate">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span id="btnText">Save Camera</span>
                    <span id="btnSpinner" class="spinner" style="display: none;"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let cameras = [];
    let editingCameraId = null;

    // Detection type helpers
    function getDetectionLabel(type) {
        const labels = {
            'NONE': 'Off',
            'MOTION': 'Motion',
            'HUMAN': 'Human',
            'MOTION_HUMAN': 'Motion+Human'
        };
        return labels[type] || 'Off';
    }

    function getDetectionColor(type) {
        const colors = {
            'NONE': '#718096',
            'MOTION': '#3182ce',
            'HUMAN': '#38a169',
            'MOTION_HUMAN': '#805ad5'
        };
        return colors[type] || '#718096';
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadCameras();
    });

    async function loadCameras() {
        try {
            const { response, data } = await apiCall('/admin/cameras');

            if (response.ok) {
                // Handle paginated response structure: data.data.data
                if (data.data && data.data.data) {
                    cameras = data.data.data; // Paginated structure
                } else if (data.data) {
                    cameras = data.data; // Direct data array
                } else if (data.cameras) {
                    cameras = data.cameras; // Alternative structure
                } else {
                    cameras = data; // Fallback
                }

                console.log('Loaded cameras:', cameras);
                renderTable(cameras);
            } else {
                showAlert('Failed to load cameras', 'error');
            }
        } catch (error) {
            console.error('Error loading cameras:', error);
            showAlert('Error loading cameras', 'error');
        }
    }

    function renderTable(camerasToRender) {
        const tableContainer = document.getElementById('tableContainer');

        if (camerasToRender.length === 0) {
            tableContainer.innerHTML = `
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <h3>No Cameras Added</h3>
                    <p>Add your first camera to get started</p>
                </div>
            `;
            return;
        }

        // Debug: Log camera data to see is_active values
        console.log('Rendering cameras:', camerasToRender.map(c => ({
            id: c.id,
            name: c.name,
            is_active: c.is_active,
            is_active_type: typeof c.is_active
        })));

        tableContainer.innerHTML = `
            <table>
                <thead>
                    <tr>
                        <th>Camera</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Detection</th>
                        <th>Resolution</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${camerasToRender.map(camera => `
                        <tr>
                            <td>
                                <div class="camera-name-cell">
                                    <div class="camera-icon">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="camera-name-info">
                                        <h4>${camera.name}</h4>
                                        <p style="font-size: 0.8rem;">${camera.connection_type === 'USB' ? camera.usb_path || 'N/A' : (camera.stream_url || 'N/A').substring(0, 30) + '...'}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="display: inline-block; padding: 0.25rem 0.5rem; background: ${camera.connection_type === 'USB' ? 'var(--success)' : 'var(--accent)'}; color: white; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                    ${camera.connection_type || 'N/A'}
                                </span>
                            </td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" ${camera.is_active == 1 || camera.is_active === true ? 'checked' : ''} onchange="toggleCamera(${camera.id}, this.checked)" data-camera-id="${camera.id}">
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <span style="display: inline-block; padding: 0.25rem 0.5rem; background: ${getDetectionColor(camera.detection_type)}; color: white; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                    ${getDetectionLabel(camera.detection_type)}
                                </span>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">${camera.resolution || 'N/A'}</td>
                            <td style="color: var(--text-secondary); font-size: 0.85rem;">${camera.location || '-'}</td>
                            <td>
                                <div class="action-btn-group">
                                    <button class="icon-btn" onclick="editCamera(${camera.id})" title="Edit">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button class="icon-btn" onclick="viewCameraDetails(${camera.id})" title="Details">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                    <button class="icon-btn" onclick="deleteCamera(${camera.id})" title="Delete" style="color: var(--danger);">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    function filterCameras() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const filtered = cameras.filter(camera =>
            camera.name.toLowerCase().includes(searchTerm) ||
            (camera.location && camera.location.toLowerCase().includes(searchTerm))
        );
        renderTable(filtered);
    }

    function refreshCameras() {
        loadCameras();
        showAlert('Cameras refreshed', 'success');
    }

    function toggleConnectionFields() {
        const connectionType = document.getElementById('connectionType').value;
        const usbPathGroup = document.getElementById('usbPathGroup');
        const streamUrlGroup = document.getElementById('streamUrlGroup');
        const streamAuthGroup = document.getElementById('streamAuthGroup');
        const usbPath = document.getElementById('usbPath');
        const streamUrl = document.getElementById('streamUrl');

        if (connectionType === 'USB') {
            usbPathGroup.style.display = 'block';
            streamUrlGroup.style.display = 'none';
            streamAuthGroup.style.display = 'none';
            usbPath.required = true;
            streamUrl.required = false;
        } else {
            usbPathGroup.style.display = 'none';
            streamUrlGroup.style.display = 'block';
            streamAuthGroup.style.display = 'block';
            usbPath.required = false;
            streamUrl.required = true;
        }
    }

    function toggleDetectionSettings() {
        const detectionType = document.getElementById('detectionType').value;
        const sensitivityGroup = document.getElementById('sensitivityGroup');
        const recordingSettingsGroup = document.getElementById('recordingSettingsGroup');

        if (detectionType === 'NONE') {
            sensitivityGroup.style.display = 'none';
            recordingSettingsGroup.style.display = 'none';
        } else {
            sensitivityGroup.style.display = 'block';
            recordingSettingsGroup.style.display = 'grid';
        }
    }

    function updateSensitivityLabel() {
        const value = document.getElementById('detectionSensitivity').value;
        document.getElementById('sensitivityValue').textContent = value;
    }

    function openAddCameraModal() {
        editingCameraId = null;
        document.getElementById('modalTitle').textContent = 'Add Camera';
        document.getElementById('cameraForm').reset();
        document.getElementById('cameraId').value = '';
        document.getElementById('cameraInfoSection').style.display = 'none';
        document.getElementById('connectionType').value = 'USB';
        document.getElementById('detectionType').value = 'NONE';
        document.getElementById('detectionSensitivity').value = 50;
        document.getElementById('recordingDuration').value = 180;
        document.getElementById('thumbnailEnabled').checked = true;
        toggleConnectionFields();
        toggleDetectionSettings();
        updateSensitivityLabel();
        document.getElementById('cameraModal').style.display = 'flex';
    }

    function editCamera(id) {
        const camera = cameras.find(c => c.id === id);
        if (!camera) return;

        editingCameraId = id;
        document.getElementById('modalTitle').textContent = 'Edit Camera';
        document.getElementById('cameraId').value = camera.id;
        document.getElementById('cameraName').value = camera.name;
        document.getElementById('location').value = camera.location || '';
        document.getElementById('description').value = camera.description || '';

        // Set connection type and toggle fields
        document.getElementById('connectionType').value = camera.connection_type || 'USB';
        toggleConnectionFields();

        // Populate connection-specific fields
        if (camera.connection_type === 'USB') {
            document.getElementById('usbPath').value = camera.usb_path || '';
        } else {
            document.getElementById('streamUrl').value = camera.stream_url || '';
            document.getElementById('streamUsername').value = camera.stream_username || '';
            document.getElementById('streamPassword').value = camera.stream_password || '';
        }

        // Populate detection settings
        document.getElementById('detectionType').value = camera.detection_type || 'NONE';
        document.getElementById('detectionSensitivity').value = camera.detection_sensitivity || 50;
        document.getElementById('recordingDuration').value = camera.recording_duration || 180;
        document.getElementById('thumbnailEnabled').checked = camera.thumbnail_enabled !== false;
        toggleDetectionSettings();
        updateSensitivityLabel();

        // Show detected camera info if available
        if (camera.resolution || camera.frame_rate) {
            document.getElementById('cameraInfoSection').style.display = 'block';
            document.getElementById('detectedResolution').textContent = camera.resolution || 'N/A';
            document.getElementById('detectedFrameRate').textContent = camera.frame_rate ? camera.frame_rate + ' fps' : 'N/A';
            document.getElementById('resolution').value = camera.resolution || '';
            document.getElementById('frameRate').value = camera.frame_rate || '';
        }

        document.getElementById('cameraModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('cameraModal').style.display = 'none';
        editingCameraId = null;
    }

    async function detectCameraInfo() {
        const detectBtn = document.getElementById('detectBtn');
        const connectionType = document.getElementById('connectionType').value;
        let cameraPath = '';

        if (connectionType === 'USB') {
            cameraPath = document.getElementById('usbPath').value;
            if (!cameraPath) {
                showAlert('Please enter USB device path first', 'error');
                return;
            }
        } else {
            cameraPath = document.getElementById('streamUrl').value;
            if (!cameraPath) {
                showAlert('Please enter RTSP stream URL first', 'error');
                return;
            }
        }

        detectBtn.disabled = true;
        detectBtn.innerHTML = '<span class="spinner" style="display: inline-block;"></span> Detecting...';

        try {
            // Call Python API to get camera info
            const response = await fetch('http://localhost:5000/api/camera/info', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    camera_connection_path: cameraPath
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                const resolution = data.data.resolution;
                const frameRate = data.data.frame_rate;

                // Display the detected info
                document.getElementById('cameraInfoSection').style.display = 'block';
                document.getElementById('detectedResolution').textContent = resolution || 'N/A';
                document.getElementById('detectedFrameRate').textContent = frameRate ? frameRate + ' fps' : 'N/A';

                // Store in hidden fields
                document.getElementById('resolution').value = resolution || '';
                document.getElementById('frameRate').value = frameRate || '';

                showAlert('Camera info detected successfully!', 'success');
            } else {
                showAlert(data.error || 'Failed to detect camera info. Make sure the camera is accessible.', 'error');
            }
        } catch (error) {
            console.error('Error detecting camera info:', error);
            showAlert('Failed to connect to camera service. Make sure Python backend is running.', 'error');
        } finally {
            detectBtn.disabled = false;
            detectBtn.innerHTML = `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Auto-Detect Camera Info
            `;
        }
    }

    document.getElementById('cameraForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');

        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnSpinner.style.display = 'block';

        const connectionType = document.getElementById('connectionType').value;

        const formData = {
            name: document.getElementById('cameraName').value,
            location: document.getElementById('location').value,
            connection_type: connectionType,
            description: document.getElementById('description').value,
            // Detection settings
            detection_type: document.getElementById('detectionType').value,
            detection_sensitivity: parseInt(document.getElementById('detectionSensitivity').value),
            recording_duration: parseInt(document.getElementById('recordingDuration').value),
            thumbnail_enabled: document.getElementById('thumbnailEnabled').checked
        };

        // Add connection-specific fields
        if (connectionType === 'USB') {
            formData.usb_path = document.getElementById('usbPath').value;
        } else {
            formData.stream_url = document.getElementById('streamUrl').value;
            const username = document.getElementById('streamUsername').value;
            const password = document.getElementById('streamPassword').value;
            if (username) formData.stream_username = username;
            if (password) formData.stream_password = password;
        }

        // Add auto-detected camera info if available
        const resolution = document.getElementById('resolution').value;
        const frameRate = document.getElementById('frameRate').value;
        if (resolution) formData.resolution = resolution;
        if (frameRate) formData.frame_rate = parseInt(frameRate);

        try {
            const endpoint = editingCameraId ? `/admin/cameras/${editingCameraId}` : '/admin/cameras';
            const method = editingCameraId ? 'PUT' : 'POST';

            const { response, data } = await apiCall(endpoint, {
                method: method,
                body: JSON.stringify(formData)
            });

            if (response.ok) {
                showAlert(editingCameraId ? 'Camera updated successfully' : 'Camera added successfully', 'success');
                closeModal();
                loadCameras();
            } else {
                showAlert(data.error || data.message || 'Failed to save camera', 'error');
            }
        } catch (error) {
            console.error('Error saving camera:', error);
            showAlert('Error saving camera', 'error');
        } finally {
            submitBtn.disabled = false;
            btnText.style.display = 'block';
            btnSpinner.style.display = 'none';
        }
    });

    async function toggleCamera(id, isActive) {
        try {
            const { response, data } = await apiCall(`/admin/cameras/toggle/${id}`, {
                method: 'POST'
            });

            if (response.ok) {
                showAlert(`Camera ${isActive ? 'activated' : 'deactivated'}`, 'success');
                loadCameras();
            } else {
                showAlert('Failed to toggle camera', 'error');
                loadCameras(); // Reload to reset toggle
            }
        } catch (error) {
            showAlert('Error toggling camera', 'error');
            loadCameras();
        }
    }

    async function deleteCamera(id) {
        if (!confirm('Are you sure you want to delete this camera?')) return;

        try {
            const { response } = await apiCall(`/admin/cameras/${id}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                showAlert('Camera deleted successfully', 'success');
                loadCameras();
            } else {
                showAlert('Failed to delete camera', 'error');
            }
        } catch (error) {
            showAlert('Error deleting camera', 'error');
        }
    }

    function viewCameraDetails(id) {
        const camera = cameras.find(c => c.id === id);
        if (!camera) {
            showAlert('Camera not found', 'error');
            return;
        }

        const connectionInfo = camera.connection_type === 'USB'
            ? `USB Path: ${camera.usb_path || 'N/A'}`
            : `Stream URL: ${camera.stream_url || 'N/A'}\nUsername: ${camera.stream_username || 'Not set'}`;

        alert(`Camera Details:\n\nName: ${camera.name}\nID: ${camera.id}\nType: ${camera.connection_type}\n${connectionInfo}\nLocation: ${camera.location || 'N/A'}\nDescription: ${camera.description || 'N/A'}\n\nTechnical Specs:\nResolution: ${camera.resolution || 'N/A'}\nFrame Rate: ${camera.frame_rate ? camera.frame_rate + ' fps' : 'N/A'}\n\nStatus: ${camera.is_active ? 'Active' : 'Inactive'}\nWebSocket URL: ${camera.websocket_url || 'Not streaming'}`);
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

    // Close modal on outside click
    document.getElementById('cameraModal').addEventListener('click', (e) => {
        if (e.target.id === 'cameraModal') {
            closeModal();
        }
    });
</script>
@endsection

