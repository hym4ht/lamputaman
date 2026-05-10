<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Lamputaman') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-[#fafafa] text-gray-900 antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Navbar -->
    <nav class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-100 sticky top-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="/" class="text-2xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-violet-600">
                        Lamputaman.
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex space-x-10 items-center">
                    <a href="#" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-200">Home</a>
                    <a href="#" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-200">Features</a>
                    <a href="#" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-200">Pricing</a>
                    <a href="#" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors duration-200">Contact</a>
                </div>

                <!-- Auth Buttons -->
                <div class="hidden md:flex items-center space-x-5">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-600 hover:text-indigo-600 font-medium transition-colors">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-6 py-2.5 rounded-full font-medium hover:bg-indigo-700 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200">Register</a>
                            @endif
                        @endauth
                    @endif
                </div>

                <!-- Mobile menu button -->
                <div class="flex items-center md:hidden">
                    <button type="button" class="mobile-menu-button text-gray-500 hover:text-indigo-600 focus:outline-none p-2 transition-colors" aria-controls="mobile-menu" aria-expanded="false">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="md:hidden hidden mobile-menu bg-white border-t border-gray-100 shadow-xl absolute w-full left-0">
            <div class="px-4 pt-4 pb-4 space-y-2 sm:px-6">
                <a href="#" class="block px-3 py-2.5 rounded-lg text-base font-medium text-gray-900 bg-gray-50">Home</a>
                <a href="#" class="block px-3 py-2.5 rounded-lg text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-gray-50 transition-colors">Features</a>
                <a href="#" class="block px-3 py-2.5 rounded-lg text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-gray-50 transition-colors">Pricing</a>
                <a href="#" class="block px-3 py-2.5 rounded-lg text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-gray-50 transition-colors">Contact</a>
            </div>
            <div class="pt-4 pb-6 border-t border-gray-100">
                <div class="px-6 space-y-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="block w-full text-center bg-gray-100 text-gray-900 px-4 py-3 rounded-xl font-medium hover:bg-gray-200 transition-colors">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="block w-full text-center text-indigo-700 bg-indigo-50 px-4 py-3 rounded-xl font-medium hover:bg-indigo-100 transition-colors">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="block w-full text-center bg-indigo-600 text-white px-4 py-3 rounded-xl font-medium hover:bg-indigo-700 shadow-md transition-colors">Register</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-16 sm:mt-28 mb-24">
        <div class="text-center">
            <div class="inline-flex items-center px-4 py-2 rounded-full bg-indigo-50 text-indigo-700 font-semibold text-sm mb-8">
                <span class="flex h-2 w-2 rounded-full bg-indigo-600 mr-2"></span>
                Welcome to the new experience
            </div>
            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight text-gray-900 mb-6 leading-tight">
                Simplicity Meets <br class="hidden sm:block" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500">Modern Design</span>
            </h1>
            <p class="mt-6 max-w-2xl mx-auto text-xl md:text-2xl text-gray-500 mb-12 font-light">
                A clean, minimalist approach to managing your digital life. Start focusing on what truly matters today.
            </p>
            <div class="flex justify-center gap-4 flex-col sm:flex-row px-4">
                <a href="{{ route('register') ?? '#' }}" class="inline-flex justify-center items-center px-8 py-4 border border-transparent text-lg font-medium rounded-full shadow-lg shadow-indigo-200 text-white bg-indigo-600 hover:bg-indigo-700 hover:-translate-y-1 transition-all duration-300">
                    Get Started Free
                </a>
                <a href="#" class="inline-flex justify-center items-center px-8 py-4 border-2 border-gray-200 shadow-sm text-lg font-medium rounded-full text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 transition-all duration-300">
                    Learn More
                </a>
            </div>
        </div>

        <!-- Features/Mockup Image Placeholder -->
        <div class="mt-24 flex justify-center relative">
            <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-[2rem] blur opacity-20"></div>
            <div class="relative w-full max-w-5xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-gray-100 aspect-[16/9] flex flex-col">
                <div class="h-12 border-b border-gray-100 flex items-center px-4 gap-2 bg-gray-50/50">
                    <div class="w-3 h-3 rounded-full bg-red-400"></div>
                    <div class="w-3 h-3 rounded-full bg-amber-400"></div>
                    <div class="w-3 h-3 rounded-full bg-green-400"></div>
                </div>
                <div class="flex-1 flex items-center justify-center bg-gray-50/30">
                    <div class="text-center">
                        <svg class="w-16 h-16 mx-auto text-indigo-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-gray-400 text-lg font-medium">Dashboard Preview</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Script for mobile menu -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.querySelector('.mobile-menu-button');
            const menu = document.querySelector('.mobile-menu');

            btn.addEventListener('click', () => {
                menu.classList.toggle('hidden');
            });
        });
    </script>
</body>
</html>
