@extends('layouts.app')

@section('title', 'Welcome - Camera Hub')

@section('styles')
<style>
    .hero-section {
        min-height: calc(100vh - 64px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        text-align: center;
    }

    .hero-content {
        max-width: 700px;
    }

    .hero-icon {
        width: 120px;
        height: 120px;
        margin: 0 auto 2rem;
        background: linear-gradient(135deg, var(--accent) 0%, #3182ce 100%);
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 20px 40px rgba(66, 153, 225, 0.3);
    }

    .hero-icon svg {
        width: 64px;
        height: 64px;
        color: white;
    }

    .hero-title {
        font-size: 3rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 1rem;
        line-height: 1.2;
    }

    .hero-subtitle {
        font-size: 1.25rem;
        color: var(--text-secondary);
        margin-bottom: 2.5rem;
        line-height: 1.6;
    }

    .hero-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .hero-buttons .btn {
        padding: 0.875rem 2rem;
        font-size: 1.05rem;
        min-width: 150px;
    }

    .features-section {
        padding: 4rem 2rem;
        background: var(--bg-card);
        border-top: 1px solid var(--border);
    }

    .features-container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .features-title {
        text-align: center;
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 3rem;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
    }

    .feature-card {
        padding: 2rem;
        background: var(--bg-main);
        border-radius: 12px;
        border: 1px solid var(--border);
        transition: all 0.3s;
    }

    .feature-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: var(--accent);
    }

    .feature-icon {
        width: 56px;
        height: 56px;
        background: var(--accent);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
    }

    .feature-icon svg {
        width: 28px;
        height: 28px;
        color: white;
    }

    .feature-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.75rem;
    }

    .feature-description {
        color: var(--text-secondary);
        line-height: 1.6;
        font-size: 0.95rem;
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 2rem;
        }

        .hero-subtitle {
            font-size: 1.1rem;
        }

        .hero-buttons {
            flex-direction: column;
        }

        .hero-buttons .btn {
            width: 100%;
        }
    }
</style>
@endsection

@section('content')
<div class="hero-section">
    <div class="hero-content">
        <div class="hero-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
        </div>

        <h1 class="hero-title">Camera Hub</h1>
        <p class="hero-subtitle">
            Manage and monitor your camera streams in real-time with our powerful, easy-to-use platform.
            Stream, control, and share access to multiple cameras seamlessly.
        </p>

        <div class="hero-buttons">
            <a href="{{ url('/register') }}" class="btn btn-primary">
                Get Started
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px; height: 18px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
            <a href="{{ url('/login') }}" class="btn btn-secondary">
                Sign In
            </a>
        </div>
    </div>
</div>

<div class="features-section">
    <div class="features-container">
        <h2 class="features-title">Why Choose Camera Hub?</h2>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Real-Time Streaming</h3>
                <p class="feature-description">
                    View multiple camera feeds simultaneously with low-latency streaming via WebSocket technology.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: var(--success);">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Secure Access</h3>
                <p class="feature-description">
                    JWT-based authentication ensures your camera streams are protected and accessible only to authorized users.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: var(--warning);">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Easy Management</h3>
                <p class="feature-description">
                    Admin interface for adding, editing, and controlling camera streams with automatic resolution and frame rate detection.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: var(--primary);">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="feature-title">User-Friendly Interface</h3>
                <p class="feature-description">
                    Clean, modern design with intuitive controls makes managing cameras effortless for users of all skill levels.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: #8b5cf6;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Responsive Design</h3>
                <p class="feature-description">
                    Access your camera streams from any device - desktop, tablet, or mobile with fully responsive layouts.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon" style="background: #10b981;">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="feature-title">High Performance</h3>
                <p class="feature-description">
                    Optimized streaming with Python backend ensures smooth playback even with multiple concurrent camera feeds.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

