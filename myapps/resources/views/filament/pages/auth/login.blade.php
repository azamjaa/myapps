<x-filament-panels::page.simple>
    <style>
        /* KEDA Enterprise Login Theme */
        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #fbbf24 100%);
            min-height: 100vh;
        }

        .fi-simple-page {
            background: transparent !important;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            max-width: 480px;
            margin: 0 auto;
        }

        .login-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.15) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
            50% { transform: scale(1.1) rotate(5deg); opacity: 0.8; }
        }

        .logo-container {
            position: relative;
            z-index: 1;
            margin-bottom: 1.5rem;
        }

        .logo-keda {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 24px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 4px solid rgba(251, 191, 36, 0.3);
            position: relative;
            overflow: hidden;
        }

        .logo-keda::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, transparent 100%);
        }

        .logo-keda svg {
            width: 80px;
            height: 80px;
            position: relative;
            z-index: 1;
        }

        .app-title {
            font-size: 2rem;
            font-weight: 800;
            color: white;
            margin: 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        .app-subtitle {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 0.5rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .login-body {
            padding: 2.5rem 2rem 2rem;
        }

        .welcome-text {
            text-align: center;
            margin-bottom: 2rem;
        }

        .welcome-text h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e3a8a;
            margin: 0 0 0.5rem 0;
        }

        .welcome-text p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        /* Custom form styling */
        .fi-fo-text-input input {
            border-radius: 12px !important;
            border: 2px solid #e5e7eb !important;
            padding: 0.875rem 1rem !important;
            font-size: 1rem !important;
            transition: all 0.3s ease !important;
        }

        .fi-fo-text-input input:focus {
            border-color: #1e3a8a !important;
            box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.1) !important;
            outline: none !important;
        }

        .fi-btn {
            border-radius: 12px !important;
            padding: 0.875rem 1.5rem !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            transition: all 0.3s ease !important;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%) !important;
            border: none !important;
        }

        .fi-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 10px 25px rgba(30, 58, 138, 0.3) !important;
        }

        .fi-fo-field-wrp-label label {
            font-weight: 600 !important;
            color: #1e3a8a !important;
            font-size: 0.95rem !important;
        }

        .fi-fo-field-wrp-helper-text {
            color: #6b7280 !important;
            font-size: 0.875rem !important;
        }

        .login-footer {
            text-align: center;
            padding: 1.5rem 2rem 2rem;
            border-top: 1px solid #e5e7eb;
            margin-top: 1rem;
        }

        .login-footer p {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0;
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.05) 0%, rgba(251, 191, 36, 0.05) 100%);
            border-radius: 12px;
            border: 1px solid rgba(30, 58, 138, 0.1);
            margin-top: 1rem;
        }

        .security-badge svg {
            width: 20px;
            height: 20px;
            color: #1e3a8a;
        }

        .security-badge span {
            font-size: 0.875rem;
            color: #1e3a8a;
            font-weight: 600;
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-container {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .login-header {
                padding: 2rem 1.5rem;
            }

            .logo-keda {
                width: 100px;
                height: 100px;
            }

            .logo-keda svg {
                width: 65px;
                height: 65px;
            }

            .app-title {
                font-size: 1.5rem;
            }

            .login-body {
                padding: 2rem 1.5rem 1.5rem;
            }
        }
    </style>

    <div class="login-container">
        <!-- Header with Logo -->
        <div class="login-header">
            <div class="logo-container">
                <div class="logo-keda">
                    <!-- KEDA Logo SVG -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="none">
                        <!-- Shield Background -->
                        <path d="M50 10 L80 25 L80 50 Q80 75 50 90 Q20 75 20 50 L20 25 Z" 
                              fill="url(#kedaGradient)" 
                              stroke="#1e3a8a" 
                              stroke-width="2"/>
                        
                        <!-- Letter K -->
                        <path d="M35 35 L35 65 M35 50 L50 35 M35 50 L50 65" 
                              stroke="#fbbf24" 
                              stroke-width="4" 
                              stroke-linecap="round" 
                              stroke-linejoin="round"/>
                        
                        <!-- Star -->
                        <path d="M65 45 L67 51 L73 51 L68 55 L70 61 L65 57 L60 61 L62 55 L57 51 L63 51 Z" 
                              fill="#fbbf24"/>
                        
                        <defs>
                            <linearGradient id="kedaGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#1e3a8a;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                
                <h1 class="app-title">{{ $this->getHeading() }}</h1>
                <p class="app-subtitle">{{ $this->getSubHeading() }}</p>
            </div>
        </div>

        <!-- Login Form -->
        <div class="login-body">
            <div class="welcome-text">
                <h2>Selamat Kembali</h2>
                <p>Sila log masuk untuk meneruskan</p>
            </div>

            {{ \Filament\Support\Facades\FilamentView::renderHook('panels::auth.login.form.before') }}

            <x-filament-panels::form wire:submit="authenticate">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>

            {{ \Filament\Support\Facades\FilamentView::renderHook('panels::auth.login.form.after') }}
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <div class="security-badge">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>Secure Login with SSO</span>
            </div>
            
            <p style="margin-top: 1.5rem;">
                &copy; {{ date('Y') }} KEDA. All rights reserved.
            </p>
        </div>
    </div>
</x-filament-panels::page.simple>

