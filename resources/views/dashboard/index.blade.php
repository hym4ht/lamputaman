<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard | Garden Monitoring IoT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/css/dashboard.css', 'resources/js/app.js'])
    
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
                'title' => 'Jadwal Lampu & Pompa',
                'subtitle' => 'Atur jam nyala lampu dan durasi pompa dalam satu screen.',
            ],
        ];
        $sidebarItems = [
            'ringkasan' => ['label' => 'Ringkasan', 'icon' => 'bi-grid-1x2', 'route' => 'dashboard'],
            'kontrol' => ['label' => 'Kontrol', 'icon' => 'bi-sliders', 'route' => 'dashboard.kontrol'],
            'jadwal' => ['label' => 'Jadwal', 'icon' => 'bi-calendar-week', 'route' => 'dashboard.jadwal'],
        ];
    @endphp

<div class="app-container"
         data-dashboard-url="/dashboard/data"
         data-toggle-base="/dashboard/control"
         data-lamp-bulk-url="/dashboard/control-lamps" 
         data-default-sensor-range="25m">
        @include('dashboard.partials.sidebar')

        <!-- Main Content -->
        <main class="main-content">
            @include('dashboard.partials.header')

            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            @includeWhen($activeScreen === 'ringkasan', 'dashboard.screens.ringkasan')
            @includeWhen($activeScreen === 'kontrol', 'dashboard.screens.kontrol')
            @includeWhen($activeScreen === 'jadwal', 'dashboard.screens.jadwal')
        </main>
    </div>

    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" id="menuToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>


    
</body>
<x-footer />
</html>
