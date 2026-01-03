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
        background: var(--bg-card);
        border-radius: 8px;
        border: 2px dashed var(--accent);
        box-shadow: var(--shadow-sm);
    }

    .share-access-section h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .share-access-section h3 svg {
        color: var(--accent);
    }

    .share-access-section > p {
        font-size: 0.9rem;
        color: var(--text-secondary);
        margin-bottom: 1rem;
    }

    .share-access-placeholder {
        padding: 2rem;
        text-align: center;
        background: var(--bg-main);
        border-radius: 6px;
        border: 1px solid var(--border);
        display: block;
    }

    .share-access-placeholder svg {
        display: block;
        margin: 0 auto;
        color: var(--accent);
        opacity: 0.6;
    }

    /* Share Modal Styles */
    .share-link-container {
        background: var(--bg-main);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .share-link-url {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }

    .share-link-url input {
        flex: 1;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 0.9rem;
        background: var(--bg-card);
    }

    .share-link-url button {
        padding: 0.75rem 1rem;
        white-space: nowrap;
    }

    .share-info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-top: 1rem;
    }

    .share-info-item {
        background: var(--bg-card);
        padding: 0.75rem;
        border-radius: 6px;
        text-align: center;
    }

    .share-info-item label {
        display: block;
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin-bottom: 0.25rem;
    }

    .share-info-item span {
        font-weight: 600;
        color: var(--text-primary);
    }

    .share-tokens-list {
        margin-top: 1.5rem;
        border-top: 1px solid var(--border);
        padding-top: 1.5rem;
    }

    .share-tokens-list h4 {
        font-size: 0.95rem;
        margin-bottom: 1rem;
        color: var(--text-primary);
    }

    .share-token-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 6px;
        margin-bottom: 0.5rem;
    }

    .share-token-info h5 {
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }

    .share-token-info p {
        font-size: 0.8rem;
        color: var(--text-secondary);
    }

    .share-token-status {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .share-token-status.active {
        background: var(--success);
        color: white;
    }

    .share-token-status.expired {
        background: var(--danger);
        color: white;
    }

    .share-token-status.revoked {
        background: var(--secondary);
        color: white;
    }

    /* Share button in action group */
    .icon-btn.share-btn {
        color: var(--accent);
    }

    .icon-btn.share-btn:hover {
        background: rgba(66, 153, 225, 0.1);
        color: var(--accent);
    }

    /* User Access button */
    .icon-btn.access-btn {
        color: #805ad5;
    }

    .icon-btn.access-btn:hover {
        background: rgba(128, 90, 213, 0.1);
        color: #805ad5;
    }

    /* User Access Modal Styles */
    .access-list-container {
        max-height: 300px;
        overflow-y: auto;
        margin-top: 1rem;
    }

    .access-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: var(--bg-main);
        border: 1px solid var(--border);
        border-radius: 8px;
        margin-bottom: 0.75rem;
    }

    .access-item:hover {
        border-color: var(--accent);
    }

    .access-user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .access-user-avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #805ad5, #553c9a);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1rem;
    }

    .access-user-details h5 {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.125rem;
    }

    .access-user-details p {
        font-size: 0.8rem;
        color: var(--text-secondary);
    }

    .access-permissions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .permission-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .permission-badge.view {
        background: rgba(56, 161, 105, 0.15);
        color: #38a169;
    }

    .permission-badge.control {
        background: rgba(49, 130, 206, 0.15);
        color: #3182ce;
    }

    .permission-badge.configure {
        background: rgba(128, 90, 213, 0.15);
        color: #805ad5;
    }

    .permission-badge.disabled {
        background: var(--bg-main);
        color: var(--text-secondary);
        opacity: 0.5;
    }

    .access-status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .access-status-badge.active {
        background: rgba(56, 161, 105, 0.15);
        color: #38a169;
    }

    .access-status-badge.expired {
        background: rgba(229, 62, 62, 0.15);
        color: #e53e3e;
    }

    .access-actions {
        display: flex;
        gap: 0.5rem;
    }

    .permission-toggle-group {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .permission-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: var(--bg-main);
        border: 1px solid var(--border);
        border-radius: 8px;
    }

    .permission-toggle-info h5 {
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.125rem;
    }

    .permission-toggle-info p {
        font-size: 0.8rem;
        color: var(--text-secondary);
    }

    .no-access-message {
        text-align: center;
        padding: 2rem;
        color: var(--text-secondary);
    }

    .no-access-message svg {
        width: 48px;
        height: 48px;
        margin-bottom: 0.75rem;
        opacity: 0.5;
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

    <!-- Share Access Section -->
    <div class="share-access-section">
        <h3>
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; flex-shrink: 0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
            <span>Camera Stream Sharing</span>
        </h3>
        <p>Share camera streams with non-users for a limited time. Click the share button on any camera to generate a temporary viewing link.</p>
        <div class="share-access-placeholder">
            <div style="display: flex; flex-direction: column; align-items: center;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 48px; height: 48px; color: var(--text-secondary); margin-bottom: 1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <p style="color: var(--text-secondary); margin: 0;">Generate secure, time-limited share links for guests to view camera streams without requiring an account</p>
            </div>
        </div>
    </div>
</div>

<!-- Share Camera Modal -->
<div id="shareModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Share Camera Stream</h2>
            <button class="modal-close" onclick="closeShareModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="shareCameraId">

            <div style="background: var(--bg-main); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 48px; height: 48px; background: var(--accent); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <svg fill="none" stroke="white" viewBox="0 0 24 24" style="width: 24px; height: 24px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 id="shareCameraName" style="font-weight: 600; margin-bottom: 0.125rem;">Camera Name</h4>
                        <p id="shareCameraLocation" style="font-size: 0.85rem; color: var(--text-secondary);">Location</p>
                    </div>
                </div>
            </div>

            <form id="shareForm">
                <div class="form-group">
                    <label class="form-label" for="shareName">Link Name (Optional)</label>
                    <input type="text" id="shareName" class="form-input" placeholder="e.g., Guest Viewing">
                    <small style="color: var(--text-secondary); font-size: 0.8rem;">A descriptive name to identify this share link</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="watchDuration">Watch Duration *</label>
                        <select id="watchDuration" class="form-input" required>
                            <option value="300">5 minutes</option>
                            <option value="600">10 minutes</option>
                            <option value="900">15 minutes</option>
                            <option value="1800">30 minutes</option>
                            <option value="3600" selected>1 hour</option>
                            <option value="7200">2 hours</option>
                            <option value="14400">4 hours</option>
                            <option value="28800">8 hours</option>
                            <option value="86400">24 hours</option>
                        </select>
                        <small style="color: var(--text-secondary); font-size: 0.8rem;">How long the viewer can watch once they start</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="expiresInHours">Link Valid For *</label>
                        <select id="expiresInHours" class="form-input" required>
                            <option value="1">1 hour</option>
                            <option value="6">6 hours</option>
                            <option value="12">12 hours</option>
                            <option value="24" selected>24 hours</option>
                            <option value="48">2 days</option>
                            <option value="72">3 days</option>
                            <option value="168">7 days</option>
                        </select>
                        <small style="color: var(--text-secondary); font-size: 0.8rem;">How long until the link expires</small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="maxViews">Maximum Views (Optional)</label>
                    <input type="number" id="maxViews" class="form-input" placeholder="Unlimited" min="1" max="100">
                    <small style="color: var(--text-secondary); font-size: 0.8rem;">Leave empty for unlimited views</small>
                </div>
            </form>

            <!-- Generated Link Section (hidden initially) -->
            <div id="generatedLinkSection" style="display: none;">
                <div class="share-link-container">
                    <h4 style="font-size: 0.95rem; margin-bottom: 0.75rem; color: var(--success); display: flex; align-items: center; gap: 0.5rem;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Share Link Generated!
                    </h4>
                    <div class="share-link-url">
                        <input type="text" id="generatedUrl" readonly>
                        <button type="button" class="btn btn-primary" onclick="copyShareLink()">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                            </svg>
                            Copy
                        </button>
                    </div>
                    <div class="share-info-grid">
                        <div class="share-info-item">
                            <label>Watch Duration</label>
                            <span id="shareInfoDuration">--</span>
                        </div>
                        <div class="share-info-item">
                            <label>Expires</label>
                            <span id="shareInfoExpires">--</span>
                        </div>
                        <div class="share-info-item">
                            <label>Max Views</label>
                            <span id="shareInfoViews">--</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Existing Share Tokens -->
            <div class="share-tokens-list" id="shareTokensList" style="display: none;">
                <h4>Active Share Links</h4>
                <div id="shareTokensContainer">
                    <!-- Tokens will be loaded here -->
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeShareModal()">Close</button>
            <button type="button" class="btn btn-primary" id="generateShareBtn" onclick="generateShareLink()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                Generate Share Link
            </button>
        </div>
    </div>
</div>

<!-- User Access Management Modal -->
<div id="accessModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2>Manage User Access</h2>
            <button class="modal-close" onclick="closeAccessModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="accessCameraId">

            <!-- Camera Info Header -->
            <div style="background: var(--bg-main); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 48px; height: 48px; background: #805ad5; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <svg fill="none" stroke="white" viewBox="0 0 24 24" style="width: 24px; height: 24px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 id="accessCameraName" style="font-weight: 600; margin-bottom: 0.125rem;">Camera Name</h4>
                        <p id="accessCameraLocation" style="font-size: 0.85rem; color: var(--text-secondary);">Location</p>
                    </div>
                </div>
            </div>

            <!-- Grant New Access Section -->
            <div style="border: 1px solid var(--border); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px; color: #805ad5;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Grant Access to User
                </h4>

                <form id="grantAccessForm">
                    <div class="form-group">
                        <label class="form-label" for="accessUserId">Select User *</label>
                        <select id="accessUserId" class="form-input" required>
                            <option value="">-- Select a user --</option>
                        </select>
                    </div>

                    <div class="permission-toggle-group">
                        <div class="permission-toggle">
                            <div class="permission-toggle-info">
                                <h5>View</h5>
                                <p>Can view camera stream</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="permCanView" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="permission-toggle">
                            <div class="permission-toggle-info">
                                <h5>Control</h5>
                                <p>Can control camera (PTZ, zoom)</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="permCanControl">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="permission-toggle">
                            <div class="permission-toggle-info">
                                <h5>Configure</h5>
                                <p>Can change camera settings</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" id="permCanConfigure">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row" style="margin-top: 1rem;">
                        <div class="form-group">
                            <label class="form-label" for="accessStartsAt">Access Starts (Optional)</label>
                            <input type="datetime-local" id="accessStartsAt" class="form-input">
                            <small style="color: var(--text-secondary); font-size: 0.8rem;">Leave empty for immediate access</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="accessExpiresAt">Access Expires (Optional)</label>
                            <input type="datetime-local" id="accessExpiresAt" class="form-input">
                            <small style="color: var(--text-secondary); font-size: 0.8rem;">Leave empty for permanent access</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="grantAccessBtn" style="width: 100%; margin-top: 1rem;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Grant Access
                    </button>
                </form>
            </div>

            <!-- Current Access List -->
            <div>
                <h4 style="font-size: 0.95rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
                    <span style="display: flex; align-items: center; gap: 0.5rem;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px; color: var(--accent);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Users with Access
                    </span>
                    <span id="accessCount" style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 400;">0 users</span>
                </h4>

                <div id="accessListContainer" class="access-list-container">
                    <!-- Access list will be loaded here -->
                    <div class="no-access-message">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                        <p>No users have access to this camera yet</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeAccessModal()">Close</button>
        </div>
    </div>
</div>

<!-- Edit User Access Modal -->
<div id="editAccessModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Edit User Access</h2>
            <button class="modal-close" onclick="closeEditAccessModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editAccessId">
            <input type="hidden" id="editAccessCameraId">

            <div style="background: var(--bg-main); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div id="editAccessUserAvatar" class="access-user-avatar">U</div>
                    <div>
                        <h4 id="editAccessUserName" style="font-weight: 600; margin-bottom: 0.125rem;">User Name</h4>
                        <p id="editAccessUserEmail" style="font-size: 0.85rem; color: var(--text-secondary);">user@email.com</p>
                    </div>
                </div>
            </div>

            <form id="editAccessForm">
                <div class="permission-toggle-group">
                    <div class="permission-toggle">
                        <div class="permission-toggle-info">
                            <h5>View</h5>
                            <p>Can view camera stream</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="editPermCanView">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="permission-toggle">
                        <div class="permission-toggle-info">
                            <h5>Control</h5>
                            <p>Can control camera (PTZ, zoom)</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="editPermCanControl">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>

                    <div class="permission-toggle">
                        <div class="permission-toggle-info">
                            <h5>Configure</h5>
                            <p>Can change camera settings</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="editPermCanConfigure">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="form-row" style="margin-top: 1rem;">
                    <div class="form-group">
                        <label class="form-label" for="editAccessStartsAt">Access Starts</label>
                        <input type="datetime-local" id="editAccessStartsAt" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="editAccessExpiresAt">Access Expires</label>
                        <input type="datetime-local" id="editAccessExpiresAt" class="form-input">
                        <small style="color: var(--text-secondary); font-size: 0.8rem;">Leave empty for permanent access</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeEditAccessModal()">Cancel</button>
            <button type="button" class="btn btn-primary" id="updateAccessBtn" onclick="updateUserAccess()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Update Access
            </button>
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
                                    <button class="icon-btn access-btn" onclick="openAccessModal(${camera.id})" title="Manage User Access">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                        </svg>
                                    </button>
                                    <button class="icon-btn share-btn" onclick="openShareModal(${camera.id})" title="Share Stream">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                        </svg>
                                    </button>
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

    // Share Modal Functions
    let currentShareCameraId = null;

    function openShareModal(cameraId) {
        currentShareCameraId = cameraId;
        const camera = cameras.find(c => c.id === cameraId);

        if (!camera) {
            showAlert('Camera not found', 'error');
            return;
        }

        // Reset form
        document.getElementById('shareForm').reset();
        document.getElementById('generatedLinkSection').style.display = 'none';
        document.getElementById('shareTokensList').style.display = 'none';
        document.getElementById('generateShareBtn').style.display = 'inline-flex';

        // Set camera info
        document.getElementById('shareCameraId').value = cameraId;
        document.getElementById('shareCameraName').textContent = camera.name;
        document.getElementById('shareCameraLocation').textContent = camera.location || 'No location set';

        // Load existing share tokens
        loadShareTokens(cameraId);

        document.getElementById('shareModal').style.display = 'flex';
    }

    function closeShareModal() {
        document.getElementById('shareModal').style.display = 'none';
        currentShareCameraId = null;
    }

    async function loadShareTokens(cameraId) {
        try {
            const { response, data } = await apiCall(`/admin/cameras/${cameraId}/share-tokens`);

            if (response.ok && data.share_tokens && data.share_tokens.length > 0) {
                const container = document.getElementById('shareTokensContainer');
                container.innerHTML = data.share_tokens.map(token => {
                    let statusClass = 'active';
                    let statusText = 'Active';

                    if (!token.is_active) {
                        statusClass = 'revoked';
                        statusText = 'Revoked';
                    } else if (!token.is_valid) {
                        statusClass = 'expired';
                        statusText = 'Expired';
                    }

                    return `
                        <div class="share-token-item">
                            <div class="share-token-info">
                                <h5>${token.name || 'Unnamed Link'}</h5>
                                <p>Duration: ${token.watch_duration_formatted} | Expires: ${token.expires_at_formatted}</p>
                                <p>Views: ${token.current_views}${token.max_views ? '/' + token.max_views : ''}</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <span class="share-token-status ${statusClass}">${statusText}</span>
                                ${token.is_valid ? `
                                    <button class="icon-btn" onclick="copyTokenLink('${token.share_url}')" title="Copy Link">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                        </svg>
                                    </button>
                                    <button class="icon-btn" onclick="revokeToken(${cameraId}, ${token.id})" title="Revoke" style="color: var(--danger);">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }).join('');

                document.getElementById('shareTokensList').style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading share tokens:', error);
        }
    }

    async function generateShareLink() {
        const cameraId = document.getElementById('shareCameraId').value;
        const btn = document.getElementById('generateShareBtn');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" style="display: inline-block;"></span> Generating...';

        const formData = {
            name: document.getElementById('shareName').value || null,
            watch_duration: parseInt(document.getElementById('watchDuration').value),
            expires_in_hours: parseInt(document.getElementById('expiresInHours').value),
            max_views: document.getElementById('maxViews').value ? parseInt(document.getElementById('maxViews').value) : null
        };

        try {
            const { response, data } = await apiCall(`/admin/cameras/${cameraId}/share`, {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            if (response.ok && data.success) {
                // Show generated link
                document.getElementById('generatedUrl').value = data.share_token.share_url;
                document.getElementById('shareInfoDuration').textContent = data.share_token.watch_duration_formatted;
                document.getElementById('shareInfoExpires').textContent = data.share_token.expires_at_formatted;
                document.getElementById('shareInfoViews').textContent = data.share_token.max_views || 'Unlimited';

                document.getElementById('generatedLinkSection').style.display = 'block';
                document.getElementById('generateShareBtn').style.display = 'none';

                // Reload tokens list
                loadShareTokens(cameraId);

                showAlert('Share link generated successfully!', 'success');
            } else {
                showAlert(data.error || 'Failed to generate share link', 'error');
            }
        } catch (error) {
            console.error('Error generating share link:', error);
            showAlert('Error generating share link', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                Generate Share Link
            `;
        }
    }

    function copyShareLink() {
        const urlInput = document.getElementById('generatedUrl');
        urlInput.select();
        document.execCommand('copy');
        showAlert('Share link copied to clipboard!', 'success');
    }

    function copyTokenLink(url) {
        navigator.clipboard.writeText(url).then(() => {
            showAlert('Share link copied to clipboard!', 'success');
        }).catch(() => {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = url;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showAlert('Share link copied to clipboard!', 'success');
        });
    }

    async function revokeToken(cameraId, tokenId) {
        if (!confirm('Are you sure you want to revoke this share link? This cannot be undone.')) {
            return;
        }

        try {
            const { response, data } = await apiCall(`/admin/cameras/${cameraId}/share-tokens/${tokenId}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                showAlert('Share link revoked successfully', 'success');
                loadShareTokens(cameraId);
            } else {
                showAlert(data.error || 'Failed to revoke share link', 'error');
            }
        } catch (error) {
            console.error('Error revoking token:', error);
            showAlert('Error revoking share link', 'error');
        }
    }

    // Close share modal on outside click
    document.getElementById('shareModal').addEventListener('click', (e) => {
        if (e.target.id === 'shareModal') {
            closeShareModal();
        }
    });

    // =============================================
    // USER ACCESS MANAGEMENT FUNCTIONS
    // =============================================

    let currentAccessCameraId = null;
    let availableUsers = [];
    let currentAccessList = [];

    async function loadAvailableUsers() {
        try {
            const { response, data } = await apiCall('/admin/users/available');
            if (response.ok && data.success) {
                availableUsers = data.users;
                return data.users;
            }
        } catch (error) {
            console.error('Error loading available users:', error);
        }
        return [];
    }

    function populateUserDropdown(excludeUserIds = []) {
        const select = document.getElementById('accessUserId');
        select.innerHTML = '<option value="">-- Select a user --</option>';

        availableUsers.forEach(user => {
            if (!excludeUserIds.includes(user.id)) {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = `${user.name} (${user.email})`;
                select.appendChild(option);
            }
        });
    }

    async function openAccessModal(cameraId) {
        currentAccessCameraId = cameraId;
        const camera = cameras.find(c => c.id === cameraId);

        if (!camera) {
            showAlert('Camera not found', 'error');
            return;
        }

        // Reset form
        document.getElementById('grantAccessForm').reset();
        document.getElementById('permCanView').checked = true;
        document.getElementById('permCanControl').checked = false;
        document.getElementById('permCanConfigure').checked = false;

        // Set camera info
        document.getElementById('accessCameraId').value = cameraId;
        document.getElementById('accessCameraName').textContent = camera.name;
        document.getElementById('accessCameraLocation').textContent = camera.location || 'No location set';

        // Load available users first
        await loadAvailableUsers();

        // Load current access list
        await loadCameraAccessList(cameraId);

        document.getElementById('accessModal').style.display = 'flex';
    }

    function closeAccessModal() {
        document.getElementById('accessModal').style.display = 'none';
        currentAccessCameraId = null;
    }

    async function loadCameraAccessList(cameraId) {
        try {
            const { response, data } = await apiCall(`/admin/cameras/${cameraId}/access`);

            if (response.ok && data.success) {
                currentAccessList = data.access_list;
                renderAccessList(data.access_list);

                // Update user dropdown to exclude users who already have access
                const existingUserIds = data.access_list.map(a => a.user_id);
                populateUserDropdown(existingUserIds);

                // Update count
                document.getElementById('accessCount').textContent = `${data.access_list.length} user${data.access_list.length !== 1 ? 's' : ''}`;
            }
        } catch (error) {
            console.error('Error loading camera access list:', error);
        }
    }

    function renderAccessList(accessList) {
        const container = document.getElementById('accessListContainer');

        if (accessList.length === 0) {
            container.innerHTML = `
                <div class="no-access-message">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                    <p>No users have access to this camera yet</p>
                </div>
            `;
            return;
        }

        container.innerHTML = accessList.map(access => {
            const initials = access.user_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
            const statusClass = access.is_active ? 'active' : 'expired';
            const statusText = access.is_active ? 'Active' : 'Expired';

            return `
                <div class="access-item">
                    <div class="access-user-info">
                        <div class="access-user-avatar">${initials}</div>
                        <div class="access-user-details">
                            <h5>${access.user_name}</h5>
                            <p>${access.user_email}</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div class="access-permissions">
                            <span class="permission-badge ${access.can_view ? 'view' : 'disabled'}" title="View">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 12px; height: 12px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                View
                            </span>
                            <span class="permission-badge ${access.can_control ? 'control' : 'disabled'}" title="Control">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 12px; height: 12px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                                </svg>
                                Control
                            </span>
                            <span class="permission-badge ${access.can_configure ? 'configure' : 'disabled'}" title="Configure">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 12px; height: 12px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Config
                            </span>
                        </div>
                        <span class="access-status-badge ${statusClass}">${statusText}</span>
                        <div class="access-actions">
                            <button class="icon-btn" onclick="openEditAccessModal(${access.id})" title="Edit Access">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button class="icon-btn" onclick="revokeUserAccess(${currentAccessCameraId}, ${access.id}, '${access.user_name}')" title="Revoke Access" style="color: var(--danger);">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Grant Access Form Submit
    document.getElementById('grantAccessForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const btn = document.getElementById('grantAccessBtn');
        const cameraId = document.getElementById('accessCameraId').value;
        const userId = document.getElementById('accessUserId').value;

        if (!userId) {
            showAlert('Please select a user', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" style="display: inline-block;"></span> Granting...';

        const formData = {
            user_id: parseInt(userId),
            can_view: document.getElementById('permCanView').checked,
            can_control: document.getElementById('permCanControl').checked,
            can_configure: document.getElementById('permCanConfigure').checked,
            access_starts_at: document.getElementById('accessStartsAt').value || null,
            access_expires_at: document.getElementById('accessExpiresAt').value || null
        };

        try {
            const { response, data } = await apiCall(`/admin/cameras/${cameraId}/access`, {
                method: 'POST',
                body: JSON.stringify(formData)
            });

            if (response.ok && data.success) {
                showAlert(data.message || 'Access granted successfully!', 'success');

                // Reset form
                document.getElementById('grantAccessForm').reset();
                document.getElementById('permCanView').checked = true;

                // Reload access list
                await loadCameraAccessList(cameraId);
            } else {
                showAlert(data.error || 'Failed to grant access', 'error');
            }
        } catch (error) {
            console.error('Error granting access:', error);
            showAlert('Error granting access', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Grant Access
            `;
        }
    });

    // Edit Access Modal
    function openEditAccessModal(accessId) {
        const access = currentAccessList.find(a => a.id === accessId);
        if (!access) {
            showAlert('Access entry not found', 'error');
            return;
        }

        // Set hidden fields
        document.getElementById('editAccessId').value = access.id;
        document.getElementById('editAccessCameraId').value = access.camera_id;

        // Set user info
        const initials = access.user_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        document.getElementById('editAccessUserAvatar').textContent = initials;
        document.getElementById('editAccessUserName').textContent = access.user_name;
        document.getElementById('editAccessUserEmail').textContent = access.user_email;

        // Set permissions
        document.getElementById('editPermCanView').checked = access.can_view;
        document.getElementById('editPermCanControl').checked = access.can_control;
        document.getElementById('editPermCanConfigure').checked = access.can_configure;

        // Set dates
        if (access.access_starts_at) {
            // Convert to datetime-local format
            const startsAt = new Date(access.access_starts_at.replace(' ', 'T'));
            document.getElementById('editAccessStartsAt').value = formatDateTimeLocal(startsAt);
        } else {
            document.getElementById('editAccessStartsAt').value = '';
        }

        if (access.access_expires_at) {
            const expiresAt = new Date(access.access_expires_at.replace(' ', 'T'));
            document.getElementById('editAccessExpiresAt').value = formatDateTimeLocal(expiresAt);
        } else {
            document.getElementById('editAccessExpiresAt').value = '';
        }

        document.getElementById('editAccessModal').style.display = 'flex';
    }

    function closeEditAccessModal() {
        document.getElementById('editAccessModal').style.display = 'none';
    }

    function formatDateTimeLocal(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    async function updateUserAccess() {
        const btn = document.getElementById('updateAccessBtn');
        const accessId = document.getElementById('editAccessId').value;
        const cameraId = document.getElementById('editAccessCameraId').value;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner" style="display: inline-block;"></span> Updating...';

        const formData = {
            can_view: document.getElementById('editPermCanView').checked,
            can_control: document.getElementById('editPermCanControl').checked,
            can_configure: document.getElementById('editPermCanConfigure').checked,
            access_starts_at: document.getElementById('editAccessStartsAt').value || null,
            access_expires_at: document.getElementById('editAccessExpiresAt').value || null
        };

        try {
            const { response, data } = await apiCall(`/admin/cameras/${cameraId}/access/${accessId}`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });

            if (response.ok && data.success) {
                showAlert(data.message || 'Access updated successfully!', 'success');
                closeEditAccessModal();

                // Reload access list
                await loadCameraAccessList(cameraId);
            } else {
                showAlert(data.error || 'Failed to update access', 'error');
            }
        } catch (error) {
            console.error('Error updating access:', error);
            showAlert('Error updating access', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Update Access
            `;
        }
    }

    async function revokeUserAccess(cameraId, accessId, userName) {
        if (!confirm(`Are you sure you want to revoke ${userName}'s access to this camera?`)) {
            return;
        }

        try {
            const { response, data } = await apiCall(`/admin/cameras/${cameraId}/access/${accessId}`, {
                method: 'DELETE'
            });

            if (response.ok && data.success) {
                showAlert(data.message || 'Access revoked successfully', 'success');
                await loadCameraAccessList(cameraId);
            } else {
                showAlert(data.error || 'Failed to revoke access', 'error');
            }
        } catch (error) {
            console.error('Error revoking access:', error);
            showAlert('Error revoking access', 'error');
        }
    }

    // Close access modal on outside click
    document.getElementById('accessModal').addEventListener('click', (e) => {
        if (e.target.id === 'accessModal') {
            closeAccessModal();
        }
    });

    document.getElementById('editAccessModal').addEventListener('click', (e) => {
        if (e.target.id === 'editAccessModal') {
            closeEditAccessModal();
        }
    });
</script>
@endsection

