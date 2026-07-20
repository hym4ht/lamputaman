<aside class="sidebar max-md:!bg-white max-md:!shadow-2xl" id="sidebar">
    <div class="sidebar-header flex items-center justify-between">
        <div class="brand">
            <div class="brand-icon">
                <i class="bi bi-flower1"></i>
            </div>
            <div class="brand-text">
                <h1>Lampu Taman</h1>
                <p>Garden Monitoring IoT</p>
            </div>
        </div>
        <button type="button" class="md:hidden text-gray-400 hover:text-gray-700 p-1.5 bg-gray-100 hover:bg-gray-200 rounded-lg border-0 cursor-pointer text-lg flex items-center justify-center transition-colors" data-sidebar-close id="sidebarCloseBtn" aria-label="Close Sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>


    <nav class="nav-menu">
        @foreach ($sidebarItems as $screen => $item)
            <a href="{{ route($item['route']) }}" 
               data-nav-link
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
