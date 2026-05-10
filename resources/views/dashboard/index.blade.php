<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard | Smart Garden IoT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --color-bg: #fafafa;
            --color-surface: #ffffff;
            --color-border: #e8e8e8;
            --color-text: #1a1a1a;
            --color-text-muted: #737373;
            --color-accent: #2563eb;
            --color-accent-hover: #1d4ed8;
            --color-success: #059669;
            --color-danger: #dc2626;
            --color-warning: #f59e0b;
            
            --spacing-xs: 0.5rem;
            --spacing-sm: 0.75rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            --spacing-2xl: 3rem;
            
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 2px 8px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 4px 16px rgba(0, 0, 0, 0.08);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Layout */
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background: var(--color-surface);
            border-right: 1px solid var(--color-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            padding: var(--spacing-xl) var(--spacing-lg);
            border-bottom: 1px solid var(--color-border);
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .brand-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        
        .brand-text h1 {
            font-size: 15px;
            font-weight: 600;
            margin: 0;
            color: var(--color-text);
        }
        
        .brand-text p {
            font-size: 12px;
            color: var(--color-text-muted);
            margin: 0;
        }
        
        .nav-menu {
            flex: 1;
            padding: var(--spacing-lg);
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm) var(--spacing-md);
            margin-bottom: var(--spacing-xs);
            border-radius: var(--radius-sm);
            text-decoration: none;
            color: var(--color-text-muted);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .nav-item:hover {
            background: #f5f5f5;
            color: var(--color-text);
        }
        
        .nav-item.active {
            background: #f0f9ff;
            color: var(--color-accent);
        }
        
        .nav-item i {
            font-size: 18px;
        }
        
        .sidebar-footer {
            padding: var(--spacing-lg);
            border-top: 1px solid var(--color-border);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
        }
        
        .user-avatar {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-label {
            font-size: 11px;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-text);
        }
        
        .btn-logout {
            width: 100%;
            padding: var(--spacing-sm) var(--spacing-md);
            background: transparent;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            color: var(--color-text-muted);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-xs);
        }
        
        .btn-logout:hover {
            background: #fef2f2;
            border-color: #fecaca;
            color: var(--color-danger);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            min-width: 0;
            margin-left: 260px;
            padding: var(--spacing-2xl);
        }
        
        .page-header {
            margin-bottom: var(--spacing-2xl);
        }
        
        .page-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: var(--spacing-xs);
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-text);
            margin-bottom: var(--spacing-xs);
        }
        
        .page-subtitle {
            font-size: 15px;
            color: var(--color-text-muted);
        }
        
        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-2xl);
        }
        
        .metric-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            transition: all 0.2s ease;
        }
        
        .metric-card:hover {
            box-shadow: var(--shadow-md);
        }
        
        .metric-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--spacing-md);
        }
        
        .metric-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metric-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        
        .metric-card.temperature .metric-icon {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .metric-card.humidity .metric-icon {
            background: #dbeafe;
            color: #3b82f6;
        }
        
        .metric-card.status .metric-icon {
            background: #f3e8ff;
            color: #9333ea;
        }
        
        .metric-card.pump .metric-icon {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .metric-value {
            font-size: 36px;
            font-weight: 700;
            color: var(--color-text);
            margin-bottom: var(--spacing-xs);
            line-height: 1;
        }
        
        .metric-unit {
            font-size: 20px;
            font-weight: 600;
            color: var(--color-text-muted);
            margin-left: 4px;
        }
        
        .metric-value.compact {
            font-size: 18px;
            font-weight: 600;
        }
        
        .metric-note {
            font-size: 12px;
            color: var(--color-text-muted);
        }
        
        /* Chart Section */
        .chart-section {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-2xl);
        }
        
        .section-header {
            margin-bottom: var(--spacing-xl);
        }

        .section-header.chart-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--spacing-lg);
        }
        
        .section-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: var(--spacing-xs);
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--color-text);
        }

        .chart-range-switch {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            justify-content: flex-end;
            padding: 4px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            background: #f7f7f7;
        }

        .chart-range-button {
            min-height: 32px;
            padding: 6px 10px;
            border: 0;
            border-radius: 5px;
            background: transparent;
            color: var(--color-text-muted);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .chart-range-button:hover {
            color: var(--color-text);
            background: #ffffff;
        }

        .chart-range-button.active {
            color: #ffffff;
            background: var(--color-accent);
            box-shadow: var(--shadow-sm);
        }
        
        .chart-container {
            position: relative;
            height: 320px;
            width: 100%;
        }
        
        /* Control Panel */
        .control-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }
        
        .control-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-lg);
            background: #fafafa;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }
        
        .control-item:hover {
            background: #f5f5f5;
        }
        
        .control-info {
            flex: 1;
        }
        
        .control-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 2px;
        }
        
        .control-status {
            font-size: 13px;
            color: var(--color-text-muted);
        }
        
        .control-status.on {
            color: var(--color-success);
            font-weight: 500;
        }
        
        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 28px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #d1d5db;
            transition: 0.3s;
            border-radius: 28px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: var(--color-accent);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }
        
        /* Schedule Section */
        .schedule-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: var(--spacing-xl);
        }
        
        .schedule-form {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
        }
        
        .form-group {
            margin-bottom: var(--spacing-lg);
        }
        
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: var(--spacing-xs);
        }
        
        .form-input {
            width: 100%;
            padding: var(--spacing-sm) var(--spacing-md);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-sm);
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--spacing-md);
        }
        
        .day-selector {
            display: flex;
            gap: var(--spacing-xs);
            flex-wrap: wrap;
        }

        .every-day-option {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            margin-bottom: var(--spacing-sm);
            color: var(--color-text);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .every-day-option input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .day-checkbox {
            position: relative;
        }
        
        .day-checkbox input {
            position: absolute;
            opacity: 0;
        }
        
        .day-label {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border: 1px solid var(--color-border);
            border-radius: 50%;
            font-size: 11px;
            font-weight: 600;
            color: var(--color-text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
        }
        
        .day-checkbox input:checked + .day-label {
            background: var(--color-accent);
            border-color: var(--color-accent);
            color: white;
        }
        
        .day-label:hover {
            border-color: var(--color-accent);
        }
        
        .btn-primary {
            width: 100%;
            padding: var(--spacing-md);
            background: var(--color-accent);
            border: none;
            border-radius: var(--radius-sm);
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background: var(--color-accent-hover);
        }
        
        .schedule-list {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
        }
        
        .schedule-items {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }
        
        .schedule-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-md);
            background: #fafafa;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }
        
        .schedule-item:hover {
            background: #f5f5f5;
        }
        
        .schedule-details h4 {
            font-size: 15px;
            font-weight: 600;
            color: var(--color-text);
            margin-bottom: 4px;
        }
        
        .schedule-meta {
            font-size: 12px;
            color: var(--color-text-muted);
        }
        
        .schedule-actions {
            display: flex;
            gap: var(--spacing-xs);
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .badge.active {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge.inactive {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .btn-sm {
            padding: 6px 12px;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            background: white;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-sm:hover {
            background: #f9fafb;
        }
        
        .btn-danger {
            border-color: #fee2e2;
            color: var(--color-danger);
        }
        
        .btn-danger:hover {
            background: #fef2f2;
        }
        
        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--color-text-muted);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: var(--spacing-md);
            opacity: 0.3;
        }
        
        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            position: fixed;
            bottom: var(--spacing-lg);
            right: var(--spacing-lg);
            width: 56px;
            height: 56px;
            background: var(--color-accent);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 24px;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
        }
        
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1000;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .sidebar-backdrop.open {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                padding: var(--spacing-lg);
            }
            
            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .schedule-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            /* Perbaikan untuk grafik di mobile */
            .chart-section {
                padding: var(--spacing-md);
                margin-bottom: var(--spacing-lg);
            }
            
            .chart-container {
                height: 400px; /* Tinggi grafik lebih besar di mobile */
                min-height: 400px;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* Pastikan canvas grafik responsif */
            .chart-container canvas {
                max-width: 100%;
                height: 100% !important;
            }
            
            /* Header section lebih compact di mobile */
            .page-header {
                margin-bottom: var(--spacing-lg);
            }
            
            .page-title {
                font-size: 24px;
            }
            
            .page-subtitle {
                font-size: 14px;
            }
            
            .section-header {
                margin-bottom: var(--spacing-md);
            }

            .section-header.chart-header {
                align-items: stretch;
                flex-direction: column;
            }

            .chart-range-switch {
                justify-content: flex-start;
            }
            
            .section-title {
                font-size: 18px;
            }
            
            /* Metric cards lebih compact */
            .metric-card {
                padding: var(--spacing-md);
            }
            
            .metric-value {
                font-size: 32px;
            }
            
            .metric-unit {
                font-size: 18px;
            }
        }
        
        /* Untuk layar sangat kecil */
        @media (max-width: 480px) {
            .chart-container {
                height: 350px;
                min-height: 350px;
            }
            
            .main-content {
                padding: var(--spacing-md);
                padding-bottom: 80px; /* Space untuk floating button */
            }
            
            .metric-value {
                font-size: 28px;
            }
            
            .page-title {
                font-size: 20px;
            }
        }
        
        /* Alert */
        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-sm);
            margin-bottom: var(--spacing-lg);
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body>
    @php
        $profileName = auth()->user()?->name ?? 'User';
        $screenMeta = [
            'ringkasan' => [
                'title' => 'Ringkasan & Grafik',
                'subtitle' => 'Pantau kondisi taman terbaru dan tren sensor terbaru.',
            ],
            'kontrol' => [
                'title' => 'Kontrol Perangkat',
                'subtitle' => 'Kendalikan relay lampu dan pompa dari satu screen.',
            ],
            'jadwal' => [
                'title' => 'Jadwal Pompa',
                'subtitle' => 'Atur alarm penyiraman otomatis dan status jadwal.',
            ],
        ];
        $sidebarItems = [
            'ringkasan' => ['label' => 'Ringkasan', 'icon' => 'bi-grid-1x2', 'route' => 'dashboard'],
            'kontrol' => ['label' => 'Kontrol', 'icon' => 'bi-sliders', 'route' => 'dashboard.kontrol'],
            'jadwal' => ['label' => 'Jadwal', 'icon' => 'bi-calendar-week', 'route' => 'dashboard.jadwal'],
        ];
    @endphp

    <div class="app-container"
         data-dashboard-url="{{ route('dashboard.data') }}"
         data-toggle-base="{{ url('/dashboard/control') }}"
         data-default-sensor-range="25m">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="brand">
                    <div class="brand-icon">
                        <i class="bi bi-flower1"></i>
                    </div>
                    <div class="brand-text">
                        <h1>Lampu Taman</h1>
                        <p>Smart Garden IoT</p>
                    </div>
                </div>
            </div>

            <nav class="nav-menu">
                @foreach ($sidebarItems as $screen => $item)
                    <a href="{{ route($item['route']) }}" 
                       class="nav-item {{ $activeScreen === $screen ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="user-avatar">{{ str($profileName)->trim()->substr(0, 1)->upper() }}</div>
                    <div class="user-info">
                        <div class="user-label">Profil</div>
                        <div class="user-name">{{ $profileName }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-logout" type="submit">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="page-header">
                <div class="page-label">Dashboard</div>
                <h1 class="page-title">{{ $screenMeta[$activeScreen]['title'] }}</h1>
                <p class="page-subtitle">{{ $screenMeta[$activeScreen]['subtitle'] }}</p>
            </header>

            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            @if ($activeScreen === 'ringkasan')
            <!-- Metrics -->
            <div class="metrics-grid">
                <div class="metric-card temperature">
                    <div class="metric-header">
                        <div class="metric-label">Suhu</div>
                        <div class="metric-icon">
                            <i class="bi bi-thermometer-half"></i>
                        </div>
                    </div>
                    <div class="metric-value">
                        <span id="temperatureValue">{{ $latest ? number_format($latest->suhu, 1) : '--' }}</span>
                        <span class="metric-unit">&deg;C</span>
                    </div>
                    <p class="metric-note" id="latestTemperatureTime">
                        {{ $latest?->created_at?->timezone(config('app.timezone'))->format('d M Y H:i:s') ?? 'Belum ada data sensor' }}
                    </p>
                </div>

                <div class="metric-card humidity">
                    <div class="metric-header">
                        <div class="metric-label">Kelembaban</div>
                        <div class="metric-icon">
                            <i class="bi bi-droplet-half"></i>
                        </div>
                    </div>
                    <div class="metric-value">
                        <span id="humidityValue">{{ $latest ? number_format($latest->kelembaban, 1) : '--' }}</span>
                        <span class="metric-unit">%</span>
                    </div>
                    <p class="metric-note" id="latestHumidityTime">
                        {{ $latest?->created_at?->timezone(config('app.timezone'))->format('d M Y H:i:s') ?? 'Menunggu NodeMCU' }}
                    </p>
                </div>

                <div class="metric-card status">
                    <div class="metric-header">
                        <div class="metric-label">Status API</div>
                        <div class="metric-icon">
                            <i class="bi bi-router"></i>
                        </div>
                    </div>
                    <div class="metric-value compact" id="apiStatus">Siap</div>
                    <p class="metric-note" id="lastRefresh">Data akan diperbarui otomatis</p>
                </div>

                <div class="metric-card pump">
                    <div class="metric-header">
                        <div class="metric-label">Pompa</div>
                        <div class="metric-icon">
                            <i class="bi bi-power"></i>
                        </div>
                    </div>
                    <div class="metric-value compact" id="pumpMode">
                        {{ $pumpStatus['effective_active'] ? $pumpStatus['source'] : 'OFF' }}
                    </div>
                    <p class="metric-note" id="pumpNote">
                        @if ($pumpStatus['manual_active'])
                            Tombol manual aktif
                        @elseif ($pumpStatus['automatic_active'])
                            Jadwal aktif: {{ $pumpStatus['active_schedule']['name'] }}
                        @else
                            Menunggu jadwal/manual
                        @endif
                    </p>
                </div>
            </div>

            <!-- Chart -->
            <section class="chart-section">
                <div class="section-header chart-header">
                    <div>
                        <div class="section-label" id="sensorRangeLabel">25 Menit Terakhir</div>
                        <h2 class="section-title">Grafik Sensor</h2>
                    </div>

                    <div class="chart-range-switch" aria-label="Rentang grafik sensor">
                        <button class="chart-range-button" type="button" data-sensor-range="1m" aria-pressed="false">1 Menit</button>
                        <button class="chart-range-button" type="button" data-sensor-range="5m" aria-pressed="false">5 Menit</button>
                        <button class="chart-range-button active" type="button" data-sensor-range="25m" aria-pressed="true">25 Menit</button>
                        <button class="chart-range-button" type="button" data-sensor-range="1h" aria-pressed="false">1 Jam</button>
                        <button class="chart-range-button" type="button" data-sensor-range="1d" aria-pressed="false">1 Hari</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="sensorChart"></canvas>
                </div>
            </section>

            @elseif ($activeScreen === 'kontrol')
            <!-- Control Panel -->
            <section class="chart-section">
                <div class="section-header">
                    <div class="section-label">Relay Active Low</div>
                    <h2 class="section-title">Kontrol Perangkat</h2>
                </div>

                <div class="control-list">
                    @foreach ($devices as $device => $label)
                        <div class="control-item">
                            <div class="control-info">
                                <div class="control-name">{{ $label }}</div>
                                <div class="control-status {{ ($controls[$device] ?? 0) ? 'on' : '' }}" data-device-status="{{ $device }}">
                                    @if ($device === 'pompa' && ($controls[$device] ?? 0))
                                        ON ({{ $pumpStatus['source'] }})
                                    @else
                                        {{ ($controls[$device] ?? 0) ? 'ON' : 'OFF' }}
                                    @endif
                                </div>
                            </div>

                            <label class="toggle-switch">
                                <input type="checkbox" 
                                       class="control-toggle"
                                       data-device="{{ $device }}"
                                       @checked($manualControls[$device] ?? false)>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </section>

            @elseif ($activeScreen === 'jadwal')
            <!-- Schedule Section -->
            <div class="schedule-grid">
                <!-- Form -->
                <div class="schedule-form">
                    <div class="section-header">
                        <div class="section-label">Alarm Pompa</div>
                        <h2 class="section-title">Jadwal Otomatis</h2>
                    </div>

                    <form method="POST" action="{{ route('dashboard.pump-schedules.store') }}">
                        @csrf
                        <input type="hidden" name="is_enabled" value="0">

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="scheduleName">Nama</label>
                                <input class="form-input" 
                                       id="scheduleName"
                                       name="name"
                                       type="text"
                                       maxlength="80"
                                       value="{{ old('name') }}"
                                       placeholder="Pagi">
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="startTime">Jam Mulai</label>
                                <input class="form-input"
                                       id="startTime"
                                       name="start_time"
                                       type="time"
                                       value="{{ old('start_time', '06:00') }}"
                                       required>
                                @error('start_time')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="durationMinutes">Durasi (menit)</label>
                                <input class="form-input"
                                       id="durationMinutes"
                                       name="duration_minutes"
                                       type="number"
                                       min="1"
                                       max="1440"
                                       value="{{ old('duration_minutes', 10) }}"
                                       required>
                                @error('duration_minutes')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Pilih Hari</label>
                            <label class="every-day-option" for="everyDayToggle">
                                <input type="checkbox"
                                       id="everyDayToggle"
                                       @checked(collect(old('days', array_keys($dayLabels)))->count() === count($dayLabels))>
                                <span>Setiap hari</span>
                            </label>
                            <div class="day-selector">
                                @foreach ($dayLabels as $day => $label)
                                    <div class="day-checkbox">
                                        <input type="checkbox"
                                               class="schedule-day"
                                               name="days[]"
                                               id="day-{{ $day }}"
                                               value="{{ $day }}"
                                               @checked(collect(old('days', array_keys($dayLabels)))->contains((string) $day) || collect(old('days', array_keys($dayLabels)))->contains($day))>
                                        <label class="day-label" for="day-{{ $day }}">
                                            {{ substr($label, 0, 3) }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('days')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" 
                                       id="scheduleEnabled" 
                                       name="is_enabled" 
                                       value="1" 
                                       checked
                                       style="width: 16px; height: 16px; cursor: pointer;">
                                <span class="form-label" style="margin: 0; font-size: 14px;">Aktifkan Langsung</span>
                            </label>
                        </div>

                        <button class="btn-primary" type="submit">Tambah Jadwal</button>
                    </form>
                </div>

                <!-- List -->
                <div class="schedule-list">
                    <div class="section-header">
                        <div class="section-label">Daftar Alarm</div>
                        <h2 class="section-title">Jadwal Tersimpan</h2>
                    </div>

                    <div class="schedule-items">
                        @forelse ($pumpSchedules as $schedule)
                            <div class="schedule-item">
                                <div class="schedule-details">
                                    <h4>{{ $schedule->name }}</h4>
                                    <div class="schedule-meta">
                                        {{ $schedule->daysLabel() }} · {{ $schedule->startsAtLabel() }} · {{ $schedule->duration_minutes }} menit
                                    </div>
                                </div>

                                <div class="schedule-actions">
                                    <span class="badge {{ $schedule->is_enabled ? 'active' : 'inactive' }}">
                                        {{ $schedule->is_enabled ? 'Aktif' : 'Mati' }}
                                    </span>

                                    <form method="POST" action="{{ route('dashboard.pump-schedules.toggle', $schedule) }}" style="display: inline;">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_enabled" value="{{ $schedule->is_enabled ? 0 : 1 }}">
                                        <button class="btn-sm" type="submit">
                                            {{ $schedule->is_enabled ? 'Matikan' : 'Aktifkan' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('dashboard.pump-schedules.destroy', $schedule) }}" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-sm btn-danger" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <p>Belum ada jadwal pompa.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif
        </main>
    </div>

    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" id="menuToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    
    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');

        menuToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            sidebarBackdrop.classList.toggle('open');
        });

        sidebarBackdrop?.addEventListener('click', () => {
            sidebar.classList.remove('open');
            sidebarBackdrop.classList.remove('open');
        });
    </script>
</body>
</html>
