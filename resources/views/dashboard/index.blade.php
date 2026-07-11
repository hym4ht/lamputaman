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

<x-navbar :is-dashboard="true" />

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

    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    @if(env('FIREBASE_PROJECT_ID'))
    <!-- Firebase Cloud Messaging Setup -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js"></script>
    <script>
        window.firebaseConfig = {
            apiKey: "{{ env('FIREBASE_API_KEY') }}",
            authDomain: "{{ env('FIREBASE_AUTH_DOMAIN') }}",
            projectId: "{{ env('FIREBASE_PROJECT_ID') }}",
            storageBucket: "{{ env('FIREBASE_STORAGE_BUCKET') }}",
            messagingSenderId: "{{ env('FIREBASE_MESSAGING_SENDER_ID') }}",
            appId: "{{ env('FIREBASE_APP_ID') }}"
        };
        window.firebaseVapidKey = "{{ env('FIREBASE_VAPID_KEY') }}";

        if (window.firebaseConfig.apiKey) {
            firebase.initializeApp(window.firebaseConfig);
            const messaging = firebase.messaging();

            if ('serviceWorker' in navigator) {
                const params = new URLSearchParams(window.firebaseConfig).toString();
                navigator.serviceWorker.register('/firebase-messaging-sw.js?' + params)
                    .then((registration) => {
                        console.log('FCM Service Worker registered:', registration);
                        messaging.useServiceWorker(registration);
                        requestFcmToken();
                    })
                    .catch((err) => {
                        console.error('FCM Service Worker registration failed:', err);
                    });
            }

            function requestFcmToken() {
                Notification.requestPermission().then((permission) => {
                    if (permission === 'granted') {
                        messaging.getToken({ vapidKey: window.firebaseVapidKey }).then((currentToken) => {
                            if (currentToken) {
                                console.log('FCM Token:', currentToken);
                                fetch('/api/fcm/register', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                                    },
                                    body: JSON.stringify({ token: currentToken })
                                })
                                .then(res => res.json())
                                .then(data => console.log('FCM Token registered to server:', data))
                                .catch(err => console.error('Error registering FCM token:', err));
                            } else {
                                console.warn('No registration token available.');
                            }
                        }).catch((err) => {
                            console.error('An error occurred while retrieving token:', err);
                        });
                    } else {
                        console.warn('Notification permission not granted.');
                    }
                });
            }

            // Foreground Message Handler
            messaging.onMessage((payload) => {
                console.log('Foreground message received:', payload);
                if (Notification.permission === 'granted') {
                    new Notification(payload.notification.title, {
                        body: payload.notification.body,
                        icon: payload.notification.icon || '/uhn_logo.png'
                    });
                }
            });
        }
    </script>
    @endif
</body>
<x-footer />
</html>
