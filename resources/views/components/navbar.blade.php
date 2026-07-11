@props(['isDashboard' => false])

@if($isDashboard)
    <!-- Dashboard Top Navbar (visible on mobile/tablet only) -->
    <header class="md:hidden border-b border-[#e8e8e8] bg-white/95 backdrop-blur-md sticky top-0 z-50 px-4 py-3 flex justify-between items-center shadow-sm">
        <div class="flex items-center gap-3">
            <!-- Mobile Toggle Sidebar -->
            <button class="text-gray-700 p-0 border-0 bg-transparent flex items-center justify-center cursor-pointer" id="menuToggle" aria-label="Toggle Sidebar" style="font-size: 1.5rem;">
                <i class="bi bi-list"></i>
            </button>
            <div class="brand flex items-center gap-2">
                <div class="brand-icon shrink-0 w-8 h-8 rounded bg-gradient-to-br from-emerald-600 to-teal-500 text-white flex items-center justify-center text-sm shadow-sm">
                    <i class="bi bi-flower1"></i>
                </div>
                <div class="brand-text">
                    <h1 class="text-xs font-bold text-gray-800 leading-tight">Lampu Taman</h1>
                    <p class="text-[9px] text-gray-500 leading-tight">Monitoring IoT</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('public.monitoring') }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 border border-gray-200 text-gray-600 hover:bg-gray-50 active:bg-gray-100 rounded-lg text-xs font-semibold no-underline transition-colors shadow-sm" title="Lihat Monitoring Publik">
                <i class="bi bi-eye"></i>
                <span class="hidden sm:inline">Lihat Publik</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="inline-flex items-center gap-1 px-2.5 py-1.5 border border-rose-200 text-rose-700 bg-rose-50/50 hover:bg-rose-100/60 active:bg-rose-100 rounded-lg text-xs font-semibold cursor-pointer transition-colors shadow-sm" type="submit" title="Keluar">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="hidden sm:inline">Keluar</span>
                </button>
            </form>
        </div>
    </header>
@else
    <!-- Public Navbar (Responsive) -->
    <header class="border-b border-[#e8e8e8] bg-white/85 backdrop-blur-md sticky top-0 z-50 py-2.5 sm:py-3 mb-6 sm:mb-8 shadow-sm">
        <div class="flex justify-between items-center gap-3 w-full" style="max-width: 1200px; margin: 0 auto; padding: 0 1.25rem;">
            <div class="brand flex items-center gap-2 sm:gap-3 min-w-0">
                <div class="brand-icon shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded bg-gradient-to-br from-emerald-600 to-teal-500 text-white flex items-center justify-center text-base sm:text-lg shadow-sm">
                    <i class="bi bi-flower1"></i>
                </div>
                <div class="brand-text min-w-0">
                    <h1 class="text-sm font-bold text-gray-800 leading-tight truncate">Lampu Taman</h1>
                    <p class="text-[10px] sm:text-[11px] text-gray-500 leading-tight truncate">Garden Monitoring IoT</p>
                </div>
            </div>

            <div class="shrink-0 flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-4 py-1.5 sm:py-2 border border-emerald-200 text-emerald-700 bg-emerald-50/50 hover:bg-emerald-100/60 active:bg-emerald-100 rounded-lg text-[11px] sm:text-xs font-semibold no-underline transition-colors shadow-sm">
                        <i class="bi bi-grid-1x2"></i>
                        <span class="hidden sm:inline">Ke Dashboard</span>
                        <span class="sm:hidden">Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-4 py-1.5 sm:py-2 border border-gray-200 text-gray-600 hover:bg-gray-50 active:bg-gray-100 rounded-lg text-[11px] sm:text-xs font-semibold no-underline transition-colors shadow-sm">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span class="hidden sm:inline">Login Admin</span>
                        <span class="sm:hidden">Login</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>
@endif
