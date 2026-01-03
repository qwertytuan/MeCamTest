@extends('layouts.app')

@section('title', 'Register - Camera Hub')

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
            <h1>Create Account</h1>
            <p>Sign up to get started with Camera Hub</p>
        </div>

        <div id="alertContainer"></div>

        <form id="registerForm">
            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-input"
                    placeholder="Enter your name"
                    required
                >
                <span class="form-error" id="nameError"></span>
            </div>

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
                    placeholder="Create a password"
                    required
                >
                <span class="form-error" id="passwordError"></span>
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirm Password</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-input"
                    placeholder="Confirm your password"
                    required
                >
                <span class="form-error" id="passwordConfirmationError"></span>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;" id="submitBtn">
                <span id="btnText">Create Account</span>
                <span id="btnSpinner" class="spinner" style="display: none;"></span>
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="{{ url('/login') }}">Sign in</a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('registerForm').addEventListener('submit', async (e) => {
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
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            password: document.getElementById('password').value,
            password_confirmation: document.getElementById('password_confirmation').value
        };

        try {
            const response = await fetch(`${API_URL}/auth/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok) {
                // Show success message
                alertContainer.innerHTML = `
                    <div class="alert alert-success">
                        Registration successful! Please login with your credentials.
                    </div>
                `;

                // Redirect to login page
                setTimeout(() => {
                    window.location.href = '{{ url("/login") }}';
                }, 2000);
            } else {
                // Show validation errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const errorElement = document.getElementById(`${field}Error`);
                        const inputElement = document.getElementById(field);

                        if (errorElement && inputElement) {
                            errorElement.textContent = data.errors[field][0];
                            errorElement.classList.add('show');
                            inputElement.classList.add('error');
                        }
                    });
                } else {
                    alertContainer.innerHTML = `
                        <div class="alert alert-error">
                            ${data.message || 'Registration failed. Please try again.'}
                        </div>
                    `;
                }

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

