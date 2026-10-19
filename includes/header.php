<?php
/**
 * Global Header Component with Multi-Language (EN, AM, OM) & Settings Gear
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/i18n.php';

$currentUser = getCurrentUser();
$activePage = $activePage ?? '';
$currentLang = getCurrentLang();
$availableLangs = getAvailableLanguages();

// Current clean URI for language switching without losing filters
$currentUri = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/index.php');
function buildLangUrl(string $targetLang): string {
    $parts = parse_url($_SERVER['REQUEST_URI'] ?? '');
    $queryParams = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $queryParams);
    }
    $queryParams['lang'] = $targetLang;
    $path = $parts['path'] ?? '';
    return $path . '?' . http_build_query($queryParams);
}

// Determine Active Theme Mode: DB (if logged in) -> Session -> Cookie -> 'default'
$activeTheme = 'default';
if (!empty($currentUser['theme_mode']) && in_array($currentUser['theme_mode'], ['light', 'dark', 'default'], true)) {
    $activeTheme = $currentUser['theme_mode'];
} elseif (!empty($_SESSION['theme_mode']) && in_array($_SESSION['theme_mode'], ['light', 'dark', 'default'], true)) {
    $activeTheme = $_SESSION['theme_mode'];
} elseif (!empty($_COOKIE['site_theme']) && in_array($_COOKIE['site_theme'], ['light', 'dark', 'default'], true)) {
    $activeTheme = $_COOKIE['site_theme'];
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLang) ?>" data-theme="<?= htmlspecialchars($activeTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . SITE_NAME : SITE_NAME . ' - ' . SITE_TAGLINE ?></title>
    
    <!-- Meta SEO -->
    <meta name="description" content="<?= isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Buy, sell, or rent residential and commercial properties in Ethiopia with verified agents, transparent broker fees, and satellite map search.' ?>">
    
    <!-- Anti-flicker Theme Initialization -->
    <script>
        window.APP_BASE_URL = <?= json_encode(BASE_URL) ?>;
        (function() {
            try {
                var storedTheme = localStorage.getItem('site_theme');
                var serverTheme = <?= json_encode($activeTheme) ?>;
                var effectiveTheme = storedTheme || serverTheme || 'default';
                document.documentElement.setAttribute('data-theme', effectiveTheme);
            } catch (e) {}
        })();
    </script>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    
    <!-- Leaflet OpenStreetMap CDN -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>
<body class="<?= ($activeTheme === 'dark') ? 'theme-dark' : '' ?>">

<header class="site-header">
    <div class="nav-container">
        <a href="<?= BASE_URL ?>/index.php" class="brand-logo" id="headerBrandLogo">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Estate</span>Craft <span style="font-size: 13px; font-weight: 700; color: #10b981; background: rgba(16, 185, 129, 0.12); padding: 2px 6px; border-radius: 4px; margin-left: 2px;">ET</span>
        </a>

        <ul class="nav-links" id="navLinks">
            <li><a href="<?= BASE_URL ?>/properties/index.php?purpose=sale" class="<?= $activePage === 'buy' ? 'active' : '' ?>"><?= __('nav_buy', 'Buy') ?></a></li>
            <li><a href="<?= BASE_URL ?>/properties/index.php?purpose=rent" class="<?= $activePage === 'rent' ? 'active' : '' ?>"><?= __('nav_rent', 'Rent') ?></a></li>
            <li><a href="<?= BASE_URL ?>/properties/create.php" class="<?= $activePage === 'sell' ? 'active' : '' ?>"><?= __('nav_sell', 'Sell') ?></a></li>
            <li><a href="<?= BASE_URL ?>/properties/index.php" class="<?= $activePage === 'browse' ? 'active' : '' ?>"><?= __('nav_explore', 'Explore All') ?></a></li>
            <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                <li><a href="<?= BASE_URL ?>/user/reports.php" class="<?= $activePage === 'reports' ? 'active' : '' ?>"><?= __('nav_safety_reports', 'Safety Reports') ?></a></li>
            <?php endif; ?>
        </ul>

        <div class="nav-actions">
            <!-- Theme Mode Switcher (Day, Night, Default) -->
            <div class="theme-dropdown-wrapper" style="position: relative; display: inline-block;">
                <button type="button" class="btn btn-secondary btn-sm" id="themeToggleBtn" style="padding: 6px 10px; display: flex; align-items: center; gap: 6px; font-size: 13px;" title="<?= __('theme_mode', 'Theme Mode') ?>" aria-label="Toggle theme mode">
                    <span id="themeHeaderIcon" style="font-size: 14px;">
                        <?= ($activeTheme === 'light') ? '☀️' : (($activeTheme === 'dark') ? '🌙' : '🖥️') ?>
                    </span>
                    <span id="themeHeaderLabel" style="font-size: 13px; font-weight: 600;">
                        <?= ($activeTheme === 'light') ? __('mode_day', 'Day') : (($activeTheme === 'dark') ? __('mode_night', 'Night') : __('mode_default', 'Default')) ?>
                    </span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div class="theme-menu" id="themeMenu" style="display: none; position: absolute; right: 0; top: 110%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; box-shadow: var(--shadow-lg); min-width: 180px; z-index: 1200; overflow: hidden;">
                    <button type="button" class="theme-menu-item" data-theme-choice="light" style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; font-size: 13px; background: none; border: none; cursor: pointer; text-align: left; color: var(--text-main); border-bottom: 1px solid var(--border-color);">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span>☀️</span>
                            <span><?= __('mode_day', 'Day Mode') ?></span>
                        </div>
                        <span class="theme-check" data-theme-check="light" style="<?= ($activeTheme === 'light') ? '' : 'display:none;' ?> color: var(--primary); font-weight: bold;">&check;</span>
                    </button>
                    <button type="button" class="theme-menu-item" data-theme-choice="dark" style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; font-size: 13px; background: none; border: none; cursor: pointer; text-align: left; color: var(--text-main); border-bottom: 1px solid var(--border-color);">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span>🌙</span>
                            <span><?= __('mode_night', 'Night Mode') ?></span>
                        </div>
                        <span class="theme-check" data-theme-check="dark" style="<?= ($activeTheme === 'dark') ? '' : 'display:none;' ?> color: var(--primary); font-weight: bold;">&check;</span>
                    </button>
                    <button type="button" class="theme-menu-item" data-theme-choice="default" style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; font-size: 13px; background: none; border: none; cursor: pointer; text-align: left; color: var(--text-main);">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span>🖥️</span>
                            <span><?= __('mode_default', 'System Default') ?></span>
                        </div>
                        <span class="theme-check" data-theme-check="default" style="<?= ($activeTheme === 'default') ? '' : 'display:none;' ?> color: var(--primary); font-weight: bold;">&check;</span>
                    </button>
                </div>
            </div>

            <!-- Language Dropdown Switcher (Amharic, Afaan Oromoo, English) -->
            <div class="lang-dropdown-wrapper" style="position: relative; display: inline-block;">
                <button type="button" class="btn btn-secondary btn-sm" id="langToggleBtn" style="padding: 6px 10px; display: flex; align-items: center; gap: 6px; font-size: 13px;">
                    <span style="font-size: 14px;"><?= $availableLangs[$currentLang]['flag'] ?></span>
                    <span><?= $availableLangs[$currentLang]['native'] ?></span>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </button>
                <div class="lang-menu" id="langMenu" style="display: none; position: absolute; right: 0; top: 110%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; box-shadow: var(--shadow-lg); min-width: 160px; z-index: 1200; overflow: hidden;">
                    <a href="<?= buildLangUrl('en') ?>" style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; font-size: 13px; color: var(--text-main); text-decoration: none; border-bottom: 1px solid var(--border-color); <?= $currentLang === 'en' ? 'background: var(--primary-light); font-weight: 700;' : '' ?>">
                        <span>🇬🇧</span> English
                    </a>
                    <a href="<?= buildLangUrl('am') ?>" style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; font-size: 13px; color: var(--text-main); text-decoration: none; border-bottom: 1px solid var(--border-color); <?= $currentLang === 'am' ? 'background: var(--primary-light); font-weight: 700;' : '' ?>">
                        <span>🇪🇹</span> አማርኛ (Amharic)
                    </a>
                    <a href="<?= buildLangUrl('om') ?>" style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; font-size: 13px; color: var(--text-main); text-decoration: none; <?= $currentLang === 'om' ? 'background: var(--primary-light); font-weight: 700;' : '' ?>">
                        <span>🌳</span> Afaan Oromoo
                    </a>
                </div>
            </div>

            <?php if ($currentUser): ?>
                <a href="<?= BASE_URL ?>/properties/create.php" class="btn btn-primary btn-sm" id="btnPostProperty">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    <?= __('nav_post_property', 'Post Property') ?>
                </a>
                
                <div style="display:flex; align-items:center; gap:6px;">
                    <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-secondary btn-sm" id="btnUserDashboard" title="My Dashboard">
                        <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:<?= $currentUser['phone_verified'] ? '#10b981' : '#f59e0b' ?>;"></span>
                        <?= __('nav_dashboard', 'Dashboard') ?>
                    </a>

                    <!-- User Settings Gear Button (User Prompt Highlight) -->
                    <a href="<?= BASE_URL ?>/user/settings.php" class="btn btn-secondary btn-sm" id="btnUserSettings" title="Settings (Languages, Password, Feedback, AI)" style="padding: 6px 8px; color: var(--secondary);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </a>

                    <?php if ($currentUser['role'] === 'admin'): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-sm" id="btnAdminPanel" title="Admin Control Center" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #f59e0b; border: 1px solid #f59e0b; font-weight: 700; padding: 6px 12px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Admin
                        </a>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-outline btn-sm" title="Sign Out" style="padding: 6px 10px;">
                        <?= __('nav_signout', 'Sign Out') ?>
                    </a>
                </div>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-secondary btn-sm" id="btnSignIn"><?= __('nav_signin', 'Sign In') ?></a>
                <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-sm" id="btnRegister"><?= __('nav_register', 'Get Started') ?></a>
            <?php endif; ?>

            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Toggle navigation menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>
        </div>
    </div>
</header>

<script>
// Language selector dropdown toggle
document.addEventListener('DOMContentLoaded', function() {
    const langBtn = document.getElementById('langToggleBtn');
    const langMenu = document.getElementById('langMenu');

    if (langBtn && langMenu) {
        langBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            langMenu.style.display = langMenu.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', function() {
            langMenu.style.display = 'none';
        });
    }
});
</script>

<!-- Flash Alerts Container -->
<div class="alert-container">
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <div><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
            <button class="alert-close" aria-label="Close notification">&times;</button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error">
            <div><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
            <button class="alert-close" aria-label="Close notification">&times;</button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_warning'])): ?>
        <div class="alert alert-warning">
            <div><?= htmlspecialchars($_SESSION['flash_warning']) ?></div>
            <button class="alert-close" aria-label="Close notification">&times;</button>
        </div>
        <?php unset($_SESSION['flash_warning']); ?>
    <?php endif; ?>
</div>

<main>
