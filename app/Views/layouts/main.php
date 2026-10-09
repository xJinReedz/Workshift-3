<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $this->e($pageTitle ?? 'WorkShift — Client CRM for Freelancers') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('/favicon.svg') ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Master Stylesheet -->
    <link rel="stylesheet" href="<?= asset('/assets/css/app.css') ?>">
    <!-- SortableJS CDN for board drag-and-drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <!-- Instant Dark/Light Mode Bootstrapper & CSRF Token -->
    <script>
        window.__CSRF_TOKEN__ = "<?= csrf_token() ?>";
        window.__BASE_URL__ = "<?= base_url() ?>";
        window.__APP_ENV__ = "<?= e($appConfig['app']['env'] ?? 'local') ?>";
        (function() {
            const saved = localStorage.getItem('workshift-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', saved || (prefersDark ? 'dark' : 'dark'));
        })();
    </script>
</head>
<body>
    <div class="app-shell">
        <!-- 1. Fixed Top Bar (56px full width across entire screen) -->
        <header class="app-topbar">
            <div class="topbar-left">
                <!-- Mobile Menu Hamburger (< 768px) -->
                <button type="button" class="topbar-menu-toggle" onclick="toggleSidebar()" aria-label="Open navigation menu">
                    <?= clay_icon('menu', 18) ?>
                </button>

                <!-- Brand Logo & Name -->
                <a href="<?= base_url('/dashboard') ?>" class="topbar-brand" title="WorkShift CRM">
                    <?= logo_svg(26) ?>
                    <span class="topbar-brand-name">WorkShift</span>
                </a>

                <!-- Wide Search Field -->
                <div class="topbar-search-form">
                    <span class="topbar-search-icon"><?= clay_icon('search', 16) ?></span>
                    <input type="search" class="topbar-search-input" placeholder="Search clients, tasks, invoices..." aria-label="Search">
                </div>

                <!-- Solid Blue Create Button with Dropdown -->
                <div class="relative" id="create-dropdown-container">
                    <button type="button" class="btn btn-primary btn-sm" onclick="toggleCreateDropdown()" aria-expanded="false" aria-haspopup="true">
                        <span>Create</span>
                        <?= clay_icon('chevron-down', 12) ?>
                    </button>
                    <div id="create-dropdown-menu" class="dropdown-menu">
                        <a href="<?= base_url('/clients/create') ?>" class="dropdown-item">
                            <?= clay_icon('users', 14) ?>
                            <span>New Client</span>
                        </a>
                        <a href="<?= base_url('/invoices/create') ?>" class="dropdown-item">
                            <?= clay_icon('invoice', 14) ?>
                            <span>New Invoice</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?= base_url('/dashboard') ?>" class="dropdown-item" onclick="if(typeof openNewTaskPrompt==='function'){openNewTaskPrompt();return false;}">
                            <?= clay_icon('plus', 14) ?>
                            <span>New Task</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="topbar-right">
                <!-- Global Floating Timer Bar -->
                <div id="global-timer-widget" class="clay-timer-widget hidden">
                    <span class="timer-pulse-dot"></span>
                    <span id="global-timer-title" class="timer-title" title="Active Task">Working...</span>
                    <span id="global-timer-display" class="timer-digits">00:00:00</span>
                    <button type="button" id="global-timer-stop-btn" class="btn btn-danger btn-sm" style="height: 24px; padding: 0 8px; font-size: 11px;">
                        Stop
                    </button>
                </div>

                <!-- Plan Badge -->
                <a href="<?= base_url('/settings') ?>" class="lozenge <?= ($currentUser['plan'] ?? 'basic') === 'pro' ? 'lozenge-discovery' : 'lozenge-default' ?>" style="text-decoration: none;" title="Your plan tier">
                    <?= ($currentUser['plan'] ?? 'basic') === 'pro' ? 'Pro' : 'Free' ?>
                </a>

                <!-- Notifications Bell Icon -->
                <a href="<?= base_url('/notifications') ?>" class="btn btn-ghost btn-sm relative" title="Notifications" aria-label="View notifications" style="width: 36px; height: 36px; padding: 0;">
                    <?= clay_icon('bell', 16) ?>
                    <?php if (!empty($unreadNotificationCount) && $unreadNotificationCount > 0): ?>
                        <span style="position: absolute; top: 6px; right: 6px; width: 7px; height: 7px; border-radius: var(--radius-full); background: var(--color-danger);"></span>
                    <?php endif; ?>
                </a>

                <!-- Help Icon -->
                <a href="<?= base_url('/settings') ?>" class="btn btn-ghost btn-sm" title="Help & Support" aria-label="Help" style="width: 36px; height: 36px; padding: 0;">
                    <?= clay_icon('help-circle', 16) ?>
                </a>

                <!-- Theme Toggle (Light / Dark) -->
                <button type="button" class="btn btn-ghost btn-sm" onclick="toggleTheme()" title="Toggle Light / Dark Mode" aria-label="Toggle theme" style="width: 36px; height: 36px; padding: 0;">
                    <span class="theme-toggle-icon" data-theme="dark"><?= clay_icon('sun', 16) ?></span>
                    <span class="theme-toggle-icon" data-theme="light"><?= clay_icon('moon', 16) ?></span>
                </button>

                <!-- Avatar Menu -->
                <a href="<?= base_url('/settings') ?>" class="topbar-user-pill" title="Profile & Settings">
                    <div class="user-avatar-circle"><?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?></div>
                    <span class="user-pill-name"><?= $this->e($currentUser['name'] ?? 'Freelancer') ?></span>
                </a>

                <!-- Logout Button with Modal Confirmation -->
                <button type="button" class="btn btn-ghost btn-sm" onclick="return WS.confirmLogout(event)" title="Log Out of WorkShift" aria-label="Log Out" style="width: 36px; height: 36px; padding: 0;">
                    <?= clay_icon('logout', 16) ?>
                </button>
            </div>
        </header>

        <!-- 2. Horizontal Body Split below Topbar -->
        <div class="app-body">
            <!-- Dimmed Backdrop for Mobile Off-Canvas Drawer -->
            <div class="sidebar-backdrop" onclick="closeSidebar()" aria-hidden="true"></div>

            <!-- Left Sidebar: 264px Desktop, Off-Canvas on Mobile -->
            <aside class="app-sidebar" aria-label="Main Navigation">
                <div class="sidebar-panel">
                    <!-- Mobile Drawer Header -->
                    <div class="sidebar-header-mobile">
                        <div class="flex items-center gap-2">
                            <?= logo_svg(22) ?>
                            <span class="font-bold text-sm">WorkShift Menu</span>
                        </div>
                        <button type="button" class="sidebar-close-btn" onclick="closeSidebar()" aria-label="Close navigation menu">
                            <?= clay_icon('x', 18) ?>
                        </button>
                    </div>

                    <nav class="sidebar-nav">
                        <div class="nav-section-label">Workspace</div>
                        <a href="<?= base_url('/dashboard') ?>" class="nav-item <?= active_nav('/dashboard') ?>">
                            <span class="nav-item-icon"><?= clay_icon('dashboard', 16) ?></span>
                            <span>Dashboard</span>
                        </a>

                        <div class="flex items-center justify-between" style="padding-right: var(--space-2);">
                            <a href="<?= base_url('/clients') ?>" class="nav-item flex-1 <?= active_nav('/clients') && !active_nav('/clients/pipeline') ? 'active' : '' ?>">
                                <span class="nav-item-icon"><?= clay_icon('users', 16) ?></span>
                                <span>Clients</span>
                            </a>
                            <a href="<?= base_url('/clients/create') ?>" class="btn btn-ghost btn-sm" title="Add Client" style="width: 28px; height: 28px; padding: 0;">
                                <?= clay_icon('plus', 14) ?>
                            </a>
                        </div>

                        <a href="<?= base_url('/clients/pipeline') ?>" class="nav-item <?= active_nav('/clients/pipeline') ?>">
                            <span class="nav-item-icon"><?= clay_icon('pipeline', 16) ?></span>
                            <span>Pipeline</span>
                        </a>
                        <a href="<?= base_url('/invoices') ?>" class="nav-item <?= active_nav('/invoices') ?>">
                            <span class="nav-item-icon"><?= clay_icon('invoice', 16) ?></span>
                            <span>Invoices</span>
                        </a>
                        <a href="<?= base_url('/notifications') ?>" class="nav-item <?= active_nav('/notifications') ?>">
                            <span class="nav-item-icon"><?= clay_icon('bell', 16) ?></span>
                            <span>Notifications</span>
                            <?php if (!empty($unreadNotificationCount) && $unreadNotificationCount > 0): ?>
                                <span class="nav-item-badge"><?= $unreadNotificationCount ?></span>
                            <?php endif; ?>
                        </a>

                        <div class="nav-section-label">Configuration</div>
                        <a href="<?= base_url('/settings') ?>" class="nav-item <?= active_nav('/settings') ?>">
                            <span class="nav-item-icon"><?= clay_icon('settings', 16) ?></span>
                            <span>Settings</span>
                        </a>

                        <!-- Mobile-only logout link inside drawer -->
                        <div class="block md:hidden mt-4 pt-3" style="border-top: 1px solid var(--color-border);">
                            <a href="<?= base_url('/logout') ?>" onclick="return WS.confirmLogout(event)" class="nav-item text-danger">
                                <span class="nav-item-icon"><?= clay_icon('logout', 16) ?></span>
                                <span>Log Out</span>
                            </a>
                        </div>
                    </nav>

                    <div class="sidebar-footer">
                        <?php if (($currentUser['plan'] ?? 'basic') !== 'pro'): ?>
                            <div class="sidebar-pro-card">
                                <h4>Upgrade to Pro</h4>
                                <p>Unlimited active clients, Maya payments & automated reminder emails.</p>
                                <a href="<?= base_url('/settings?upgrade=1') ?>" class="btn btn-primary btn-sm w-full">
                                    Upgrade (₱499/mo)
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area (Full screen width) -->
            <div class="app-main">
                <main class="app-content <?= ($isFullBleed ?? false) ? 'full-bleed' : '' ?>">
                    <?= $content ?>
                </main>
            </div>
        </div>

        <!-- Floating Bottom Dock for Mobile (< 640px) -->
        <nav class="clay-bottom-dock" aria-label="Mobile Navigation">
            <a href="<?= base_url('/dashboard') ?>" class="dock-item <?= active_nav('/dashboard') ?>">
                <?= clay_icon('dashboard', 18) ?>
                <span>Dashboard</span>
            </a>
            <a href="<?= base_url('/clients') ?>" class="dock-item <?= active_nav('/clients') && !active_nav('/clients/pipeline') ? 'active' : '' ?>">
                <?= clay_icon('users', 18) ?>
                <span>Clients</span>
            </a>
            <a href="<?= base_url('/invoices') ?>" class="dock-item <?= active_nav('/invoices') ?>">
                <?= clay_icon('invoice', 18) ?>
                <span>Invoices</span>
            </a>
            <a href="<?= base_url('/settings') ?>" class="dock-item <?= active_nav('/settings') ?>">
                <?= clay_icon('settings', 18) ?>
                <span>Settings</span>
            </a>
        </nav>

        <!-- Slide-Over Panel (560px Drawer) -->
        <div id="slideover-backdrop" class="slideover-backdrop" onclick="closeTaskSlideover()" aria-hidden="true"></div>
        <div id="task-slideover" class="slideover-panel" role="dialog" aria-modal="true" aria-label="Task Details">
            <div class="slideover-header">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-sm text-primary">Task Inspector</span>
                </div>
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeTaskSlideover()" aria-label="Close panel" style="width: 32px; height: 32px; padding: 0;">
                    <?= clay_icon('x', 16) ?>
                </button>
            </div>
            <div id="slideover-task-body" class="slideover-body"></div>
        </div>

        <!-- Fallback Legacy Modal Container -->
        <div id="task-modal" class="clay-modal-backdrop hidden" role="dialog" aria-modal="true">
            <div class="clay-modal">
                <div class="modal-header">
                    <h3 style="font-size: var(--text-h3); font-weight: var(--weight-semibold); margin: 0;">Task Details</h3>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="closeTaskModal()" aria-label="Close modal">
                        <?= clay_icon('x', 16) ?>
                    </button>
                </div>
                <div id="modal-task-body" class="modal-body"></div>
            </div>
        </div>
    </div>

    <!-- Application JavaScript -->
    <script src="<?= asset('/assets/js/app.js') ?>"></script>
    <script src="<?= asset('/assets/js/timer.js') ?>"></script>
    <script>
    function toggleCreateDropdown() {
        const menu = document.getElementById('create-dropdown-menu');
        menu.classList.toggle('open');
    }
    document.addEventListener('click', function(e) {
        const container = document.getElementById('create-dropdown-container');
        if (container && !container.contains(e.target)) {
            document.getElementById('create-dropdown-menu')?.classList.remove('open');
        }
    });
    </script>

    <!-- Global In-App Modal Component & Server Flash Messages -->
    <?php include APP_PATH . '/Views/partials/modal.php'; ?>
</body>
</html>
