<?php
/**
 * Authentication and Session Management
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    // Cached in session for performance
    if (isset($_SESSION['user_data'])) {
        return $_SESSION['user_data'];
    }

    return refreshCurrentUser();
}

function refreshCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, phone_verified, role, avatar, bio, broker_agency, broker_license_no, broker_fee_percentage, broker_fixed_fee, broker_fee_notes, preferred_language, theme_mode, ai_smart_alerts, ai_search_query, created_at FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_data'] = $user;
        $_SESSION['user_role'] = $user['role'];
        return $user;
    }

    // User no longer exists in database
    logoutUser();
    return null;
}

function loginUser(array $user): void {
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_data'] = $user;
}

function logoutUser(): void {
    unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_name'], $_SESSION['user_role'], $_SESSION['user_data']);
    session_regenerate_id(true);
}

function requireAuth(): array {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = "Please log in to access this page.";
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/index.php');
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }
    return getCurrentUser();
}

function requireBroker(): array {
    $user = requireAuth();
    if ($user['role'] !== 'broker' && $user['role'] !== 'admin') {
        $_SESSION['flash_error'] = "This section is reserved for verified brokers.";
        header("Location: " . BASE_URL . "/user/dashboard.php");
        exit;
    }
    return $user;
}

function requireAdmin(): array {
    $user = requireAuth();
    if ($user['role'] !== 'admin') {
        $_SESSION['flash_error'] = "Access denied: Administrator privileges required.";
        header("Location: " . BASE_URL . "/index.php");
        exit;
    }
    return $user;
}
