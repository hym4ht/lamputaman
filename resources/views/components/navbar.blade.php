@props(['isDashboard' => false])

@if($isDashboard)
    <!-- Dashboard Top Navbar (visible on mobile/tablet only) -->
    <header class="md:hidden border-b border-[#e8e8e8] bg-white/95 backdrop-blur-md sticky top-0 z-50 px-4 flex justify-between items-center shadow-sm" style="padding-top: 12px !important; padding-bottom: 12px !important;">
        <div class="flex items-center gap-3">
            <!-- Mobile Toggle Sidebar -->
            <button class="text-gray-700 p-0 border-0 bg-transparent flex items-center justify-center cursor-pointer" id="menuToggle" aria-label="Toggle Sidebar" style="font-size: 1.5rem;">
                <i class="bi bi-list"></i>
            </button>
            <div class="flex items-center gap-2">
                <div class="shrink-0 w-8 h-8 rounded bg-gradient-to-br from-emerald-600 to-teal-500 text-white flex items-center justify-center text-sm shadow-sm" style="border-radius: 6px !important;">
                    <i class="bi bi-flower1"></i>
                </div>
                <div>
                    <h1 class="text-xs font-bold text-gray-800 leading-tight m-0">Lampu Taman</h1>
                    <p class="text-[9px] text-gray-500 leading-tight m-0">Monitoring IoT</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('public.monitoring') }}" class="border border-gray-200 text-gray-600 hover:bg-gray-50 active:bg-gray-100 text-xs font-semibold no-underline transition-colors shadow-sm" title="Lihat Monitoring Publik" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 36px !important; height: 36px !important; border-radius: 6px !important; text-decoration: none !important; padding: 0 !important; box-sizing: border-box !important;">
                <i class="bi bi-eye" style="font-size: 1rem;"></i>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="border border-rose-200 text-rose-700 bg-rose-50/50 hover:bg-rose-100/60 active:bg-rose-100 text-xs font-semibold cursor-pointer transition-colors shadow-sm" type="submit" title="Keluar" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 36px !important; height: 36px !important; border-radius: 6px !important; padding: 0 !important; box-sizing: border-box !important;">
                    <i class="bi bi-box-arrow-right" style="font-size: 1rem;"></i>
                </button>
            </form>
        </div>
    </header>
@else
    <!-- Public Navbar (Responsive) -->
    <header class="border-b border-[#e8e8e8] bg-white/85 backdrop-blur-md sticky top-0 z-50 mb-6 sm:mb-8 shadow-sm" style="padding-top: 14px !important; padding-bottom: 14px !important;">
        <div class="flex justify-between items-center gap-3 w-full" style="max-width: 1200px; margin: 0 auto; padding: 0 1.25rem;">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded bg-gradient-to-br from-emerald-600 to-teal-500 text-white flex items-center justify-center text-base sm:text-lg shadow-sm" style="border-radius: 6px !important;">
                    <i class="bi bi-flower1"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="text-sm font-bold text-gray-800 leading-tight truncate m-0">Lampu Taman</h1>
                    <p class="text-[10px] sm:text-[11px] text-gray-500 leading-tight truncate m-0">Garden Monitoring IoT</p>
                </div>
            </div>

            <div class="shrink-0 flex items-center gap-2">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="border border-emerald-200 text-emerald-700 bg-emerald-50/50 hover:bg-emerald-100/60 active:bg-emerald-100 text-[11px] sm:text-xs font-semibold no-underline transition-colors shadow-sm"
                       style="display: inline-flex !important; align-items: center !important; gap: 8px !important; padding: 6px 14px !important; border-radius: 6px !important; text-decoration: none !important; box-sizing: border-box !important;">
                        <i class="bi bi-grid-1x2"></i>
                        <span class="hidden sm:inline">Ke Dashboard</span>
                        <span class="sm:hidden">Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="border border-gray-200 text-gray-600 hover:bg-gray-50 active:bg-gray-100 text-[11px] sm:text-xs font-semibold no-underline transition-colors shadow-sm"
                       style="display: inline-flex !important; align-items: center !important; gap: 8px !important; padding: 6px 14px !important; border-radius: 6px !important; text-decoration: none !important; box-sizing: border-box !important;">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span class="hidden sm:inline">Login Admin</span>
                        <span class="sm:hidden">Login</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>
@endif
