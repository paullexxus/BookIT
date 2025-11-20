<?php
/**
 * Sidebar initialization helper
 * Include this right after <body> tag and before sidebar include
 * Outputs the global toggle button with asset paths
 */

// Compute assets base for global toggle button
$script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$assets_base = defined('SITE_URL') ? rtrim(SITE_URL, '/') : ($script_dir ?: '');
$menu_icon_url = $assets_base . '/assets/images/menu.svg';
?>
<button class="sidebar-toggle-global" id="sidebarToggle" aria-label="Toggle sidebar menu">
    <span></span>
    <span></span>
    <span></span>
</button>
