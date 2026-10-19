<?php
/**
 * Database Connection Helper (PDO)
 * Real Estate Portal
 */

require_once __DIR__ . '/config.php';

function getDB(): ?PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // If connecting to the specific database failed, check if server is reachable
        try {
            // Try connecting to MySQL without database name to see if server is online
            $rootDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
            $testPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
            
            // Server is online, but database 'realestate_db' might not be created yet
            renderDbSetupPrompt("The database <strong>" . htmlspecialchars(DB_NAME) . "</strong> does not exist yet.", true);
            exit;
        } catch (PDOException $e2) {
            // Server is not running or credentials invalid
            renderDbSetupPrompt("Cannot connect to MySQL server at <code>" . DB_HOST . ":" . DB_PORT . "</code>.<br><small style='color:#64748b;font-weight:normal;'>" . htmlspecialchars($e2->getMessage()) . "</small>", false);
            exit;
        }
    }
}

/**
 * Renders a friendly troubleshooting UI when database is not yet ready
 */
function renderDbSetupPrompt(string $message, bool $serverOnline): void {
    if (php_sapi_name() === 'cli') {
        echo "[Database Error] " . strip_tags($message) . "\n";
        return;
    }

    $setupUrl = defined('BASE_URL') ? BASE_URL . '/setup.php' : 'setup.php';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Setup Required - EstateCraft</title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
            body { background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
            .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 40px; max-width: 620px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
            .badge { display: inline-block; background: #fef3c7; color: #92400e; font-size: 13px; font-weight: 700; padding: 6px 12px; border-radius: 9999px; margin-bottom: 20px; }
            h1 { font-size: 24px; margin-bottom: 12px; color: #ffffff; }
            p { color: #94a3b8; font-size: 15px; line-height: 1.6; margin-bottom: 20px; }
            .alert { background: #334155; padding: 16px; border-radius: 10px; margin-bottom: 24px; border-left: 4px solid #f59e0b; color: #f1f5f9; }
            .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #2563eb; color: #ffffff; font-weight: 600; padding: 12px 24px; border-radius: 8px; text-decoration: none; transition: 0.2s; }
            .btn:hover { background: #1d4ed8; }
            ol { margin-left: 20px; color: #cbd5e1; font-size: 14px; line-height: 1.8; margin-bottom: 20px; }
            code { background: #0f172a; padding: 2px 6px; border-radius: 4px; color: #38bdf8; font-family: monospace; }
        </style>
    </head>
    <body>
        <div class="card">
            <span class="badge">⚙️ Configuration Notice</span>
            <h1>Real Estate Database Initialization</h1>
            <div class="alert"><?= $message ?></div>
            <?php if ($serverOnline): ?>
                <p>MySQL is running! You can initialize the database tables and sample listings right now with one click:</p>
                <a href="<?= $setupUrl ?>" class="btn">🚀 Run Automatic Setup & Import Sample Data</a>
            <?php else: ?>
                <p>It looks like MySQL is not running yet. Follow these quick steps to get started:</p>
                <ol>
                    <li>Start <strong>WampServer</strong> (look for the green icon in your Windows taskbar).</li>
                    <li>Ensure the icon turns <strong>Green</strong> (Apache & MySQL services active).</li>
                    <li>Open <strong>phpMyAdmin</strong> at <code>http://localhost/phpmyadmin</code></li>
                    <li>Import the schema file located at <code>database/schema.sql</code>, or refresh this page to use the automatic installer!</li>
                </ol>
                <a href="javascript:location.reload()" class="btn" style="background:#475569;">🔄 Check Connection Again</a>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
