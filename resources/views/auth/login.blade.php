<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Smart Garden IoT Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-panel">
            <div class="auth-copy">
                <p class="eyebrow">Smart Garden IoT</p>
                <h1>Dashboard Lampu Taman</h1>
                <p class="auth-subtitle">Pantau suhu, kelembaban, dan kendalikan relay taman dari satu panel admin.</p>
            </div>

            <form class="login-card" method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="mb-4">
                    <p class="eyebrow mb-2">Admin</p>
                    <h2>Masuk</h2>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger py-2" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input
                        class="form-control @error('email') is-invalid @enderror"
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input
                        class="form-control @error('password') is-invalid @enderror"
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>

                <button class="btn btn-primary w-100" type="submit">Masuk Dashboard</button>
            </form>
        </section>
    </main>
</body>
</html>
