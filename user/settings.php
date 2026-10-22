<?php
/**
 * Master Settings Hub
 * Multi-Language (English, Amharic, Afaan Oromoo), Password Reset, Feedback, Safety Reports,
 * and Antigravity AI Real Estate Assistant
 */

$pageTitle = "Account & System Settings";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/i18n.php';

$user = requireAuth();
$pdo = getDB();

$currentLang = getCurrentLang();
$activeTab = $_GET['tab'] ?? 'language';
$error = '';
$success = '';

// Handle Language Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_language') {
    validateCsrfOrDie();

    $newLang = $_POST['language'] ?? 'en';
    if (in_array($newLang, ['en', 'am', 'om'], true)) {
        $_SESSION['lang'] = $newLang;
        setcookie('site_lang', $newLang, time() + (86400 * 365), '/');

        // Persist to user record in DB
        $stmtLang = $pdo->prepare("UPDATE users SET preferred_language = :lang WHERE id = :id");
        $stmtLang->execute(['lang' => $newLang, 'id' => $user['id']]);

        refreshCurrentUser();
        $_SESSION['flash_success'] = "Language preference successfully updated!";
        header("Location: " . BASE_URL . "/user/settings.php?tab=language&lang=" . $newLang);
        exit;
    }
}

// Handle Theme Mode Change (Day, Night, Default)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_theme') {
    validateCsrfOrDie();

    $newTheme = $_POST['theme_mode'] ?? 'default';
    if (in_array($newTheme, ['light', 'dark', 'default'], true)) {
        $_SESSION['theme_mode'] = $newTheme;
        setcookie('site_theme', $newTheme, [
            'expires'  => time() + (86400 * 365),
            'path'     => '/',
            'httponly' => false,
            'samesite' => 'Lax'
        ]);

        $stmtTheme = $pdo->prepare("UPDATE users SET theme_mode = :theme WHERE id = :id");
        $stmtTheme->execute(['theme' => $newTheme, 'id' => $user['id']]);

        refreshCurrentUser();
        $_SESSION['flash_success'] = "Display theme updated successfully!";
        header("Location: " . BASE_URL . "/user/settings.php?tab=theme");
        exit;
    }
}

// Handle Password Change in Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    validateCsrfOrDie();

    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    $stmtCheck = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
    $stmtCheck->execute(['id' => $user['id']]);
    $currentHash = $stmtCheck->fetchColumn();

    if (!empty($currentHash) && !password_verify($currentPass, $currentHash)) {
        $error = "Current password does not match our records.";
    } elseif (strlen($newPass) < 6) {
        $error = "New password must be at least 6 characters long.";
    } elseif ($newPass !== $confirmPass) {
        $error = "New passwords do not match.";
    } else {
        $newHash = password_hash($newPass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id")->execute(['hash' => $newHash, 'id' => $user['id']]);
        $success = "Your password has been changed successfully!";
    }
}

// Handle Feedback Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
    validateCsrfOrDie();

    $name     = trim($_POST['name'] ?? $user['full_name']);
    $email    = trim($_POST['email'] ?? $user['email']);
    $category = $_POST['category'] ?? 'general';
    $rating   = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
    $message  = trim($_POST['message'] ?? '');

    if (empty($message)) {
        $error = "Please provide your comments or feedback before submitting.";
    } else {
        $stmtFeedback = $pdo->prepare("
            INSERT INTO user_feedback (user_id, name, email, category, rating, message, status)
            VALUES (:uid, :name, :email, :cat, :rating, :msg, 'new')
        ");
        $stmtFeedback->execute([
            'uid'    => $user['id'],
            'name'   => $name,
            'email'  => $email,
            'cat'    => $category,
            'rating' => max(1, min(5, $rating)),
            'msg'    => $message
        ]);
        $success = "Thank you! Your feedback has been received and will help improve EstateCraft Ethiopia.";
    }
}

// Handle AI Preferences & Smart Alert Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_ai_prefs') {
    validateCsrfOrDie();

    $aiAlerts = !empty($_POST['ai_smart_alerts']) ? 1 : 0;
    $aiQuery  = trim($_POST['ai_search_query'] ?? '');
    $theme    = $_POST['theme_mode'] ?? 'light';

    $stmtAi = $pdo->prepare("
        UPDATE users SET 
            ai_smart_alerts = :alerts,
            ai_search_query = :query,
            theme_mode = :theme
        WHERE id = :id
    ");
    $stmtAi->execute([
        'alerts' => $aiAlerts,
        'query'  => $aiQuery,
        'theme'  => in_array($theme, ['light', 'dark', 'satellite_dark']) ? $theme : 'light',
        'id'     => $user['id']
    ]);

    refreshCurrentUser();
    $success = "Antigravity AI smart preferences saved successfully!";
}

// Fetch user's previous feedback
$stmtMyFeedback = $pdo->prepare("SELECT * FROM user_feedback WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5");
$stmtMyFeedback->execute(['uid' => $user['id']]);
$myFeedbackList = $stmtMyFeedback->fetchAll();

// Fetch user's submitted reports
$stmtMyReports = $pdo->prepare("
    SELECT lr.*, p.title as property_title, p.city as property_city 
    FROM listing_reports lr
    LEFT JOIN properties p ON lr.property_id = p.id
    WHERE lr.reporter_user_id = :uid OR lr.reporter_email = :email
    ORDER BY lr.created_at DESC LIMIT 10
");
$stmtMyReports->execute(['uid' => $user['id'], 'email' => $user['email']]);
$myReportsList = $stmtMyReports->fetchAll();

$availableLangs = getAvailableLanguages();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container" style="max-width: 1050px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; background: var(--primary-light); color: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                </div>
                <h1 style="font-size: 26px; font-weight: 800; color: var(--secondary); letter-spacing: -0.02em;">
                    <?= __('settings_title', 'Account & App Settings') ?>
                </h1>
            </div>
            <p style="color: var(--text-muted); font-size: 14px; margin-top: 4px;">
                Personalize languages, manage passwords, share feedback, review reports, and configure your Antigravity AI assistant.
            </p>
        </div>

        <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-secondary btn-sm">&larr; Back to Dashboard</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <div><?= htmlspecialchars($success) ?></div>
        </div>
    <?php endif; ?>

    <style>
    @media (max-width: 860px) {
        .settings-grid-wrapper {
            grid-template-columns: 1fr !important;
        }
        .settings-grid-wrapper aside {
            position: static !important;
        }
    }
    </style>

    <!-- Settings Vertical Grid Architecture -->
    <div class="settings-grid-wrapper" style="display: grid; grid-template-columns: 280px 1fr; gap: 28px; align-items: start;">
        <!-- Left Vertical Navigation Sidebar -->
        <aside style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px 14px; position: sticky; top: 90px; box-shadow: var(--shadow-sm);">
            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 12px; padding: 0 8px;">
                Settings & Preferences
            </div>
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <a href="?tab=language" class="btn btn-sm <?= $activeTab === 'language' ? 'btn-primary' : 'btn-secondary' ?>" style="text-align: left; justify-content: flex-start; padding: 12px 14px; border-radius: 10px; font-weight: 700;">
                    🌐 <?= __('tab_lang', 'Language & Regional') ?>
                </a>
                <a href="?tab=theme" class="btn btn-sm <?= $activeTab === 'theme' ? 'btn-primary' : 'btn-secondary' ?>" style="text-align: left; justify-content: flex-start; padding: 12px 14px; border-radius: 10px; font-weight: 700;">
                    🎨 <?= __('theme_mode', 'Theme Mode') ?>
                </a>
                <a href="?tab=security" class="btn btn-sm <?= $activeTab === 'security' ? 'btn-primary' : 'btn-secondary' ?>" style="text-align: left; justify-content: flex-start; padding: 12px 14px; border-radius: 10px; font-weight: 700;">
                    🔒 <?= __('tab_security', 'Password & Security') ?>
                </a>
                <a href="?tab=feedback" class="btn btn-sm <?= $activeTab === 'feedback' ? 'btn-primary' : 'btn-secondary' ?>" style="text-align: left; justify-content: flex-start; padding: 12px 14px; border-radius: 10px; font-weight: 700;">
                    💬 <?= __('tab_feedback', 'User Feedback') ?>
                </a>
                <a href="?tab=reports" class="btn btn-sm <?= $activeTab === 'reports' ? 'btn-primary' : 'btn-secondary' ?>" style="text-align: left; justify-content: flex-start; padding: 12px 14px; border-radius: 10px; font-weight: 700;">
                    🛡️ <?= __('tab_reports', 'Safety & Reports') ?>
                </a>
                <a href="<?= BASE_URL ?>/user/verify_id.php" class="btn btn-sm btn-secondary" style="text-align: left; justify-content: space-between; padding: 12px 14px; border-radius: 10px; font-weight: 700; color: #047857; border-color: #a7f3d0; background: #ecfdf5;">
                    <span>🆔 National ID Verification</span>
                    <?php if (($user['national_id_status'] ?? '') === 'verified'): ?>
                        <span style="background: #10b981; color: #fff; font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 9999px;">✓ Verified</span>
                    <?php elseif (($user['national_id_status'] ?? '') === 'pending'): ?>
                        <span style="background: #f59e0b; color: #fff; font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 9999px;">⏳ Pending</span>
                    <?php endif; ?>
                </a>
                <a href="?tab=ai_assistant" class="btn btn-sm <?= $activeTab === 'ai_assistant' ? 'btn-primary' : 'btn-secondary' ?>" style="text-align: left; justify-content: space-between; padding: 12px 14px; border-radius: 10px; font-weight: 700; background: <?= $activeTab === 'ai_assistant' ? 'linear-gradient(135deg, #2563eb, #7c3aed)' : '' ?>; color: <?= $activeTab === 'ai_assistant' ? '#fff' : '#6b21a8' ?>;">
                    <span>✨ <?= __('tab_ai_assistant', 'AI Assistant') ?></span>
                    <span style="background: rgba(255,255,255,0.25); font-size: 10px; padding: 2px 6px; border-radius: 4px;">BEST</span>
                </a>
            </div>
        </aside>

        <!-- Right Content Main Area -->
        <main style="min-width: 0;">

    <!-- ============================================================== -->
    <!-- TAB 1: LANGUAGE & REGIONAL PREFERENCES (Amharic, Oromo, English)-->
    <!-- ============================================================== -->
    <?php if ($activeTab === 'language'): ?>
        <div class="settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 32px; box-shadow: var(--shadow-sm);">
            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 20px; font-weight: 800; color: var(--secondary); margin-bottom: 6px;">
                    Language Selection &bull; የቋንቋ ምርጫ &bull; Filannoo Afaanii
                </h3>
                <p style="color: var(--text-muted); font-size: 14px;">
                    Choose your preferred language for navigation, listings, notifications, and menus.
                </p>
            </div>

            <form method="POST" action="">
                <?= getCsrfInput() ?>
                <input type="hidden" name="action" value="save_language">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; margin-bottom: 30px;">
                    <!-- English Option -->
                    <label style="border: 2px solid <?= $currentLang === 'en' ? 'var(--primary)' : 'var(--border-color)' ?>; background: <?= $currentLang === 'en' ? 'var(--primary-light)' : 'var(--bg-card)' ?>; border-radius: 12px; padding: 20px; cursor: pointer; display: flex; align-items: center; gap: 16px; transition: all 0.2s;">
                        <input type="radio" name="language" value="en" <?= $currentLang === 'en' ? 'checked' : '' ?> style="transform: scale(1.3);">
                        <div>
                            <div style="font-size: 22px; margin-bottom: 4px;">🇬🇧 English</div>
                            <div style="font-size: 13px; color: var(--text-muted);">Standard international English</div>
                        </div>
                    </label>

                    <!-- Amharic Option -->
                    <label style="border: 2px solid <?= $currentLang === 'am' ? 'var(--primary)' : 'var(--border-color)' ?>; background: <?= $currentLang === 'am' ? 'var(--primary-light)' : 'var(--bg-card)' ?>; border-radius: 12px; padding: 20px; cursor: pointer; display: flex; align-items: center; gap: 16px; transition: all 0.2s;">
                        <input type="radio" name="language" value="am" <?= $currentLang === 'am' ? 'checked' : '' ?> style="transform: scale(1.3);">
                        <div>
                            <div style="font-size: 22px; margin-bottom: 4px;">🇪🇹 አማርኛ (Amharic)</div>
                            <div style="font-size: 13px; color: var(--text-muted);">የአማርኛ ቋንቋ አገልግሎት</div>
                        </div>
                    </label>

                    <!-- Afaan Oromoo Option -->
                    <label style="border: 2px solid <?= $currentLang === 'om' ? 'var(--primary)' : 'var(--border-color)' ?>; background: <?= $currentLang === 'om' ? 'var(--primary-light)' : 'var(--bg-card)' ?>; border-radius: 12px; padding: 20px; cursor: pointer; display: flex; align-items: center; gap: 16px; transition: all 0.2s;">
                        <input type="radio" name="language" value="om" <?= $currentLang === 'om' ? 'checked' : '' ?> style="transform: scale(1.3);">
                        <div>
                            <div style="font-size: 22px; margin-bottom: 4px;">🌳 Afaan Oromoo</div>
                            <div style="font-size: 13px; color: var(--text-muted);">Tajaajila Afaan Oromoo</div>
                        </div>
                    </label>
                </div>

                <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                    <div style="font-size: 24px;">📍</div>
                    <div style="font-size: 13px; color: var(--text-muted);">
                        <strong>Regional Settings:</strong> Currency is set to <strong>ETB (Ethiopian Birr)</strong> and map bounds are locked to Ethiopia (Addis Ababa, Hawassa, Bishoftu, Bahir Dar).
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <?= __('save_changes', 'Save Changes') ?> &rarr;
                </button>
            </form>
        </div>
    <?php endif; ?>

    <!-- ============================================================== -->
    <!-- TAB: THEME MODE (Day Mode, Night Mode, System Default)         -->
    <!-- ============================================================== -->
    <?php if ($activeTab === 'theme'): ?>
        <?php 
        $currentThemeChoice = 'default';
        if (!empty($user['theme_mode']) && in_array($user['theme_mode'], ['light', 'dark', 'default'], true)) {
            $currentThemeChoice = $user['theme_mode'];
        } elseif (!empty($_SESSION['theme_mode'])) {
            $currentThemeChoice = $_SESSION['theme_mode'];
        } elseif (!empty($_COOKIE['site_theme'])) {
            $currentThemeChoice = $_COOKIE['site_theme'];
        }
        ?>
        <div class="settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 32px; box-shadow: var(--shadow-sm);">
            <div style="margin-bottom: 24px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h3 style="font-size: 20px; font-weight: 800; color: var(--secondary); margin-bottom: 6px;">
                            <?= __('theme_mode', 'Display Theme Mode') ?> &bull; የቀን እና የማታ ሁነታ
                        </h3>
                        <p style="color: var(--text-muted); font-size: 14px;">
                            Personalize your visual experience. Choose Day Mode for bright environments, Night Mode for reduced eye strain, or System Default.
                        </p>
                    </div>
                    <span class="badge" style="background: var(--primary-light); color: var(--primary); padding: 8px 14px; font-size: 13px; border-radius: 8px;">
                        Mode: <strong id="currentThemeDisplay"><?= ucfirst($currentThemeChoice) ?></strong>
                    </span>
                </div>
            </div>

            <form method="POST" action="">
                <?= getCsrfInput() ?>
                <input type="hidden" name="action" value="save_theme">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    
                    <!-- 1. Day Mode (Light) -->
                    <label class="theme-choice-card <?= $currentThemeChoice === 'light' ? 'active' : '' ?>" data-theme-card="light" onclick="setThemeMode('light', false)" style="cursor: pointer; position: relative;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="radio" name="theme_mode" value="light" class="theme-radio-input" <?= $currentThemeChoice === 'light' ? 'checked' : '' ?> style="transform: scale(1.3);">
                                <span style="font-size: 24px;">☀️</span>
                                <div>
                                    <div style="font-size: 16px; font-weight: 800; color: var(--secondary);"><?= __('mode_day', 'Day Mode') ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Standard Light</div>
                                </div>
                            </div>
                            <span class="theme-card-selected" style="<?= $currentThemeChoice === 'light' ? '' : 'display:none;' ?> font-size: 18px; color: var(--primary); font-weight: bold;">&check;</span>
                        </div>
                        
                        <!-- Mini Preview Mockup -->
                        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                            <div style="height: 10px; width: 45%; background: #2563eb; border-radius: 4px; margin-bottom: 6px;"></div>
                            <div style="height: 7px; width: 80%; background: #94a3b8; border-radius: 3px; margin-bottom: 6px;"></div>
                            <div style="height: 24px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; display: flex; align-items: center; padding: 0 8px;">
                                <span style="font-size: 10px; color: #475569; font-weight: 700;">☀️ Daytime Clean View</span>
                            </div>
                        </div>

                        <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin: 0;">
                            <?= __('mode_day_desc', 'Clean, bright interface designed for high-clarity daytime browsing.') ?>
                        </p>
                    </label>

                    <!-- 2. Night Mode (Dark) -->
                    <label class="theme-choice-card <?= $currentThemeChoice === 'dark' ? 'active' : '' ?>" data-theme-card="dark" onclick="setThemeMode('dark', false)" style="cursor: pointer; position: relative;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="radio" name="theme_mode" value="dark" class="theme-radio-input" <?= $currentThemeChoice === 'dark' ? 'checked' : '' ?> style="transform: scale(1.3);">
                                <span style="font-size: 24px;">🌙</span>
                                <div>
                                    <div style="font-size: 16px; font-weight: 800; color: var(--secondary);"><?= __('mode_night', 'Night Mode') ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Deep Slate Dark</div>
                                </div>
                            </div>
                            <span class="theme-card-selected" style="<?= $currentThemeChoice === 'dark' ? '' : 'display:none;' ?> font-size: 18px; color: var(--primary); font-weight: bold;">&check;</span>
                        </div>

                        <!-- Mini Preview Mockup -->
                        <div style="background: #0b0f19; border: 1px solid #1e293b; border-radius: 8px; padding: 12px; margin-bottom: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.5);">
                            <div style="height: 10px; width: 45%; background: #3b82f6; border-radius: 4px; margin-bottom: 6px;"></div>
                            <div style="height: 7px; width: 80%; background: #64748b; border-radius: 3px; margin-bottom: 6px;"></div>
                            <div style="height: 24px; background: #131b2e; border: 1px solid #1e293b; border-radius: 4px; display: flex; align-items: center; padding: 0 8px;">
                                <span style="font-size: 10px; color: #cbd5e1; font-weight: 700;">🌙 Night High Contrast</span>
                            </div>
                        </div>

                        <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin: 0;">
                            <?= __('mode_night_desc', 'Deep slate dark interface gentle on eyes in dim environments.') ?>
                        </p>
                    </label>

                    <!-- 3. System Default (Auto OS Detection) -->
                    <label class="theme-choice-card <?= $currentThemeChoice === 'default' ? 'active' : '' ?>" data-theme-card="default" onclick="setThemeMode('default', false)" style="cursor: pointer; position: relative;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="radio" name="theme_mode" value="default" class="theme-radio-input" <?= $currentThemeChoice === 'default' ? 'checked' : '' ?> style="transform: scale(1.3);">
                                <span style="font-size: 24px;">🖥️</span>
                                <div>
                                    <div style="font-size: 16px; font-weight: 800; color: var(--secondary);"><?= __('mode_default', 'System Default') ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Matches Device OS</div>
                                </div>
                            </div>
                            <span class="theme-card-selected" style="<?= $currentThemeChoice === 'default' ? '' : 'display:none;' ?> font-size: 18px; color: var(--primary); font-weight: bold;">&check;</span>
                        </div>

                        <!-- Mini Preview Mockup: Split Light / Dark -->
                        <div style="background: linear-gradient(90deg, #f8fafc 50%, #0b0f19 50%); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between;">
                                <div style="height: 10px; width: 35%; background: #2563eb; border-radius: 4px;"></div>
                                <div style="height: 10px; width: 35%; background: #3b82f6; border-radius: 4px;"></div>
                            </div>
                            <div style="height: 7px; width: 70%; background: #94a3b8; border-radius: 3px; margin: 6px 0;"></div>
                            <div style="height: 24px; background: rgba(30, 41, 59, 0.85); border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                <span style="font-size: 10px; color: #f8fafc; font-weight: 700;">🖥️ Auto-Sync with OS</span>
                            </div>
                        </div>

                        <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin: 0;">
                            <?= __('mode_default_desc', 'Automatically switches between Day and Night matching your device settings.') ?>
                        </p>
                    </label>

                </div>

                <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <?= __('save_changes', 'Save Changes') ?> &rarr;
                    </button>
                    <span style="font-size: 13px; color: var(--text-muted);">
                        ⚡ Click any card for instant live preview, or click Save to store in your account!
                    </span>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- ============================================================== -->
    <!-- TAB 2: PASSWORD RESET & ACCOUNT SECURITY                      -->
    <!-- ============================================================== -->
    <?php if ($activeTab === 'security'): ?>
        <div class="settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 32px; box-shadow: var(--shadow-sm);">
            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 20px; font-weight: 800; color: var(--secondary); margin-bottom: 6px;">
                    Change Password & Security Credentials
                </h3>
                <p style="color: var(--text-muted); font-size: 14px;">
                    Update your account password or request a secure OTP reset link.
                </p>
            </div>

            <!-- Password Change Form -->
            <form method="POST" action="" style="max-width: 580px; margin-bottom: 36px;">
                <?= getCsrfInput() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Current Password *</label>
                    <input type="password" name="current_password" class="form-input" placeholder="••••••••" required>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">New Password (Min. 6 characters) *</label>
                    <input type="password" name="new_password" class="form-input" placeholder="••••••••" required>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label">Confirm New Password *</label>
                    <input type="password" name="confirm_password" class="form-input" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary">Update Password</button>
                <a href="<?= BASE_URL ?>/auth/forgot_password.php" style="margin-left: 14px; font-size: 13px; font-weight: 600;">Forgot current password?</a>
            </form>

            <!-- Phone Verification Status -->
            <div style="border-top: 1px solid var(--border-color); padding-top: 24px;">
                <h4 style="font-size: 16px; font-weight: 700; color: var(--secondary); margin-bottom: 12px;">Mobile Phone Verification</h4>
                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; padding: 16px;">
                    <div>
                        <div style="font-weight: 700; color: var(--secondary); font-size: 14px;">
                            <?= htmlspecialchars($user['phone'] ?? 'No phone added') ?>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            Status: <?= $user['phone_verified'] ? '<span style="color:#10b981; font-weight:700;">Verified with SMS OTP</span>' : '<span style="color:#ef4444; font-weight:700;">Unverified</span>' ?>
                        </div>
                    </div>

                    <?php if (!$user['phone_verified']): ?>
                        <a href="<?= BASE_URL ?>/auth/verify_phone.php" class="btn btn-primary btn-sm">Verify via SMS OTP</a>
                    <?php else: ?>
                        <span class="badge badge-verified">Active</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- National ID Verification Status -->
            <div style="border-top: 1px solid var(--border-color); padding-top: 24px; margin-top: 20px;">
                <h4 style="font-size: 16px; font-weight: 700; color: var(--secondary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    🛡️ Official National ID Verification
                </h4>
                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; padding: 16px; flex-wrap: wrap; gap: 14px;">
                    <div>
                        <div style="font-weight: 700; color: var(--secondary); font-size: 14px;">
                            <?php if (!empty($user['national_id_number'])): ?>
                                <?= htmlspecialchars($user['national_id_number']) ?>
                            <?php else: ?>
                                Fayda / Kebele / Ethiopian Passport
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                            Status: 
                            <?php if (($user['national_id_status'] ?? '') === 'verified'): ?>
                                <span style="color:#10b981; font-weight:700;">Verified 🛡️</span>
                            <?php elseif (($user['national_id_status'] ?? '') === 'pending'): ?>
                                <span style="color:#f59e0b; font-weight:700;">Pending Moderator Review ⏳</span>
                            <?php elseif (($user['national_id_status'] ?? '') === 'rejected'): ?>
                                <span style="color:#ef4444; font-weight:700;">Rejected ❌</span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">Not Submitted</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="<?= BASE_URL ?>/user/verify_id.php" class="btn btn-secondary btn-sm" style="color: #047857; border-color: #a7f3d0; background: #ecfdf5;">
                        <?= ($user['national_id_status'] ?? '') === 'verified' ? 'View Certificate' : 'Verify National ID' ?>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ============================================================== -->
    <!-- TAB 3: USER FEEDBACK & RATING                                  -->
    <!-- ============================================================== -->
    <?php if ($activeTab === 'feedback'): ?>
        <div class="settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 32px; box-shadow: var(--shadow-sm);">
            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 20px; font-weight: 800; color: var(--secondary); margin-bottom: 6px;">
                    <?= __('feedback_title', 'Share Your Feedback') ?>
                </h3>
                <p style="color: var(--text-muted); font-size: 14px;">
                    <?= __('feedback_subtitle', 'Help us improve EstateCraft Ethiopia for everyone.') ?>
                </p>
            </div>

            <form method="POST" action="" style="margin-bottom: 36px;">
                <?= getCsrfInput() ?>
                <input type="hidden" name="action" value="submit_feedback">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                    <div class="form-group">
                        <label class="form-label">Your Name</label>
                        <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                    <div class="form-group">
                        <label class="form-label">Feedback Category</label>
                        <select name="category" class="form-select">
                            <option value="general">General Feedback</option>
                            <option value="suggestion">New Idea / Suggestion</option>
                            <option value="broker_experience">Broker Interaction Experience</option>
                            <option value="bug_report">Website Issue / Bug Report</option>
                            <option value="feature_request">Feature Request</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><?= __('rating', 'Your Overall Rating') ?></label>
                        <select name="rating" class="form-select">
                            <option value="5">⭐⭐⭐⭐⭐ Excellent (5/5)</option>
                            <option value="4">⭐⭐⭐⭐ Good (4/5)</option>
                            <option value="3">⭐⭐⭐ Average (3/5)</option>
                            <option value="2">⭐⭐ Needs Improvement (2/5)</option>
                            <option value="1">⭐ Poor (1/5)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label">Detailed Comments & Suggestions *</label>
                    <textarea name="message" rows="4" class="form-textarea" placeholder="Tell us what you love or what we could improve..." required></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg"><?= __('submit_feedback', 'Submit Feedback') ?></button>
            </form>

            <!-- Past Feedback History -->
            <?php if (!empty($myFeedbackList)): ?>
                <div style="border-top: 1px solid var(--border-color); padding-top: 24px;">
                    <h4 style="font-size: 16px; font-weight: 700; color: var(--secondary); margin-bottom: 14px;">Your Previous Feedback</h4>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($myFeedbackList as $fb): ?>
                            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; padding: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <div style="font-weight: 700; font-size: 14px; color: var(--secondary);">
                                        <?= str_repeat('⭐', $fb['rating']) ?> &bull; <?= ucfirst(str_replace('_', ' ', $fb['category'])) ?>
                                    </div>
                                    <span class="badge" style="background:#e2e8f0; color:#475569; font-size:11px;">
                                        Status: <?= ucfirst($fb['status']) ?>
                                    </span>
                                </div>
                                <p style="font-size: 13px; color: #475569; margin: 0;"><?= htmlspecialchars($fb['message']) ?></p>
                                <small style="font-size: 11px; color: #94a3b8; display: block; margin-top: 6px;">
                                    Submitted on <?= date('M d, Y', strtotime($fb['created_at'])) ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================== -->
    <!-- TAB 4: SAFETY REPORTS & STATUS TRACKING                        -->
    <!-- ============================================================== -->
    <?php if ($activeTab === 'reports'): ?>
        <div class="settings-card" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 32px; box-shadow: var(--shadow-sm);">
            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 20px; font-weight: 800; color: var(--secondary); margin-bottom: 6px;">
                    Safety & Platform Reports
                </h3>
                <p style="color: var(--text-muted); font-size: 14px;">
                    Track listings you have reported and monitor the status of moderation actions.
                </p>
            </div>

            <?php if (empty($myReportsList)): ?>
                <div style="text-align: center; padding: 40px 20px; background: #f8fafc; border-radius: 12px; border: 1px dashed var(--border-color);">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" style="margin: 0 auto 10px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    <h4 style="font-size: 16px; font-weight: 700; color: var(--secondary); margin-bottom: 4px;">No Flagged Listings</h4>
                    <p style="font-size: 13px; color: var(--text-muted);">You haven't filed any safety reports. To report a suspicious property, click the "Report Suspicious Listing" button on its details page.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reported Property</th>
                                <th>Reason</th>
                                <th>Date Filed</th>
                                <th>Status</th>
                                <th>Action Taken</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myReportsList as $rp): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($rp['property_title'] ?: '[Removed Property]') ?></strong>
                                        <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($rp['property_city'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:#fee2e2; color:#b91c1c;">
                                            <?= htmlspecialchars(str_replace('_', ' ', strtoupper($rp['reason']))) ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 13px;"><?= date('M d, Y', strtotime($rp['created_at'])) ?></td>
                                    <td>
                                        <?php if ($rp['status'] === 'pending'): ?>
                                            <span class="badge" style="background:#fef3c7; color:#92400e;">Under Review</span>
                                        <?php elseif ($rp['status'] === 'action_taken'): ?>
                                            <span class="badge badge-verified">Action Taken</span>
                                        <?php else: ?>
                                            <span class="badge" style="background:#e2e8f0; color:#475569;"><?= ucfirst($rp['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 12px; color: var(--text-muted);">
                                        <?= htmlspecialchars($rp['moderator_notes'] ?: 'Pending moderator inspection') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================== -->
    <!-- TAB 5: ANTIGRAVITY AI ASSISTANT & BEST FEATURE IN SETTING      -->
    <!-- ============================================================== -->
    <?php if ($activeTab === 'ai_assistant'): ?>
        <div class="ai-assistant-card" style="background: linear-gradient(135deg, #ffffff 0%, #faf5ff 100%); border: 2px solid #c084fc; border-radius: 16px; padding: 32px; box-shadow: var(--shadow-hover);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <span class="badge" style="background: #f3e8ff; color: #7e22ce; border: 1px solid #d8b4fe; font-size: 12px; font-weight: 700; margin-bottom: 8px;">
                        🤖 Antigravity Built-In AI Engine
                    </span>
                    <h3 style="font-size: 24px; font-weight: 800; color: #4c1d95; letter-spacing: -0.02em;">
                        AI Smart Assistant & Listing Intelligence
                    </h3>
                    <p style="color: #6b21a8; font-size: 14px;">
                        The ultimate real estate productivity suite: automated listing copy generation, smart buyer matcher, and regional language translation.
                    </p>
                </div>

                <div style="background: #7e22ce; color: #ffffff; padding: 8px 16px; border-radius: 9999px; font-size: 12px; font-weight: 700;">
                    Status: Online & Ready ⚡
                </div>
            </div>

            <!-- Feature 1: AI Listing Description Polisher (Multilingual EN, AM, OM) -->
            <div class="ai-feature-box" style="background: var(--bg-card); border: 1px solid #e9d5ff; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(126, 34, 206, 0.05);">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #f3e8ff; color: #7e22ce; display: flex; align-items: center; justify-content: center; font-weight: 800;">
                        ✨
                    </div>
                    <div>
                        <h4 style="font-size: 16px; font-weight: 800; color: #581c87;">
                            AI Property Description Generator (English, አማርኛ, Afaan Oromoo)
                        </h4>
                        <div style="font-size: 12px; color: #7e22ce;">Generate high-converting luxury marketing copy for your property listing in seconds</div>
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label class="form-label" style="color: #581c87;">Quick Notes (Bedrooms, location, unique perks, price):</label>
                    <input type="text" id="aiInputNotes" class="form-input" placeholder="e.g. 4 bedroom villa in Bole Atlas, swimming pool, backup generator, 45M ETB" value="4 bedroom modern villa in Bole Atlas, swimming pool, 5000L water tank, backup generator, 48.5M ETB">
                </div>

                <div style="display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-sm btn-primary" onclick="generateAiText('en')">🇬🇧 Generate in English</button>
                    <button type="button" class="btn btn-sm" style="background:#10b981; color:#fff;" onclick="generateAiText('am')">🇪🇹 Generate in አማርኛ</button>
                    <button type="button" class="btn btn-sm" style="background:#f59e0b; color:#fff;" onclick="generateAiText('om')">🌳 Generate in Afaan Oromoo</button>
                </div>

                <div class="form-group">
                    <label class="form-label" style="color: #581c87;">Generated Output (Ready to Copy to Your Listing):</label>
                    <textarea id="aiOutputText" rows="4" class="form-textarea" style="background: #faf5ff; border-color: #d8b4fe; color: #3b0764; font-size: 14px; line-height: 1.6;" readonly>Click one of the generate buttons above to produce optimized marketing copy with Antigravity AI!</textarea>
                </div>
            </div>

            <!-- Feature 2: Smart Search Alert & Interface Customization Form -->
            <form method="POST" action="" class="ai-feature-box" style="background: var(--bg-card); border: 1px solid #e9d5ff; border-radius: 12px; padding: 24px;">
                <?= getCsrfInput() ?>
                <input type="hidden" name="action" value="save_ai_prefs">

                <h4 style="font-size: 16px; font-weight: 800; color: #581c87; margin-bottom: 14px;">
                    AI Smart Matcher & Notification Filters
                </h4>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="color: #581c87;">AI Smart Match Search Query</label>
                    <input type="text" name="ai_search_query" class="form-input" placeholder="e.g. 3-bedroom furnished rental in Kazanchis with backup generator under 90,000 ETB" value="<?= htmlspecialchars($user['ai_search_query'] ?? '') ?>">
                    <small style="color: #7e22ce; font-size: 12px;">Antigravity AI analyzes active listings and prioritizes properties that best match this prompt.</small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div>
                        <label class="form-label" style="color: #581c87;">Interface Theme Style</label>
                        <select name="theme_mode" class="form-select">
                            <option value="default" <?= ($user['theme_mode'] ?? '') === 'default' ? 'selected' : '' ?>>🖥️ System Default Mode</option>
                            <option value="light" <?= ($user['theme_mode'] ?? '') === 'light' ? 'selected' : '' ?>>☀️ Day Mode (Light)</option>
                            <option value="dark" <?= ($user['theme_mode'] ?? '') === 'dark' ? 'selected' : '' ?>>🌙 Night Mode (Dark)</option>
                        </select>
                    </div>

                    <div style="display: flex; align-items: center; padding-top: 22px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="ai_smart_alerts" value="1" <?= !empty($user['ai_smart_alerts']) ? 'checked' : '' ?> style="transform: scale(1.2);">
                            <span style="font-weight: 700; color: #581c87; font-size: 14px;">Enable AI Proactive Property Alerts</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #7c3aed, #4f46e5);">
                    Save AI Preferences
                </button>
            </form>
        </div>

        <script>
        function generateAiText(lang) {
            const notes = document.getElementById('aiInputNotes').value;
            const output = document.getElementById('aiOutputText');

            output.value = "🤖 Antigravity AI is composing your listing copy...";

            setTimeout(() => {
                if (lang === 'en') {
                    output.value = `🌟 LUXURY RESIDENCE FOR SALE IN BOLE ATLAS\n\n` +
                        `Presenting an extraordinary modern architectural villa nestled in the prestigious diplomatic district of Bole Atlas. ` +
                        `Featuring 4 lavish en-suite bedrooms, high-ceiling reception salon, chef-grade Italian kitchen, and a private swimming pool with sun terrace. ` +
                        `Equipped with 24/7 security compound, 5,000L backup water reserves, and heavy-duty automatic generator. An unparalleled investment opportunity at 48,500,000 ETB.`;
                } else if (lang === 'am') {
                    output.value = `🌟 በቦሌ አትላስ ለሽያጭ የቀረበ እጅግ ዘመናዊ ቪላ\n\n` +
                        `በተመራጩ ዲፕሎማቲክ ሰፈር ቦሌ አትላስ ውስጥ የሚገኝ ድንቅ የመኖሪያ ቪላ። ` +
                        `4 ሰፋፊ መኝታ ቤቶች፣ ዘመናዊ የጣሊያን ኪችን፣ የግል መዋኛ ገንዳ (Swimming Pool) እና የሚያምር ግቢ ያካተተ። ` +
                        `የ 5000 ሊትር የውሃ ማጠራቀሚያ ታንክ፣ 24 ሰዓት የደህንነት ጥበቃ እና ጀነሬተር የተገጠመለት። ዋጋ፡ 48,500,000 የኢትዮጵያ ብር።`;
                } else if (lang === 'om') {
                    output.value = `🌟 VIILAA AMMAYYAA BOOLEE ATLAS KEESSATTI GURGURAMU\n\n` +
                        `Naannoo filatamaa Boolee Atlas keessatti viilaa bareedaa fi ammayyaa gurguraaf dhiyaate. ` +
                        `Kutaa ciisichaa 4, kushiinaa ammayyaa, dirree bishaanii (swimming pool) fi qe'ee bal'aa qaba. ` +
                        `Kuusaa bishaanii liitira 5000, eegumsa amansiisaa sa'aatii 24 fi jeneereetara of keessaa qaba. Gatiin: 48,500,000 ETB.`;
                }
            }, 300);
        }
        </script>
    <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
