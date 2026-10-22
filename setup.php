<?php
/**
 * EstateCraft - One-Click Database Installer & Setup
 * Creates the database, tables, and loads seed listings for WampServer
 */

require_once __DIR__ . '/config/config.php';

$message = '';
$status = 'idle'; // 'idle', 'success', 'error'
$errorDetails = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' || isset($_GET['auto']) || php_sapi_name() === 'cli') {
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Create Database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `" . DB_NAME . "`;");

        // Load SQL Schema
        $sqlPath = __DIR__ . '/database/schema.sql';
        if (!file_exists($sqlPath)) {
            throw new Exception("Schema file not found at " . $sqlPath);
        }

        $sqlContent = file_get_contents($sqlPath);
        
        // Execute queries
        $pdo->exec($sqlContent);

        // Ensure upload directory exists
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }

        $status = 'success';
        $message = "Database '" . DB_NAME . "' and all tables have been successfully created with sample properties and user accounts!";
    } catch (Exception $e) {
        $status = 'error';
        $message = "Installation failed: " . $e->getMessage();
        $errorDetails = $e->getTraceAsString();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg: #0b0f19;
            --card-bg: #111827;
            --border: #1f2937;
            --text-main: #f9fafb;
            --text-muted: #9ca3af;
            --success: #10b981;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg); color: var(--text-main); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .setup-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 20px; max-width: 650px; width: 100%; padding: 40px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .logo { font-size: 26px; font-weight: 800; color: #fff; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
        .logo span { color: var(--primary); }
        .badge { display: inline-block; background: rgba(37, 99, 235, 0.15); color: #60a5fa; font-size: 13px; font-weight: 700; padding: 6px 14px; border-radius: 9999px; margin-bottom: 16px; border: 1px solid rgba(37, 99, 235, 0.3); }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 12px; }
        p { color: var(--text-muted); font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
        .alert { padding: 18px; border-radius: 12px; margin-bottom: 24px; font-size: 15px; line-height: 1.5; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; }
        .alert-error { background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
        .credentials-box { background: #0f172a; border: 1px solid #1e293b; border-radius: 12px; padding: 20px; margin-bottom: 24px; }
        .credentials-box h3 { font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 14px; }
        .cred-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #1e293b; font-size: 14px; }
        .cred-item:last-child { border-bottom: none; }
        .cred-role { font-weight: 600; color: #f8fafc; }
        .cred-val { color: #38bdf8; font-family: monospace; }
        .btn-primary { display: inline-block; background: var(--primary); color: #fff; text-decoration: none; font-weight: 600; padding: 14px 28px; border-radius: 10px; border: none; cursor: pointer; font-size: 15px; transition: background 0.2s; width: 100%; text-align: center; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-secondary { display: inline-block; background: #374151; color: #fff; text-decoration: none; font-weight: 600; padding: 14px 28px; border-radius: 10px; margin-top: 12px; width: 100%; text-align: center; }
        .btn-secondary:hover { background: #4b5563; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
        .info-card { background: #1f2937; padding: 14px; border-radius: 10px; font-size: 13px; }
        .info-card span { display: block; color: var(--text-muted); font-size: 12px; margin-bottom: 4px; }
        .info-card strong { color: #fff; font-size: 14px; }
    </style>
</head>
<body>

<div class="setup-card">
    <div class="logo">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span>Estate</span>Craft Installer
    </div>

    <span class="badge">WampServer Quick Setup</span>
    <h1>Initialize Real Estate Portal</h1>
    <p>This automated wizard connects to your local MySQL server, provisions the <code><?= DB_NAME ?></code> database, and installs the initial properties, categories, and test user accounts.</p>

    <div class="info-grid">
        <div class="info-card">
            <span>MySQL Host</span>
            <strong><?= DB_HOST ?>:<?= DB_PORT ?></strong>
        </div>
        <div class="info-card">
            <span>Database User</span>
            <strong><?= DB_USER ?></strong>
        </div>
    </div>

    <?php if ($status === 'success'): ?>
        <div class="alert alert-success">
            <strong>✅ Installation Completed Successfully!</strong><br>
            <?= htmlspecialchars($message) ?>
        </div>

        <div class="credentials-box">
            <h3>Sample Testing Accounts (Password for all: <code>Password123!</code>)</h3>
            <div class="cred-item">
                <span class="cred-role">Verified Broker</span>
                <span class="cred-val">sara.broker@realestate.local</span>
            </div>
            <div class="cred-item">
                <span class="cred-role">Direct Property Owner</span>
                <span class="cred-val">dawit.owner@example.com</span>
            </div>
            <div class="cred-item">
                <span class="cred-role">Admin / Safety Moderator</span>
                <span class="cred-val">admin@realestate.local</span>
            </div>
        </div>

        <a href="<?= BASE_URL ?>/index.php" class="btn-primary">Explore Website Homepage &rarr;</a>
    <?php elseif ($status === 'error'): ?>
        <div class="alert alert-error">
            <strong>❌ Setup Failed:</strong><br>
            <?= nl2br(htmlspecialchars($message)) ?>
        </div>
        <form method="POST">
            <button type="submit" class="btn-primary">Try Again</button>
        </form>
    <?php else: ?>
        <form method="POST">
            <button type="submit" class="btn-primary">Install Database & Seed Data Now</button>
            <a href="<?= BASE_URL ?>/index.php" class="btn-secondary">Skip to Website</a>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
