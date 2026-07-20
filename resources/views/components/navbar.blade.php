@props(['isDashboard' => false])

@if($isDashboard)
    <!-- Dashboard Top Navbar (visible on mobile/tablet only) -->
    <header class="d-flex d-md-none md:hidden border-b border-[#e8e8e8] bg-white/95 backdrop-blur-md sticky top-0 z-50 px-4 justify-between items-center shadow-sm" style="padding-top: 12px !important; padding-bottom: 12px !important; display: flex !important; align-items: center !important; justify-content: space-between !important; min-height: 56px !important; background-color: #ffffff !important; width: 100% !important; box-sizing: border-box !important;">
        <div class="d-flex flex items-center gap-3" style="display: flex !important; align-items: center !important; gap: 12px !important;">
            <!-- Mobile Toggle Sidebar -->
            <button class="text-gray-700 p-0 border-0 bg-transparent flex items-center justify-center cursor-pointer" id="menuToggle" type="button" aria-label="Toggle Sidebar" style="font-size: 1.5rem; background: transparent; border: none; padding: 0; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-list" style="font-size: 1.6rem; color: #374151;"></i>
            </button>
            <div class="d-flex flex items-center gap-2" style="display: flex !important; align-items: center !important; gap: 8px !important;">
                <div class="shrink-0 w-8 h-8 rounded bg-gradient-to-br from-emerald-600 to-teal-500 text-white flex items-center justify-center text-sm shadow-sm" style="width: 32px !important; height: 32px !important; border-radius: 6px !important; background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important; color: white !important; display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important;">
                    <i class="bi bi-flower1" style="font-size: 1rem;"></i>
                </div>
                <div>
                    <h1 class="text-xs font-bold text-gray-800 leading-tight m-0" style="margin: 0 !important; font-size: 0.85rem !important; font-weight: 700 !important; color: #1f2937 !important; line-height: 1.2 !important;">Lampu Taman</h1>
                    <p class="text-[9px] text-gray-500 leading-tight m-0" style="margin: 0 !important; font-size: 0.65rem !important; color: #6b7280 !important; line-height: 1.2 !important;">Monitoring IoT</p>
                </div>
            </div>
        </div>

        <div class="d-flex flex items-center gap-2" style="display: flex !important; align-items: center !important; gap: 8px !important;">
            <a href="{{ route('public.monitoring') }}" class="border border-gray-200 text-gray-600 hover:bg-gray-50 active:bg-gray-100 text-xs font-semibold no-underline transition-colors shadow-sm" title="Lihat Monitoring Publik" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 36px !important; height: 36px !important; border-radius: 6px !important; text-decoration: none !important; padding: 0 !important; box-sizing: border-box !important; border: 1px solid #e5e7eb !important; color: #4b5563 !important; background-color: #ffffff !important;">
                <i class="bi bi-eye" style="font-size: 1rem;"></i>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="m-0" style="margin: 0 !important;">
                @csrf
                <button class="border border-rose-200 text-rose-700 bg-rose-50/50 hover:bg-rose-100/60 active:bg-rose-100 text-xs font-semibold cursor-pointer transition-colors shadow-sm" type="submit" title="Keluar" style="display: inline-flex !important; align-items: center !important; justify-content: center !important; width: 36px !important; height: 36px !important; border-radius: 6px !important; padding: 0 !important; box-sizing: border-box !important; border: 1px solid #fecdd3 !important; color: #be123c !important; background-color: #fff1f2 !important;">
                    <i class="bi bi-box-arrow-right" style="font-size: 1rem;"></i>
                </button>
            </form>
        </div>
    </header>
@else
    <!-- Public Navbar (Responsive) -->
    <header class="border-b border-[#e8e8e8] bg-white/85 backdrop-blur-md sticky top-0 z-50 mb-6 sm:mb-8 shadow-sm" style="padding-top: 14px !important; padding-bottom: 14px !important; background-color: rgba(255, 255, 255, 0.92) !important;">
        <div class="d-flex flex justify-between items-center gap-3 w-full" style="max-width: 1200px; margin: 0 auto; padding: 0 1.25rem; display: flex !important; align-items: center !important; justify-content: space-between !important;">
            <div class="d-flex flex items-center gap-2 sm:gap-3 min-w-0" style="display: flex !important; align-items: center !important; gap: 10px !important;">
                <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded bg-gradient-to-br from-emerald-600 to-teal-500 text-white flex items-center justify-center text-base sm:text-lg shadow-sm" style="width: 36px !important; height: 36px !important; border-radius: 6px !important; background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important; color: white !important; display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important;">
                    <i class="bi bi-flower1" style="font-size: 1.1rem;"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="text-sm font-bold text-gray-800 leading-tight truncate m-0" style="margin: 0 !important; font-size: 0.95rem !important; font-weight: 700 !important; color: #1f2937 !important;">Lampu Taman</h1>
                    <p class="text-[10px] sm:text-[11px] text-gray-500 leading-tight truncate m-0" style="margin: 0 !important; font-size: 0.72rem !important; color: #6b7280 !important;">Garden Monitoring IoT</p>
                </div>
            </div>

            <div class="shrink-0 d-flex flex items-center gap-2" style="display: flex !important; align-items: center !important; gap: 8px !important;">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="border border-emerald-200 text-emerald-700 bg-emerald-50/50 hover:bg-emerald-100/60 active:bg-emerald-100 text-[11px] sm:text-xs font-semibold no-underline transition-colors shadow-sm"
                       style="display: inline-flex !important; align-items: center !important; gap: 8px !important; padding: 6px 14px !important; border-radius: 6px !important; text-decoration: none !important; box-sizing: border-box !important; border: 1px solid #a7f3d0 !important; color: #047857 !important; background-color: #ecfdf5 !important;">
                        <i class="bi bi-grid-1x2"></i>
                        <span>Ke Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="border border-gray-200 text-gray-600 hover:bg-gray-50 active:bg-gray-100 text-[11px] sm:text-xs font-semibold no-underline transition-colors shadow-sm"
                       style="display: inline-flex !important; align-items: center !important; gap: 8px !important; padding: 6px 14px !important; border-radius: 6px !important; text-decoration: none !important; box-sizing: border-box !important; border: 1px solid #e5e7eb !important; color: #4b5563 !important; background-color: #ffffff !important;">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Login Admin</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>
@endif
