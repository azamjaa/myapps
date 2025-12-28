<x-filament-widgets::widget>
    <style>
        .app-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .app-card {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border-radius: 1rem;
            padding: 1.5rem;
            color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .app-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(251, 191, 36, 0.1), transparent);
            transform: rotate(45deg);
            transition: all 0.6s ease;
        }
        
        .app-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        }
        
        .app-card:hover::before {
            right: 150%;
        }
        
        .app-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            border: 2px solid rgba(251, 191, 36, 0.3);
        }
        
        .app-icon img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }
        
        .app-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .app-description {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .app-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(251, 191, 36, 0.9);
            color: #1e3a8a;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        
        .app-sso {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(16, 185, 129, 0.9);
            color: white;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .dashboard-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, white, #fbbf24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .dashboard-subtitle {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.85);
        }
    </style>

    <div class="dashboard-header">
        <h1 class="dashboard-title">🏢 Portal MyApps KEDA</h1>
        <p class="dashboard-subtitle">
            Single Sign-On Hub & Single Source of Truth untuk Semua Aplikasi KEDA
        </p>
    </div>

    @php
        $applications = $this->getApplications();
    @endphp

    @if($applications->count() > 0)
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-1">
                📱 Aplikasi Tersedia
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
                Klik pada aplikasi untuk akses terus
            </p>
        </div>

        <div class="app-grid">
            @foreach($applications as $app)
                <a href="{{ $app->url_aplikasi }}" 
                   target="_blank"
                   class="app-card" 
                   style="background: linear-gradient(135deg, {{ $app->warna_bg ?? '#1e3a8a' }} 0%, {{ $app->warna_bg ? $app->warna_bg . 'cc' : '#3b82f6' }} 100%);">
                    
                    <div class="app-icon">
                        @if($app->logo_aplikasi)
                            <img src="{{ asset('storage/' . $app->logo_aplikasi) }}" alt="{{ $app->nama_aplikasi }}">
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        @endif
                    </div>
                    
                    <h3 class="app-title">{{ $app->nama_aplikasi }}</h3>
                    
                    <p class="app-description">
                        {{ $app->penerangan_aplikasi ?? 'Aplikasi ' . $app->nama_aplikasi }}
                    </p>
                    
                    <div class="mt-4">
                        <span class="app-badge">
                            {{ $app->kategori->nama_kategori ?? 'Aplikasi' }}
                        </span>
                        
                        @if($app->sso_comply == 1)
                            <span class="app-sso">
                                ✓ SSO Comply
                            </span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <div class="text-6xl mb-4">📱</div>
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Tiada Aplikasi Aktif
            </h3>
            <p class="text-gray-500 dark:text-gray-400">
                Aplikasi akan dipaparkan di sini apabila ditambah
            </p>
        </div>
    @endif
</x-filament-widgets::widget>

