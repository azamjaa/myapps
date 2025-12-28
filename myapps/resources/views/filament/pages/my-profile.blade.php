<x-filament-panels::page>
    <style>
        /* Profile Page Custom Styling */
        .profile-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(251, 191, 36, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }

        .profile-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(251, 191, 36, 0.5);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .profile-info h1 {
            font-size: 2rem;
            font-weight: 800;
            margin: 0 0 0.5rem 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .profile-info p {
            margin: 0.25rem 0;
            opacity: 0.95;
            font-size: 1rem;
        }

        /* Activity Feed Styling */
        .activity-feed {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-top: 2rem;
        }

        .activity-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f3f4f6;
        }

        .activity-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e3a8a;
            margin: 0;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .activity-timeline {
            position: relative;
            padding-left: 2.5rem;
        }

        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, #1e3a8a, #fbbf24);
        }

        .activity-item {
            position: relative;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
        }

        .activity-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .activity-dot {
            position: absolute;
            left: -2.5rem;
            top: 4px;
            width: 12px;
            height: 12px;
            background: white;
            border: 3px solid #1e3a8a;
            border-radius: 50%;
            box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.1);
        }

        .activity-card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 1.25rem;
            border-left: 4px solid #fbbf24;
            transition: all 0.3s ease;
        }

        .activity-card:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateX(4px);
        }

        .activity-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .activity-action {
            font-weight: 600;
            color: #1e3a8a;
            font-size: 0.95rem;
        }

        .activity-time {
            color: #6b7280;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .activity-changes {
            display: grid;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .change-row {
            display: grid;
            grid-template-columns: 150px 1fr 1fr;
            gap: 1rem;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .change-field {
            font-weight: 600;
            color: #4b5563;
        }

        .change-old {
            color: #dc2626;
            text-decoration: line-through;
            opacity: 0.7;
        }

        .change-new {
            color: #059669;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }

        .empty-state svg {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            opacity: 0.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-content {
                flex-direction: column;
                text-align: center;
            }

            .change-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .activity-timeline {
                padding-left: 1.5rem;
            }

            .activity-timeline::before {
                left: 10px;
            }

            .activity-dot {
                left: -1.35rem;
            }
        }
    </style>

    {{-- Profile Header --}}
    <div class="profile-header">
        <div class="profile-content">
            @if($this->getStaf()->gambar)
                <img src="{{ asset('storage/' . $this->getStaf()->gambar) }}" 
                     alt="{{ $this->getStaf()->nama }}" 
                     class="profile-avatar">
            @else
                <div class="profile-avatar" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 800; color: white;">
                    {{ substr($this->getStaf()->nama, 0, 1) }}
                </div>
            @endif

            <div class="profile-info">
                <h1>{{ $this->getStaf()->nama }}</h1>
                <p>📋 {{ $this->getStaf()->no_staf }} • 🆔 {{ $this->getStaf()->no_kp }}</p>
                <p>💼 {{ $this->getStaf()->jawatan->nama_jawatan ?? 'N/A' }} • 
                   🏢 {{ $this->getStaf()->bahagian->nama_bahagian ?? 'N/A' }}</p>
                <p>📧 {{ $this->getStaf()->emel }} • 📱 {{ $this->getStaf()->telefon }}</p>
            </div>
        </div>
    </div>

    {{-- Staff Information Infolist --}}
    {{ $this->staffInfolist }}

    {{-- Activity Feed --}}
    <div class="activity-feed">
        <div class="activity-header">
            <div class="activity-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px; height: 24px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2>Sejarah Aktiviti Saya</h2>
        </div>

        @php
            $activities = $this->getActivityFeed();
        @endphp

        @if($activities->count() > 0)
            <div class="activity-timeline">
                @foreach($activities as $activity)
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div class="activity-card">
                            <div class="activity-meta">
                                <span class="activity-action">
                                    @switch($activity->aksi)
                                        @case('create')
                                            ➕ Rekod Dicipta
                                            @break
                                        @case('update')
                                            ✏️ Rekod Dikemaskini
                                            @break
                                        @case('delete')
                                            🗑️ Rekod Dipadam
                                            @break
                                        @default
                                            📝 {{ ucfirst($activity->aksi) }}
                                    @endswitch
                                </span>
                                <span class="activity-time">
                                    🕐 {{ $activity->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 0.5rem;">
                                <strong>Jadual:</strong> {{ $activity->nama_jadual }}
                                @if($activity->id_rekod)
                                    • <strong>ID:</strong> {{ $activity->id_rekod }}
                                @endif
                            </div>

                            @if($activity->data_lama || $activity->data_baru)
                                @php
                                    $dataLama = is_string($activity->data_lama) ? json_decode($activity->data_lama, true) : $activity->data_lama;
                                    $dataBaru = is_string($activity->data_baru) ? json_decode($activity->data_baru, true) : $activity->data_baru;
                                    
                                    if ($activity->aksi === 'update' && is_array($dataLama) && is_array($dataBaru)) {
                                        $changes = [];
                                        foreach ($dataBaru as $key => $newValue) {
                                            if (isset($dataLama[$key]) && $dataLama[$key] != $newValue) {
                                                $changes[$key] = [
                                                    'old' => $dataLama[$key],
                                                    'new' => $newValue
                                                ];
                                            }
                                        }
                                    } else {
                                        $changes = [];
                                    }
                                @endphp

                                @if(!empty($changes))
                                    <div class="activity-changes">
                                        @foreach($changes as $field => $change)
                                            <div class="change-row">
                                                <div class="change-field">{{ ucwords(str_replace('_', ' ', $field)) }}:</div>
                                                <div class="change-old">{{ $change['old'] ?? 'N/A' }}</div>
                                                <div class="change-new">→ {{ $change['new'] ?? 'N/A' }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($activity->aksi === 'create' && is_array($dataBaru))
                                    <div style="margin-top: 0.75rem; padding: 0.75rem; background: #f0fdf4; border-radius: 8px; font-size: 0.9rem;">
                                        <strong>Rekod baru dicipta dengan {{ count($dataBaru) }} field</strong>
                                    </div>
                                @endif
                            @endif

                            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem; color: #6b7280;">
                                📅 {{ $activity->created_at->format('d/m/Y H:i:s') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #4b5563; margin-bottom: 0.5rem;">
                    Tiada Rekod Aktiviti
                </h3>
                <p>Sejarah perubahan data anda akan dipaparkan di sini</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>

