<?php
/**
 * API Endpoint: Save User Theme Mode Preference
 * Supports: 'light' (Day Mode), 'dark' (Night Mode), 'default' (System Default)
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

// Read JSON input or POST form data
$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? [];
$theme = $input['theme'] ?? $_POST['theme'] ?? 'default';

$allowedThemes = ['light', 'dark', 'default'];
if (!in_array($theme, $allowedThemes, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid theme mode. Choose light, dark, or default.']);
    exit;
}

// Save in session
$_SESSION['theme_mode'] = $theme;

// Set 1-year cookie for guest and authenticated persistence
setcookie('site_theme', $theme, [
    'expires'  => time() + (86400 * 365),
    'path'     => '/',
    'httponly' => false, // Accessible to JS if needed
    'samesite' => 'Lax'
]);

// If user is authenticated, save in database
$user = getCurrentUser();
if ($user) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET theme_mode = :theme WHERE id = :id");
        $stmt->execute([
            'theme' => $theme,
            'id'    => $user['id']
        ]);
        refreshCurrentUser();
    } catch (Exception $e) {
        // Log but do not fail cookie/session update
        error_log("Theme save DB error: " . $e->getMessage());
    }
}

echo json_encode([
    'success' => true,
    'theme'   => $theme,
    'message' => 'Theme updated successfully'
]);
exit;
