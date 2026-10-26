<?php
/**
 * API Endpoint: Get Platform Users / Members for Admin Portal Sync
 * Output: JSON
 */

require_once __DIR__ . '/../includes/cors.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

try {
    $pdo = getDB();

    $stmt = $pdo->query("
        SELECT id, full_name, email, phone, phone_verified, role, broker_agency, national_id_status, created_at 
        FROM users 
        ORDER BY id DESC
    ");
    $users = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'users'   => $users,
        'count'   => count($users)
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
