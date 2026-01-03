@extends('layouts.app')

@section('title', 'Login - Camera Hub')

@section('styles')
<style>
    .auth-container {
        min-height: calc(100vh - 64px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    .auth-card {
        background: var(--bg-card);
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
        padding: 2.5rem;
        width: 100%;
        max-width: 420px;
    }

    .auth-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .auth-header h1 {
        font-size: 1.875rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .auth-header p {
        color: var(--text-secondary);
        font-size: 0.9rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        font-size: 0.9rem;
        color: var(--text-primary);
    }

    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 0.95rem;
        transition: all 0.2s;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
    }

    .form-input.error {
        border-color: var(--danger);
    }

    .form-error {
        color: var(--danger);
        font-size: 0.85rem;
        margin-top: 0.25rem;
        display: none;
    }

    .form-error.show {
        display: block;
    }

    .auth-footer {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border);
    }

    .auth-footer a {
        color: var(--accent);
        text-decoration: none;
        font-weight: 500;
    }

    .auth-footer a:hover {
        text-decoration: underline;
    }
</style>
@endsection

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p>Sign in to your account to continue</p>
        </div>

        <div id="alertContainer"></div>

        <form id="loginForm">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input"
                    placeholder="Enter your email"
                    required
                >
                <span class="form-error" id="emailError"></span>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    placeholder="Enter your password"
                    required
                >
                <span class="form-error" id="passwordError"></span>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;" id="submitBtn">
                <span id="btnText">Sign In</span>
                <span id="btnSpinner" class="spinner" style="display: none;"></span>
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="{{ url('/register') }}">Sign up</a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const alertContainer = document.getElementById('alertContainer');

        // Clear previous errors
        document.querySelectorAll('.form-error').forEach(el => el.classList.remove('show'));
        document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));
        alertContainer.innerHTML = '';

        // Disable button
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnSpinner.style.display = 'block';

        const formData = {
            email: document.getElementById('email').value,
            password: document.getElementById('password').value
        };

        try {
            const response = await fetch(`${API_URL}/auth/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok) {
                // Store token and user info (backend returns access_token, not token)
                const token = data.access_token || data.token;
                if (!token) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-error">
                            Invalid response from server. Please try again.
                        </div>
                    `;
                    submitBtn.disabled = false;
                    btnText.style.display = 'block';
                    btnSpinner.style.display = 'none';
                    return;
                }

                localStorage.setItem('token', token);
                localStorage.setItem('user', JSON.stringify(data.user));

                console.log('Login successful, token stored:', token.substring(0, 20) + '...');

                // Show success message
                alertContainer.innerHTML = `
                    <div class="alert alert-success">
                        Login successful! Redirecting...
                    </div>
                `;

                // Redirect to dashboard
                setTimeout(() => {
                    window.location.href = '{{ url("/dashboard") }}';
                }, 1000);
            } else {
                // Show error
                alertContainer.innerHTML = `
                    <div class="alert alert-error">
                        ${data.message || 'Invalid credentials. Please try again.'}
                    </div>
                `;

                // Re-enable button
                submitBtn.disabled = false;
                btnText.style.display = 'block';
                btnSpinner.style.display = 'none';
            }
        } catch (error) {
            alertContainer.innerHTML = `
                <div class="alert alert-error">
                    An error occurred. Please try again.
                </div>
            `;

            // Re-enable button
            submitBtn.disabled = false;
            btnText.style.display = 'block';
            btnSpinner.style.display = 'none';
        }
    });
</script>
@endsection

