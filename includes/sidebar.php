<?php
// Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'admin';

// Convert 'manager' role to 'host' for consistency
if ($user_role === 'manager') {
    $user_role = 'host';
}

// Define which page should be active for each menu item
$menu_items = [
    'admin_dashboard.php' => 'Dashboard',
    'host_dashboard.php' => 'Dashboard',
    'manage_branch.php' => 'Branch Management',
    'user_management.php' => 'User Management',
    'unit_management.php' => 'Unit Management',
    'reservations.php' => 'Reservation Management',
    'reservation_management.php' => 'Reservation Management',
    'payment_management.php' => 'Payment Management',
    'amenity_management.php' => 'Amenity Management',
    'amenity_requests.php' => 'Amenity Requests',
    'reports.php' => 'Reports',
    'settings.php' => 'Settings'
];

// Function to check if menu item is active
function isActive($page, $current_page, $menu_items) {
    return $page === $current_page;
}

// Alternative method: check by page name pattern
function isActivePattern($menu_key, $current_page) {
    $admin_patterns = [
        'Dashboard' => ['admin_dashboard.php'],
        'Branch Management' => ['manage_branch.php', 'add_branch.php', 'edit_branch.php'],
        'User Management' => ['user_management.php', 'add_user.php', 'edit_user.php'],
        'Unit Management' => ['unit_management.php', 'add_unit.php', 'edit_unit.php'],
        'Reservation Management' => ['reservations.php', 'reservation_details.php'],
        'Payment Management' => ['payment_management.php', 'payment_details.php'],
        'Amenity Management' => ['amenity_management.php', 'amenity_bookings.php', 'add_amenity.php', 'edit_amenity.php'],
        'Reports' => ['reports.php', 'report_generator.php'],
        'Settings' => ['settings.php', 'profile.php']
    ];

    $host_patterns = [
        'Dashboard' => ['host_dashboard.php'],
        'Unit Management' => ['unit_management.php', 'add_unit.php', 'edit_unit.php'],
        'Reservations' => ['reservations.php', 'reservation_details.php', 'reservation_calendar.php'],
        'Payments' => ['payment_management.php', 'payment_details.php'],
        'Amenities' => ['amenities.php', 'amenity_requests.php', 'amenity_details.php'],
        'Feedback' => ['reviews.php', 'feedback.php'],
        'Notifications' => ['notifications.php'],
        'Profile' => ['profile.php', 'settings.php']
    ];
    
    $patterns = ($GLOBALS['user_role'] === 'host' || $GLOBALS['user_role'] === 'manager') ? $host_patterns : $admin_patterns;
    
    return isset($patterns[$menu_key]) && in_array($current_page, $patterns[$menu_key]);
}
?>

<!-- ====== START: Inline CSS (full) ====== -->
<style>
/* =========================================================================
   BookIT Sidebar — Complete CSS
   - Desktop: collapsible sidebar (280px <-> 64px)
   - Mobile: slide-over sidebar (hidden by default)
   - Always-visible toggle button (top-left)
   - Smooth transitions and accessible focus states
   ========================================================================= */

/* ---------- Reset / Base ---------- */
:root{
    --sidebar-width: 280px;
    --sidebar-collapsed: 64px;
    --sidebar-bg: #2b3a42;
    --sidebar-accent: #3498db;
    --sidebar-contrast: #24303c;
    --text-light: #ecf0f1;
    --muted: #95a5a6;
    --danger: #e74c3c;
    --transition-fast: 0.2s;
    --transition-medium: 0.28s;
    --nav-item-height: 44px;
    --z-sidebar: 1050;
    --z-toggle: 3000;
}
* { box-sizing: border-box; }
html,body { margin:0; padding:0; height:100%; font-family: Inter, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale; }
a { color: inherit; }

/* ---------- Layout helpers ---------- */
.app-shell { min-height:100vh; display:flex; align-items:stretch; }

/* ---------- Sidebar (base) ---------- */
.sidebar {
    background: var(--sidebar-bg);
    color: var(--text-light);
    display: flex;
    flex-direction: column;
    width: var(--sidebar-width);
    height: 100vh;
    box-shadow: 2px 0 8px rgba(0,0,0,0.15);
    overflow: hidden;
    position: fixed;
    top: 0;
    left: 0;
    z-index: var(--z-sidebar);
    transition: width var(--transition-medium) ease, transform var(--transition-medium) ease;
    will-change: width, transform;
    /* Ensure sidebar doesn't block the toggle button */
    pointer-events: auto;
}

/* Desktop: sidebar is always visible */
@media (min-width: 769px) {
    .sidebar {
        position: fixed;
        transform: translateX(0) !important;
    }
}

/* Brand */
.sidebar .brand {
    display:flex;
    align-items:center;
    gap:12px;
    padding:18px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    background: var(--sidebar-contrast);
    position: relative;
    z-index: 1;
    /* Make top-left area non-clickable to allow button clicks */
    pointer-events: auto;
}

/* Create a non-clickable area where the button is */
.sidebar .brand::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 70px;
    height: 70px;
    z-index: 9998;
    pointer-events: none;
    background: transparent;
}
.sidebar .brand i { font-size:22px; color:var(--sidebar-accent); }
.sidebar .brand .brand-title { font-weight:700; font-size:16px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* Menu */
.sidebar-menu { flex:1; overflow-y:auto; -webkit-overflow-scrolling:touch; padding:12px 0; min-height:0; }
.menu-section-label { padding:8px 18px; font-size:11px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:1px; }
.sidebar-menu ul { list-style:none; margin:0; padding:0; }
.sidebar-menu li { margin:4px 12px; border-radius:8px; }
.sidebar-menu li a {
    display:flex; align-items:center; gap:12px; padding:10px 14px; color:var(--text-light); text-decoration:none; border-radius:6px;
    transition: all var(--transition-fast) ease;
    height: var(--nav-item-height);
}
.sidebar-menu li a i { min-width:20px; text-align:center; font-size:15px; }
.sidebar-menu li a span { flex:1; font-size:14px; display:inline-block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* Hover & active */
.sidebar-menu li a:hover { background: rgba(52,152,219,0.06); color:var(--sidebar-accent); }
.sidebar-menu li a.active { background: var(--sidebar-accent); color: #fff; font-weight:600; }

/* Profile area at bottom */
.sidebar-profile-section {
    padding:12px 12px; border-top:1px solid rgba(255,255,255,0.06); background: var(--sidebar-contrast);
}
.sidebar-profile { display:flex; align-items:center; gap:10px; justify-content:space-between; padding:8px; border-radius:8px; background: rgba(52,152,219,0.05); }
.profile-info { display:flex; gap:10px; align-items:center; min-width:0; }
.profile-avatar i { font-size:30px; color:var(--sidebar-accent); }
.profile-details { display:flex; flex-direction:column; min-width:0; }
.profile-name { font-size:13px; font-weight:600; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
.profile-role { font-size:11px; color:var(--muted); text-transform:capitalize; }

/* Logout button */
.logout-btn { width:36px; height:36px; display:flex; align-items:center; justify-content:center; background:var(--danger); color:#fff; border-radius:6px; text-decoration:none; }

/* ---------- Toggle button (always visible, always clickable) ---------- */
#sidebarToggle,
.sidebar-toggle-global {
    position: fixed !important;
    top: 12px !important;
    left: 12px !important;
    width: 44px !important;
    height: 44px !important;
    background: var(--sidebar-bg) !important;
    border: 2px solid rgba(255,255,255,0.04) !important;
    border-radius: 8px !important;
    cursor: pointer !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
    align-items: center !important;
    gap: 5px !important;
    z-index: 99999 !important; /* Very high z-index to ensure it's always on top */
    padding: 6px !important;
    transition: transform var(--transition-fast) ease, background var(--transition-fast) ease !important;
    box-shadow: 0 6px 18px rgba(0,0,0,0.12) !important;
    pointer-events: auto !important;
    user-select: none !important;
    -webkit-user-select: none !important;
    -moz-user-select: none !important;
    -ms-user-select: none !important;
    margin: 0 !important;
    opacity: 1 !important;
    visibility: visible !important;
}

/* Ensure button and all children are clickable */
#sidebarToggle *,
.sidebar-toggle-global,
.sidebar-toggle-global * {
    pointer-events: auto !important;
    cursor: pointer !important;
}

.sidebar-toggle-global:hover {
    background: #34495e;
    transform: scale(1.05);
}

.sidebar-toggle-global:active {
    transform: scale(0.95);
    background: #2c3e50;
}

.sidebar-toggle-global:focus { 
    outline: 3px solid rgba(52,152,219,0.18); 
    outline-offset: 3px; 
}

#sidebarToggle span,
.sidebar-toggle-global span { 
    width: 22px !important; 
    height: 2px !important; 
    background: #fff !important; 
    border-radius: 1px !important; 
    display: block !important; 
    transition: all var(--transition-fast) ease !important; 
    transform-origin: center !important;
    pointer-events: none !important; /* Spans should not block clicks */
    margin: 0 !important;
    padding: 0 !important;
}

/* Toggle animation when open (we toggle body.sidebar-open) */
body.sidebar-open .sidebar-toggle-global span:nth-child(1) { transform: rotate(45deg) translateY(6px); }
body.sidebar-open .sidebar-toggle-global span:nth-child(2) { opacity:0; transform: translateX(-8px); }
body.sidebar-open .sidebar-toggle-global span:nth-child(3) { transform: rotate(-45deg) translateY(-6px); }

/* ---------- Desktop collapsed state ---------- */
.sidebar.sidebar-collapsed { width: var(--sidebar-collapsed); }
.sidebar.sidebar-collapsed .brand { justify-content: center; padding: 18px 0; }
.sidebar.sidebar-collapsed .brand .brand-title { display:none; }
.sidebar.sidebar-collapsed .sidebar-menu li a span { display:none; }
.sidebar.sidebar-collapsed .sidebar-menu li a { justify-content: center; }
.sidebar.sidebar-collapsed .menu-section-label { display:none; }
.sidebar.sidebar-collapsed .profile-details { display:none; }
.sidebar.sidebar-collapsed .profile-info { justify-content: center; }

/* Toggle button position - always visible, adjusts with sidebar */
@media (min-width: 769px) {
    /* Button stays in same position regardless of sidebar state */
    .sidebar-toggle-global {
        left: 12px !important;
    }
    
    /* When sidebar is collapsed, button is still visible */
    body.sidebar-collapsed .sidebar-toggle-global {
        left: 12px !important;
    }
}

/* Move content to the right when sidebar present (desktop) */
/* We'll target .main-content later to provide spacing */

/* ---------- Mobile (slide over) ---------- */
@media (max-width: 768px) {

    /* Sidebar becomes overlay; default hidden by transform: translateX(-100%) */
    .sidebar {
        transform: translateX(-100%);
        width: var(--sidebar-width);
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        transition: transform var(--transition-medium) cubic-bezier(.2,.9,.3,1);
        box-shadow: 0 2px 28px rgba(0,0,0,0.45);
    }
    /* When body has class 'sidebar-open', bring sidebar into view */
    body.sidebar-open .sidebar {
        transform: translateX(0);
    }

    /* Add overlay for backdrop */
    .sidebar-backdrop {
        display:none;
        position: fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background: rgba(0,0,0,0.45);
        z-index: calc(var(--z-sidebar) - 1);
        opacity:0;
        transition: opacity var(--transition-medium) ease;
    }
    body.sidebar-open .sidebar-backdrop { display:block; opacity:1; }

    /* Toggle button visual tweak so it sits above sidebar */
    .sidebar-toggle-global { left: 12px; top: 12px; z-index: 3001; }

    /* Page content spans full width on mobile */
    .content,
    .main-content { 
        margin-left: 0 !important; 
        width: 100% !important; 
    }

}

/* ---------- Content wrapper - Support both .content and .main-content ---------- */
.content,
.main-content {
    flex: 1;
    min-height: 100vh;
    margin-left: var(--sidebar-width);
    transition: margin-left var(--transition-medium) ease;
    box-sizing: border-box;
}

body.sidebar-collapsed .content,
body.sidebar-collapsed .main-content { 
    margin-left: var(--sidebar-collapsed) !important; 
}

/* Desktop: ensure content adjusts when sidebar is fixed */
@media (min-width: 769px) {
    .content,
    .main-content {
        margin-left: var(--sidebar-width) !important;
        transition: margin-left var(--transition-medium) ease !important;
    }
    
    body.sidebar-collapsed .content,
    body.sidebar-collapsed .main-content {
        margin-left: var(--sidebar-collapsed) !important;
    }
    
    /* Ensure content doesn't get stuck with wrong margins */
    body:not(.sidebar-collapsed) .content,
    body:not(.sidebar-collapsed) .main-content {
        margin-left: var(--sidebar-width) !important;
    }
}

/* ---------- Utility classes & accessibility helpers ---------- */
.sr-only { position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden; }
.help-text { font-size:13px; color:var(--muted); margin-top:6px; }

/* ---------- Minor responsive tweaks ---------- */
@media (max-width: 992px) {
    .sidebar .brand .brand-title { font-size:15px; }
}

/* ---------- END CSS ---------- */
</style>
<!-- ====== END: Inline CSS ====== -->

<!-- Mobile backdrop (will be toggled via body.sidebar-open) -->
<div class="sidebar-backdrop" id="sidebarBackdrop" tabindex="-1" aria-hidden="true"></div>

<!-- =========================================
     Sidebar (main) — you can drop your PHP menu here
     ========================================= -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Main sidebar">
    <div class="brand" role="banner">
        <i class="fas fa-building" aria-hidden="true"></i>
        <span class="brand-title"><?php echo ($user_role === 'host' || $user_role === 'manager') ? 'BookIT Host' : 'BookIT Admin'; ?></span>
    </div>

    <nav class="sidebar-menu" role="menu" aria-label="Primary">
        <div class="menu-section-label">MENU</div>
        <ul role="none">
            <?php if ($user_role === 'host' || $user_role === 'manager'): ?>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/host/host_dashboard.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'host_dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt" aria-hidden="true"></i><span>Dashboard</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/host/unit_management.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'unit_management.php') ? 'active' : ''; ?>">
                        <i class="fas fa-home" aria-hidden="true"></i><span>Unit Management</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/host/reservations.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'reservations.php') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check" aria-hidden="true"></i><span>Reservations</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/host/payment_management.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'payment_management.php') ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card" aria-hidden="true"></i><span>Payments</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/host/amenities.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'amenities.php') ? 'active' : ''; ?>">
                        <i class="fas fa-star" aria-hidden="true"></i><span>Amenities</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/host/reviews.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'reviews.php') ? 'active' : ''; ?>">
                        <i class="fas fa-comments" aria-hidden="true"></i><span>Feedback & Reviews</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/host/notifications.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'notifications.php') ? 'active' : ''; ?>">
                        <i class="fas fa-bell" aria-hidden="true"></i><span>Notifications</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/host/profile.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'profile.php') ? 'active' : ''; ?>">
                        <i class="fas fa-user-cog" aria-hidden="true"></i><span>Profile & Settings</span>
                    </a>
                </li>
            <?php else: /* admin */ ?>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/admin/admin_dashboard.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt" aria-hidden="true"></i><span>Dashboard</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/admin/manage_branch.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'manage_branch.php') ? 'active' : ''; ?>">
                        <i class="fas fa-code-branch" aria-hidden="true"></i><span>Branch Management</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/admin/user_management.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'user_management.php') ? 'active' : ''; ?>">
                        <i class="fas fa-users" aria-hidden="true"></i><span>User Management</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/admin/unit_management.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'unit_management.php') ? 'active' : ''; ?>">
                        <i class="fas fa-home" aria-hidden="true"></i><span>Unit Management</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/modules/reservations.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'reservations.php') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check" aria-hidden="true"></i><span>Reservation Management</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/modules/payment_management.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'payment_management.php') ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card" aria-hidden="true"></i><span>Payment Management</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/modules/amenities.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'amenities.php') ? 'active' : ''; ?>">
                        <i class="fas fa-swimming-pool" aria-hidden="true"></i><span>Amenity Management</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/admin/reports.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'reports.php') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line" aria-hidden="true"></i><span>Reports</span>
                    </a>
                </li>
                <li role="none">
                    <a role="menuitem" href="<?php echo SITE_URL; ?>/admin/settings.php"
                        class="<?php echo (basename($_SERVER['PHP_SELF']) === 'settings.php') ? 'active' : ''; ?>">
                        <i class="fas fa-cog" aria-hidden="true"></i><span>Settings</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- PROFILE SECTION -->
    <div class="sidebar-profile-section" role="region" aria-label="Account">
        <div class="profile-section-label">ACCOUNT</div>
        <div class="sidebar-profile">
            <div class="profile-info">
                <div class="profile-avatar"><i class="fas fa-user-circle" aria-hidden="true"></i></div>
                <div class="profile-details">
                    <span class="profile-name"><?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?></span>
                    <span class="profile-role"><?php echo htmlspecialchars(ucfirst($_SESSION['role'] ?? 'Admin')); ?></span>
                </div>
            </div>
            <a class="logout-btn" href="<?php echo SITE_URL; ?>/public/logout.php" title="Logout" aria-label="Logout">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</aside>

<!-- ====== START: Inline JS (full logic) ======
     NOTE: The original "BookIT Sidebar Demo JS" IIFE (legacy/demo code)
     has been commented out below to avoid duplicate event listeners and
     conflicting state between two sidebar controllers. The demo code is
     preserved here for reference during debugging — it will NOT execute.
-->
<!--
<script>
/*
    BookIT Sidebar Demo JS (COMMENTED OUT FOR PRODUCTION)
    - Legacy/demo implementation preserved for reference only.
    - Disabled to prevent duplicate handlers and state conflicts.
*/

/* ---------- Configuration & Utilities ---------- */
(function () {
    'use strict';

    var LOGGING = false; // set to true to enable console logs

    function debug() {
        if (!LOGGING) return;
        var args = Array.prototype.slice.call(arguments);
        args.unshift('[SIDEBAR-DEMO]');
        console.log.apply(console, args);
    }

    // Small helper to throttle calls (used for resize)
    function throttle(fn, wait) {
        var last = 0;
        return function () {
            var now = Date.now();
            if (now - last >= wait) {
                last = now;
                fn.apply(this, arguments);
            }
        };
    }

    // Safely parse a boolean-like string
    function parseBool(value) {
        if (typeof value === 'boolean') return value;
        if (value === 'true' || value === '1' || value === 1) return true;
        return false;
    }

    /* ---------- DOM elements ---------- */
    var globalBtn = null;
    var sidebar = null;
    var backdrop = null;
    var roleLabel = null;
    var demoToggleRole = null;

    // Media query helper - returns true if mobile mode should be used
    function isMobile() {
        try {
            return window.matchMedia('(max-width: 768px)').matches;
        } catch (e) {
            // Fallback: use window width
            return window.innerWidth <= 768;
        }
    }

    // (rest of legacy demo code preserved for reference)
    // ...

})(); // end closure
</script>
-->
<!-- ====== END: Inline JS (full logic) ====== -->

<script>
// ====== Sidebar Toggle Script - Improved Version ======
(function() {
    'use strict';
    // NOTE: This is the active sidebar controller. The legacy/demo implementation
    // earlier in this file has been disabled to prevent duplicate event handlers
    // and conflicting updates to shared DOM state (e.g., body classes and ARIA
    // attributes). Keep this script as the single source-of-truth for toggling
    // the sidebar and updating related accessibility attributes.
    //
    // High-level responsibilities:
    // - Initialize a reliable toggle button (id="sidebarToggle")
    // - Manage mobile slide-over and desktop collapse states via body classes
    // - Keep ARIA attributes (`aria-expanded`, `aria-controls`, `role`) correct
    // - Provide multiple initialization attempts to handle late-loaded markup
    // - Avoid duplicate listeners using `data-sidebar-initialized` marker
    
    // ====== SELECTORS ======
    const body = document.body;
    const sidebar = document.getElementById("sidebar");
    let toggleBtn = document.getElementById("sidebarToggle"); // Use let instead of const for reassignment
    const backdrop = document.getElementById("sidebarBackdrop");
    const contentElements = document.querySelectorAll('.content, .main-content');
    
    // Helper function to get toggle button (always fresh)
    function getToggleBtn() {
        return document.getElementById("sidebarToggle");
    }

    // Check if mobile
    function isMobile() {
        return window.matchMedia("(max-width: 768px)").matches;
    }

    // Update content margins based on sidebar state (using CSS classes only)
    function updateContentMargin() {
        const mobile = isMobile();
        
        contentElements.forEach(function(content) {
            if (!content) return;
            
            if (mobile) {
                // On mobile, content should always be full width
                content.style.marginLeft = '0';
                content.style.width = '100%';
            } else {
                // On desktop, remove ALL inline margin styles and let CSS handle it
                // CSS classes will automatically adjust based on body.sidebar-collapsed
                content.style.marginLeft = '';
                content.style.width = '';
            }
        });
    }

    // Toggle function
    function handleToggle(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Get fresh reference to toggle button
        const btn = getToggleBtn();
        const mobile = isMobile();

        if (mobile) {
            // MOBILE: slide-in / slide-out overlay
            body.classList.toggle("sidebar-open");
            const isOpen = body.classList.contains("sidebar-open");
            
            // Update aria-expanded
            if (btn) btn.setAttribute("aria-expanded", isOpen ? "true" : "false");
            
            // Update content immediately for mobile
            updateContentMargin();
        } else {
            // DESKTOP: collapse/expand (280px <-> 64px)
            const isCollapsed = body.classList.contains("sidebar-collapsed");
            
            // Toggle the collapsed state
            if (isCollapsed) {
                // Expand
                if (sidebar) sidebar.classList.remove("sidebar-collapsed");
                body.classList.remove("sidebar-collapsed");
            } else {
                // Collapse
                if (sidebar) sidebar.classList.add("sidebar-collapsed");
                body.classList.add("sidebar-collapsed");
            }
            
            // Clear any inline styles and let CSS handle the transition
            // Use requestAnimationFrame to ensure DOM has updated
            requestAnimationFrame(function() {
                updateContentMargin();
            });
            
            // Update aria-expanded
            if (btn) {
                const nowCollapsed = body.classList.contains("sidebar-collapsed");
                btn.setAttribute("aria-expanded", nowCollapsed ? "false" : "true");
            }
        }
    }

    // ====== CLICK TOGGLE BUTTON - Multiple event types for reliability ======
    function setupToggleButton() {
        const btn = document.getElementById("sidebarToggle");
        
        if (!btn) {
            console.warn("Sidebar toggle button not found! Make sure sidebar_init.php is included.");
            // Update toggleBtn variable to null
            toggleBtn = null;
            return;
        }
        
        // Update the toggleBtn variable
        toggleBtn = btn;
        
        // Force button to be on top and clickable
        btn.style.position = "fixed";
        btn.style.top = "12px";
        btn.style.left = "12px";
        btn.style.zIndex = "99999";
        btn.style.pointerEvents = "auto";
        btn.style.cursor = "pointer";
        btn.style.display = "flex";
        
        // Check if already initialized to avoid duplicate listeners
        if (btn.hasAttribute("data-sidebar-initialized")) {
            return; // Already set up
        }
        
        // Mark as initialized
        btn.setAttribute("data-sidebar-initialized", "true");
        
        // Add a single click handler. Using only 'click' (instead of mousedown/touchstart)
        // ensures a normal tap/click toggles the sidebar immediately without requiring
        // a long-press. Avoid calling stopImmediatePropagation so other UI code can
        // still respond if needed.
        btn.addEventListener("click", function(e) {
            // preventDefault() is sufficient for a button element; avoid stopping
            // propagation to reduce interference with other handlers.
            e.preventDefault();
            handleToggle(e);
            return false;
        }, false);
        
        // Ensure button is focusable and accessible
        btn.setAttribute("tabindex", "0");
        btn.setAttribute("role", "button");
        btn.setAttribute("aria-label", "Toggle sidebar");
        btn.setAttribute("aria-controls", "sidebar");
        
        // Keyboard support
        btn.addEventListener("keydown", function(e) {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                e.stopPropagation();
                handleToggle(e);
                return false;
            }
        }, true);
        
        // No document-level delegation fallback: rely on the button's click handler
        // and the element's presence. The setup function is retried multiple times
        // below to handle late-loaded markup.
    }
    
    // Setup button immediately and also on DOM ready
    setupToggleButton();
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupToggleButton);
    }
    
    // Also try after delays to catch late-loading buttons
    setTimeout(setupToggleButton, 100);
    setTimeout(setupToggleButton, 500);

    // ====== CLICK BACKDROP CLOSE (only mobile) ======
    if (backdrop) {
        backdrop.addEventListener("click", function () {
            if (isMobile()) {
                body.classList.remove("sidebar-open");
                const btn = getToggleBtn();
                if (btn) btn.setAttribute("aria-expanded", "false");
                updateContentMargin();
            }
        });
    }

    // ====== AUTO-ADJUST ON RESIZE ======
    let resizeTimer;
    window.addEventListener("resize", function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const mobile = isMobile();
            const btn = getToggleBtn();

            if (mobile) {
                // On mobile, close sidebar if open
                body.classList.remove("sidebar-open");
                if (btn) btn.setAttribute("aria-expanded", "false");
            } else {
                // On desktop, ensure sidebar is not in mobile state
                body.classList.remove("sidebar-open");
            }
            
            // Update content margins
            updateContentMargin();
        }, 150);
    });

    // Initialize on page load
    function initialize() {
        updateContentMargin();
        // setupToggleButton is already called above, but call it again to be sure
        setupToggleButton();
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
    
    // Also try after delays to catch late-loading buttons
    setTimeout(initialize, 100);
    setTimeout(initialize, 500);
    setTimeout(initialize, 1000);
})();
</script>
