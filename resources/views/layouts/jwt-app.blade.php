<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'JWT Auth App')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            text-decoration: none;
        }

        .navbar-menu {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .navbar-menu a {
            color: #333;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .navbar-menu a:hover {
            background: #f0f0f0;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .content-box {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .user-info {
            display: none;
            align-items: center;
            gap: 15px;
        }

        .user-info.logged-in {
            display: flex;
        }

        .user-name {
            color: #666;
            font-weight: 500;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        h1, h2, h3 {
            color: #333;
            margin-bottom: 20px;
        }

        .guest-menu {
            display: flex;
            gap: 10px;
        }

        .auth-menu {
            display: none;
            gap: 10px;
        }

        .auth-menu.logged-in {
            display: flex;
        }

        .guest-menu.logged-in {
            display: none;
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="navbar">
        <a href="/" class="navbar-brand">JWT Auth</a>
        <div class="navbar-menu">
            <div class="guest-menu">
                <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
            </div>
            <div class="auth-menu">
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('products.web') }}">Products</a>
                <div class="user-info">
                    <span class="user-name" id="userName"></span>
                    <button class="btn btn-danger" onclick="logout()">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="content-box">
            <div id="alertContainer"></div>
            @yield('content')
        </div>
    </div>

    <script>
        // JWT Token management
        function setToken(token) {
            localStorage.setItem('jwt_token', token);
        }

        function getToken() {
            return localStorage.getItem('jwt_token');
        }

        function removeToken() {
            localStorage.removeItem('jwt_token');
            localStorage.removeItem('user_data');
        }

        function setUserData(user) {
            localStorage.setItem('user_data', JSON.stringify(user));
        }

        function getUserData() {
            const data = localStorage.getItem('user_data');
            return data ? JSON.parse(data) : null;
        }

        // API call helper
        async function apiCall(url, method = 'GET', data = null) {
            const token = getToken();
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };

            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const options = {
                method,
                headers
            };

            if (data && method !== 'GET') {
                options.body = JSON.stringify(data);
            }
            if (data && method === 'DELETE') {
                delete options.body;
            }

            try {
                const response = await fetch(url, options);
                if (response.status === 204 || response.status === 205) {
                    showAlert("Product Deleted");
                    return null;
                }
                const result = await response.json();
                if(response.status === 204) {
                    showAlert("Product Deleted"); // No content
                }


                if (!response.ok) {
                    throw new Error(result.error || result.message || 'API call failed');
                }

                return result;
            } catch (error) {
                console.error('API Error:', error);
                throw error;
            }
        }

        // Show alert
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-${type} show">
                    ${message}
                </div>
            `;

            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) alert.classList.remove('show');
            }, 5000);
        }

        // Check authentication status
        function checkAuth() {
            const token = getToken();
            const user = getUserData();

            if (token && user) {
                document.querySelector('.guest-menu').classList.add('logged-in');
                document.querySelector('.auth-menu').classList.add('logged-in');
                document.querySelector('.user-info').classList.add('logged-in');
                document.getElementById('userName').textContent = `Hello, ${user.name}!`;
                return true;
            } else {
                document.querySelector('.guest-menu').classList.remove('logged-in');
                document.querySelector('.auth-menu').classList.remove('logged-in');
                document.querySelector('.user-info').classList.remove('logged-in');
                return false;
            }
        }

        // Logout
        async function logout() {
            try {
                await apiCall('/api/auth/logout', 'POST');
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                removeToken();
                window.location.href = '/login';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkAuth();
        });
    </script>
    @stack('scripts')
</body>
</html>

