@php
    $dashboardTab = request('tab', 'home');
    if (! in_array($dashboardTab, ['home', 'movement', 'recent', 'inventory-accuracy', 'reserved-items'], true)) {
        $dashboardTab = 'home';
    }
    $isDashboard = request()->is('/');
    $isPhaseOutItems = request()->is('phase-out/items');
    $isPhaseOutUpdateLifecycle = request()->is('phase-out/update-lifecycle-status');
    $isLoginActivity = request()->is('admin/login-activity');

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

                <a href="{{ url('/phase-out/items') }}" class="{{ $navClasses($isPhaseOutItems) }}" title="Phase-Out Items">
                    <i class="fas fa-box-open inventory-sidebar__icon" aria-hidden="true"></i>
                    <span class="inventory-sidebar-label">Phase-Out Items</span>
                </a>
                <a href="{{ url('/phase-out/update-lifecycle-status') }}" class="{{ $navClasses($isPhaseOutUpdateLifecycle) }}" title="Update Lifecycle Status">
                    <i class="fas fa-arrows-rotate inventory-sidebar__icon" aria-hidden="true"></i>
                    <span class="inventory-sidebar-label">Update Lifecycle Status</span>
                </a>
            @endif
            @if(in_array(Auth::user()->user_group ?? '', config('login_activity.allowed_user_groups', []), true))
                <div class="inventory-sidebar__divider inventory-sidebar-label" role="separator"></div>
                <p class="inventory-sidebar__section-label inventory-sidebar-label">Security</p>
                <a href="{{ url('/admin/login-activity') }}" class="{{ $navClasses($isLoginActivity) }}" title="Login activity">
                    <i class="fas fa-right-to-bracket inventory-sidebar__icon" aria-hidden="true"></i>
                    <span class="inventory-sidebar-label">Login activity</span>
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
    /*
     * This partial is included before inventory-shell__main and the backdrop in the DOM.
     * Querying for the hamburger here at parse time returns null. Defer until DOM is ready.
     */
    function initInventoryShellNav() {
        var shell = document.querySelector('[data-inventory-shell]');
        if (!shell) return;
        var aside = document.getElementById('inventory-sidebar');
        var btn = shell.querySelector('[data-sidebar-toggle]');
        var mobileToggle = shell.querySelector('[data-mobile-nav-toggle]');
        var backdrop = shell.querySelector('[data-mobile-nav-backdrop]');
        var KEY = 'inventory-sidebar-collapsed';
        var mq = typeof window.matchMedia === 'function' ? window.matchMedia('(min-width: 1367px)') : null;

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

        function setMobileNavOpen(open) {
            shell.classList.toggle('inventory-shell--mobile-nav-open', open);
            document.body.classList.toggle('inventory-shell-mobile-nav-lock', open);
            if (mobileToggle) {
                mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                mobileToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
            }
            if (backdrop) {
                backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
            }
        }

        function closeMobileNav() {
            setMobileNavOpen(false);
        }

        /* Reset drawer on load (avoids stuck-open class after navigation or bfcache). */
        setMobileNavOpen(false);

        function isDesktop() {
            return mq ? mq.matches : window.innerWidth >= 1367;
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

        if (mobileToggle) {
            mobileToggle.addEventListener('click', function () {
                setMobileNavOpen(!shell.classList.contains('inventory-shell--mobile-nav-open'));
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                closeMobileNav();
            });
        }

        var searchInput = document.getElementById('searchid');
        function closeMobileNavIfOpenOnSearchInteract() {
            if (shell.classList.contains('inventory-shell--mobile-nav-open')) {
                closeMobileNav();
            }
        }
        if (searchInput) {
            searchInput.addEventListener('focus', closeMobileNavIfOpenOnSearchInteract);
            searchInput.addEventListener('click', closeMobileNavIfOpenOnSearchInteract);
        }

        if (aside) {
            aside.querySelectorAll('a.inventory-sidebar__link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (!isDesktop()) {
                        closeMobileNav();
                    }
                });
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && shell.classList.contains('inventory-shell--mobile-nav-open')) {
                closeMobileNav();
            }
        });

        function onViewportChange() {
            if (isDesktop()) {
                closeMobileNav();
            }
        }

        if (mq && typeof mq.addEventListener === 'function') {
            mq.addEventListener('change', onViewportChange);
        } else if (mq && typeof mq.addListener === 'function') {
            mq.addListener(onViewportChange);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInventoryShellNav);
    } else {
        initInventoryShellNav();
    }
})();
</script>
