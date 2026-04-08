@php
    $dashboardTab = request('tab', 'home');
    $isDashboard = request()->is('/');
    $isPhaseOutDashboard = request()->is('phase-out/dashboard');
    $isPhaseOutItems = request()->is('phase-out/items');
    $isPhaseOutUpdateLifecycle = request()->is('phase-out/update-lifecycle-status');

    $navClasses = function (bool $active): string {
        return 'inventory-sidebar__link'.($active ? ' inventory-sidebar__link--active' : '');
    };
@endphp

<aside
    id="inventory-sidebar"
    class="inventory-sidebar"
    aria-label="Main navigation"
>
    <div class="inventory-sidebar__header">
        <button
            type="button"
            data-sidebar-toggle
            class="inventory-sidebar__collapse-btn"
            title="Collapse sidebar"
            aria-expanded="true"
        >
            <i class="fas fa-angles-left inventory-shell-collapse-icon" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="inventory-sidebar-nav" aria-label="Primary">
        <a href="{{ url('/?tab=home') }}" class="{{ $navClasses($isDashboard && $dashboardTab === 'home') }}" title="Home">
            <i class="fas fa-home inventory-sidebar__icon" aria-hidden="true"></i>
            <span class="inventory-sidebar-label">Home</span>
        </a>
        <a href="{{ url('/?tab=stock-alert') }}" class="{{ $navClasses($isDashboard && $dashboardTab === 'stock-alert') }}" title="Stock Levels Alerts">
            <i class="fas fa-exclamation-triangle inventory-sidebar__icon" aria-hidden="true"></i>
            <span class="inventory-sidebar-label">Stock Levels Alerts</span>
        </a>
        <a href="{{ url('/?tab=movement') }}" class="{{ $navClasses($isDashboard && $dashboardTab === 'movement') }}" title="Stock Movement">
            <i class="fas fa-list-alt inventory-sidebar__icon" aria-hidden="true"></i>
            <span class="inventory-sidebar-label">Stock Movement</span>
        </a>
        <a href="{{ url('/?tab=recent') }}" class="{{ $navClasses($isDashboard && $dashboardTab === 'recent') }}" title="Recently Received Items">
            <i class="fas fa-inbox inventory-sidebar__icon" aria-hidden="true"></i>
            <span class="inventory-sidebar-label">Recently Received Items</span>
        </a>
        <a href="{{ url('/?tab=inventory-accuracy') }}" class="{{ $navClasses($isDashboard && $dashboardTab === 'inventory-accuracy') }}" title="Inventory Accuracy">
            <i class="fas fa-chart-line inventory-sidebar__icon" aria-hidden="true"></i>
            <span class="inventory-sidebar-label">Inventory Accuracy</span>
        </a>
        <a href="{{ url('/?tab=reserved-items') }}" class="{{ $navClasses($isDashboard && $dashboardTab === 'reserved-items') }}" title="Reserved Items">
            <i class="fas fa-clipboard-list inventory-sidebar__icon" aria-hidden="true"></i>
            <span class="inventory-sidebar-label">Reserved Items</span>
        </a>

        @auth
            @if(in_array(Auth::user()->user_group, \App\Http\Middleware\EnsureInventoryLifecycleSettingsAccess::ALLOWED_USER_GROUPS))
                <div class="inventory-sidebar__divider inventory-sidebar-label" role="separator"></div>
                <p class="inventory-sidebar__section-label inventory-sidebar-label">Item Lifecycle Settings</p>

                <a href="{{ url('/phase-out/dashboard') }}" class="{{ $navClasses($isPhaseOutDashboard) }}" title="Phase-Out Dashboard">
                    <i class="fas fa-chart-pie inventory-sidebar__icon" aria-hidden="true"></i>
                    <span class="inventory-sidebar-label">Phase-Out Dashboard</span>
                </a>
                <a href="{{ url('/phase-out/items') }}" class="{{ $navClasses($isPhaseOutItems) }}" title="Phase-Out Items">
                    <i class="fas fa-box-open inventory-sidebar__icon" aria-hidden="true"></i>
                    <span class="inventory-sidebar-label">Phase-Out Items</span>
                </a>
                <a href="{{ url('/phase-out/update-lifecycle-status') }}" class="{{ $navClasses($isPhaseOutUpdateLifecycle) }}" title="Update Lifecycle Status">
                    <i class="fas fa-arrows-rotate inventory-sidebar__icon" aria-hidden="true"></i>
                    <span class="inventory-sidebar-label">Update Lifecycle Status</span>
                </a>
            @endif
        @endauth
    </nav>

    <div class="inventory-sidebar__footer">
        <a href="{{ url('/user_manual') }}" class="{{ $navClasses(request()->is('user_manual') || request()->is('user_manual/*')) }}" title="Help &amp; Support">
            <i class="fas fa-circle-question inventory-sidebar__icon" aria-hidden="true"></i>
            <span class="inventory-sidebar-label">Help &amp; Support</span>
        </a>
    </div>
</aside>

<script>
(function () {
    var shell = document.querySelector('[data-inventory-shell]');
    if (!shell) return;
    var aside = document.getElementById('inventory-sidebar');
    var btn = shell.querySelector('[data-sidebar-toggle]');
    var KEY = 'inventory-sidebar-collapsed';

    function applyCollapsed(collapsed) {
        shell.classList.toggle('inventory-shell--collapsed', collapsed);
        if (aside) {
            aside.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }
        if (btn) {
            btn.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        }
        try {
            localStorage.setItem(KEY, collapsed ? '1' : '0');
        } catch (e) {}
    }

    var initial = false;
    try {
        initial = localStorage.getItem(KEY) === '1';
    } catch (e) {}
    applyCollapsed(initial);

    if (btn) {
        btn.addEventListener('click', function () {
            applyCollapsed(!shell.classList.contains('inventory-shell--collapsed'));
        });
    }
})();
</script>
