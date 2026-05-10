<header class="page-header">
    <div class="page-heading">
        <div class="page-label">Dashboard</div>
        <h1 class="page-title">{{ $screenMeta[$activeScreen]['title'] }}</h1>
        <p class="page-subtitle">{{ $screenMeta[$activeScreen]['subtitle'] }}</p>
    </div>

    <button class="desktop-sidebar-toggle"
            id="desktopSidebarToggle"
            type="button"
            aria-pressed="false">
        <i class="bi bi-layout-sidebar-inset"></i>
        <span id="desktopSidebarToggleLabel">Full screen</span>
    </button>
</header>
