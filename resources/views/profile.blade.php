@extends('layouts.app')

@section('title', 'My Profile - Camera Hub')

@section('content')
<style>
    .profile-container {
        max-width: 900px;
        margin: 0 auto;
    }

    .profile-header {
        margin-bottom: 2rem;
    }

    .profile-header h1 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .profile-header p {
        color: var(--text-secondary);
        font-size: 1rem;
    }

    .profile-grid {
        display: grid;
        gap: 2rem;
    }

    .profile-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 2rem;
        box-shadow: var(--shadow-sm);
    }

    .profile-card h2 {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .profile-card h2 svg {
        width: 20px;
        height: 20px;
    }

    .avatar-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1.5rem;
    }

    .avatar-preview {
        position: relative;
        width: 150px;
        height: 150px;
    }

    .avatar-image {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--border);
        background: var(--accent);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3.5rem;
        font-weight: 600;
    }

    .avatar-image img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .avatar-upload-btn {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: var(--accent);
        color: white;
        border: 3px solid var(--bg-card);
        border-radius: 50%;
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: var(--shadow-md);
    }

    .avatar-upload-btn:hover {
        background: #3182ce;
        transform: scale(1.05);
    }

    .avatar-upload-btn svg {
        width: 20px;
        height: 20px;
    }

    #avatarInput {
        display: none;
    }

    .avatar-info {
        text-align: center;
        color: var(--text-secondary);
        font-size: 0.875rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        font-weight: 500;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .form-group input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 0.95rem;
        transition: all 0.2s;
        background: var(--bg-main);
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        background: white;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
    }

    .user-info-grid {
        display: grid;
        gap: 1rem;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        background: var(--bg-main);
        border-radius: 8px;
        border: 1px solid var(--border);
    }

    .info-label {
        font-weight: 500;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .info-value {
        color: var(--text-primary);
        font-weight: 600;
        font-size: 0.95rem;
    }

    .spinner {
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top: 2px solid white;
        width: 16px;
        height: 16px;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .profile-container {
            padding: 1rem;
        }

        .profile-card {
            padding: 1.5rem;
        }

        .form-actions {
            flex-direction: column;
        }

        .form-actions button {
            width: 100%;
        }

        .info-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
    }
</style>

<div class="container">
    <div class="profile-container">
        <div class="profile-header">
            <h1>My Profile</h1>
            <p>Manage your account settings and preferences</p>
        </div>

        <div id="alertContainer"></div>

        <div class="profile-grid">
            <!-- Avatar Section -->
            <div class="profile-card">
                <h2>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Profile Picture
                </h2>
                <div class="avatar-section">
                    <div class="avatar-preview">
                        <div class="avatar-image" id="avatarDisplay">
                            <!-- Will be populated by JavaScript -->
                        </div>
                        <label for="avatarInput" class="avatar-upload-btn" title="Change avatar">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </label>
                        <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/jpg">
                    </div>
                    <p class="avatar-info">
                        Click the camera icon to upload a new avatar<br>
                        <small>(JPG, PNG - max 2MB)</small>
                    </p>
                </div>
            </div>

            <!-- User Information -->
            <div class="profile-card">
                <h2>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Account Information
                </h2>
                <div class="user-info-grid">
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value" id="userName">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value" id="userEmail">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Role:</span>
                        <span class="info-value" id="userRole">-</span>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="profile-card">
                <h2>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Change Password
                </h2>
                <form id="changePasswordForm">
                    <div class="form-group">
                        <label for="oldPassword">Current Password *</label>
                        <input type="password" id="oldPassword" name="old_password" required minlength="6" placeholder="Enter current password">
                    </div>
                    <div class="form-group">
                        <label for="newPassword">New Password *</label>
                        <input type="password" id="newPassword" name="new_password" required minlength="6" placeholder="Enter new password (min 6 characters)">
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password *</label>
                        <input type="password" id="confirmPassword" name="new_password_confirmation" required minlength="6" placeholder="Re-enter new password">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="changePasswordBtn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update Password
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('changePasswordForm').reset()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentUser = null;

    document.addEventListener('DOMContentLoaded', function() {
        loadUserProfile();
        setupEventListeners();
    });

    function setupEventListeners() {
        // Avatar upload
        document.getElementById('avatarInput').addEventListener('change', handleAvatarUpload);

        // Password change form
        document.getElementById('changePasswordForm').addEventListener('submit', handlePasswordChange);
    }

    async function loadUserProfile() {
        try {
            const { response, data } = await apiCall('/auth/user-profile');

            if (response.ok) {
                currentUser = data;
                displayUserInfo(data);
            } else {
                showAlert('Failed to load profile', 'error');
            }
        } catch (error) {
            console.error('Error loading profile:', error);
            showAlert('Error loading profile', 'error');
        }
    }

    async function displayUserInfo(user) {
        document.getElementById('userName').textContent = user.name;
        document.getElementById('userEmail').textContent = user.email;
        document.getElementById('userRole').textContent = user.is_admin ? 'Administrator' : 'User';

        const avatarDisplay = document.getElementById('avatarDisplay');
        if (user.avatar_url) {
            const filename = user.avatar_url.includes('/') ? user.avatar_url.split('/').pop() : user.avatar_url;
            await loadAvatarImage(filename, user.name);
        } else {
            avatarDisplay.textContent = user.name.charAt(0).toUpperCase();
        }
    }

    async function loadAvatarImage(filename, userName) {
        const avatarDisplay = document.getElementById('avatarDisplay');
        const token = localStorage.getItem('token');

        try {
            const response = await fetch(`${API_URL}/auth/avatars/${filename}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'image/jpeg,image/png,image/jpg'
                }
            });

            if (response.ok) {
                const blob = await response.blob();
                const imageUrl = URL.createObjectURL(blob);

                // Clean up previous blob URL if exists
                const existingImg = avatarDisplay.querySelector('img');
                if (existingImg && existingImg.src.startsWith('blob:')) {
                    URL.revokeObjectURL(existingImg.src);
                }

                avatarDisplay.innerHTML = `<img src="${imageUrl}" alt="Avatar">`;
            } else {
                // Fallback to initial letter
                avatarDisplay.textContent = userName.charAt(0).toUpperCase();
            }
        } catch (error) {
            console.error('Error loading avatar:', error);
            avatarDisplay.textContent = userName.charAt(0).toUpperCase();
        }
    }

    async function handleAvatarUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            showAlert('File size must be less than 2MB', 'error');
            event.target.value = '';
            return;
        }

        // Validate file type
        if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
            showAlert('Only JPG and PNG files are allowed', 'error');
            event.target.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('avatar', file);

        try {
            const token = localStorage.getItem('token');
            const response = await fetch(`${API_URL}/auth/upload-avatar`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok) {
                showAlert('Avatar updated successfully!', 'success');

                // Update avatar display
                const avatarDisplay = document.getElementById('avatarDisplay');
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarDisplay.innerHTML = `<img src="${e.target.result}" alt="Avatar">`;
                };
                reader.readAsDataURL(file);

                // Update local storage user data
                const user = JSON.parse(localStorage.getItem('user'));
                user.avatar_url = data.avatar_url;
                localStorage.setItem('user', JSON.stringify(user));

                // Reload user profile to get updated data
                setTimeout(() => loadUserProfile(), 500);
            } else {
                showAlert(data.message || 'Failed to upload avatar', 'error');
            }
        } catch (error) {
            console.error('Error uploading avatar:', error);
            showAlert('Error uploading avatar', 'error');
        }

        event.target.value = '';
    }

    async function handlePasswordChange(event) {
        event.preventDefault();

        const oldPassword = document.getElementById('oldPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Validate passwords match
        if (newPassword !== confirmPassword) {
            showAlert('New passwords do not match', 'error');
            return;
        }

        // Validate password length
        if (newPassword.length < 6) {
            showAlert('Password must be at least 6 characters', 'error');
            return;
        }

        const btn = document.getElementById('changePasswordBtn');
        const originalBtnText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<div class="spinner"></div> Updating...';

        try {
            const { response, data } = await apiCall('/auth/change-pass', {
                method: 'POST',
                body: JSON.stringify({
                    old_password: oldPassword,
                    new_password: newPassword,
                    new_password_confirmation: confirmPassword
                })
            });

            if (response.ok) {
                showAlert('Password changed successfully!', 'success');
                document.getElementById('changePasswordForm').reset();
            } else {
                showAlert(data.message || 'Failed to change password. Please check your current password.', 'error');
            }
        } catch (error) {
            console.error('Error changing password:', error);
            showAlert('Error changing password', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
        }
    }

    function showAlert(message, type = 'error') {
        const alertContainer = document.getElementById('alertContainer');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';

        alertContainer.innerHTML = `
            <div class="alert ${alertClass}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px; flex-shrink: 0;">
                    ${type === 'success'
                        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
                        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
                    }
                </svg>
                ${message}
            </div>
        `;

        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 5000);
    }
</script>
@endsection

