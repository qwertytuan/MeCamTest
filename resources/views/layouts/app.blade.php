<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Camera Hub')</title>
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
            --bg-main: #f7fafc;
            --bg-card: #ffffff;
            --text-primary: #1a202c;
            --text-secondary: #718096;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-main);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Navbar */
        .navbar {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 64px;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand svg {
            width: 32px;
            height: 32px;
        }

        .navbar-menu {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .navbar-menu a, .navbar-menu button {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 0.9rem;
            font-weight: 500;
            border: none;
            background: none;
            cursor: pointer;
        }

        .navbar-menu a:hover, .navbar-menu button:hover {
            background: var(--bg-main);
            color: var(--text-primary);
        }

        .navbar-menu .active {
            color: var(--accent);
            background: rgba(66, 153, 225, 0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: var(--bg-main);
            border-radius: 50px;
            margin-left: 1rem;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-name {
            font-size: 0.9rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .admin-badge {
            background: var(--warning);
            color: white;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow-sm);
        }

        .alert-success {
            background: #f0fff4;
            color: #22543d;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: #fff5f5;
            color: #742a2a;
            border-left: 4px solid var(--danger);
        }

        .alert-warning {
            background: #fffaf0;
            color: #7c2d12;
            border-left: 4px solid var(--warning);
        }

        /* Buttons */
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            border: none;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            justify-content: center;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            background: #3182ce;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover:not(:disabled) {
            background: #38a169;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover:not(:disabled) {
            background: #e53e3e;
        }

        .btn-secondary {
            background: var(--bg-main);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover:not(:disabled) {
            background: #edf2f7;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.85rem;
        }

        .btn-icon {
            padding: 0.5rem;
            width: 36px;
            height: 36px;
        }

        /* Loading Spinner */
        .spinner {
            border: 2px solid var(--border);
            border-top: 2px solid var(--accent);
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .navbar-container {
                padding: 0 1rem;
            }

            .navbar-menu {
                gap: 0.25rem;
            }

            .navbar-menu a, .navbar-menu button {
                padding: 0.5rem;
                font-size: 0.85rem;
            }

            .user-info {
                margin-left: 0.5rem;
                padding: 0.375rem 0.75rem;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="{{ url('/') }}" class="navbar-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                Camera Hub
            </a>

            <div class="navbar-menu" id="navbarMenu">
                <!-- Will be populated by JavaScript based on auth status -->
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <script>
        // Global API base URL
        const API_URL = '/api';

        // Check authentication and setup navbar
        document.addEventListener('DOMContentLoaded', function() {
            checkAuth();
        });

        function checkAuth() {
            const token = localStorage.getItem('token');
            const user = JSON.parse(localStorage.getItem('user') || 'null');
            const navbarMenu = document.getElementById('navbarMenu');
            const currentPath = window.location.pathname;

            // Public pages that don't need auth
            const publicPages = ['/', '/login', '/register'];

            if (token && user) {
                const isAdmin = user.is_admin;

                navbarMenu.innerHTML = `
                    <a href="{{ url('/dashboard') }}" class="${currentPath === '/dashboard' ? 'active' : ''}">
                        Cameras
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

                // Load avatar image if available
                if (user.avatar_url) {
                    loadNavbarAvatar(user.avatar_url, user.name);
                }
            } else {
                navbarMenu.innerHTML = `
                    <a href="{{ url('/login') }}">Login</a>
                    <a href="{{ url('/register') }}">Register</a>
                `;

                // Only redirect to login if on protected page (not on public pages)
                const protectedPages = ['/dashboard', '/profile', '/admin/cameras'];
                if (protectedPages.includes(currentPath) && !publicPages.includes(currentPath)) {
                    console.log('Redirecting to login - no valid token found');
                    window.location.href = '{{ url("/login") }}';
                }
            }
        }

        async function loadNavbarAvatar(avatarUrl, userName) {
            const navbarAvatar = document.getElementById('navbarAvatar');
            if (!navbarAvatar) return;

            const token = localStorage.getItem('token');
            const filename = avatarUrl.includes('/') ? avatarUrl.split('/').pop() : avatarUrl;

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
                    navbarAvatar.style.backgroundImage = `url(${imageUrl})`;
                    navbarAvatar.style.backgroundSize = 'cover';
                    navbarAvatar.style.backgroundPosition = 'center';
                    navbarAvatar.textContent = '';
                }
            } catch (error) {
                console.error('Error loading navbar avatar:', error);
            }
        }

        function logout() {
            const token = localStorage.getItem('token');

            fetch(`${API_URL}/auth/logout`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            }).finally(() => {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                console.log('Logged out, redirecting to login');
                window.location.href = '{{ url("/login") }}';
            });
        }

        // Helper function for API calls
        async function apiCall(endpoint, options = {}) {
            const token = localStorage.getItem('token');
            const defaultHeaders = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };

            if (token) {
                defaultHeaders['Authorization'] = `Bearer ${token}`;
            }

            const config = {
                ...options,
                headers: {
                    ...defaultHeaders,
                    ...options.headers
                }
            };

            try {
                const response = await fetch(`${API_URL}${endpoint}`, config);
                const data = await response.json();

                if (response.status === 401) {
                    localStorage.removeItem('token');
                    localStorage.removeItem('user');
                    window.location.href = '{{ url("/login") }}';
                    throw new Error('Unauthorized');
                }

                return { response, data };
            } catch (error) {
                console.error('API call error:', error);
                throw error;
            }
        }
    </script>

    @yield('scripts')
</body>
</html>

