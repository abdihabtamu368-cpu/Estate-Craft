<?php
/**
 * API Endpoint: Register User / Broker
 * Supports AJAX fetch requests from both SPA and traditional forms
 * Output: JSON
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Accept JSON body or traditional POST
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$fullName = trim($input['full_name'] ?? $input['name'] ?? '');
$email    = trim($input['email'] ?? '');
$phone    = trim($input['phone'] ?? '+251 91 123 4567');
$password = $input['password'] ?? '';
$role     = in_array($input['role'] ?? 'user', ['user', 'broker', 'admin'], true) ? $input['role'] : 'user';

if (empty($fullName) || empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide full name, email, and password.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 6 characters.'
    ]);
    exit;
}

try {
    $pdo = getDB();

    // Check if email already registered
    $stmtCheck = $pdo->prepare("SELECT id, full_name, email, role FROM users WHERE email = :email LIMIT 1");
    $stmtCheck->execute(['email' => $email]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        // Log in user if already exists
        loginUser($existing);
        echo json_encode([
            'success' => true,
            'message' => 'Account already existed. Logged in successfully.',
            'user' => [
                'id' => (int)$existing['id'],
                'full_name' => $existing['full_name'],
                'email' => $existing['email'],
                'role' => $existing['role']
            ]
        ]);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmtInsert = $pdo->prepare("
        INSERT INTO users (
            full_name, email, password_hash, phone, phone_verified, role, created_at
        ) VALUES (
            :name, :email, :pass, :phone, 1, :role, NOW()
        )
    ");

    $stmtInsert->execute([
        'name'  => $fullName,
        'email' => $email,
        'pass'  => $passwordHash,
        'phone' => $phone,
        'role'  => $role
    ]);

    $newUserId = (int)$pdo->lastInsertId();

    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmtUser->execute(['id' => $newUserId]);
    $newUser = $stmtUser->fetch();

    if ($newUser) {
        loginUser($newUser);
    }

    echo json_encode([
        'success' => true,
        'message' => "Welcome to EstateCraft, {$fullName}!",
        'user' => [
            'id' => $newUserId,
            'full_name' => $fullName,
            'email' => $email,
            'role' => $role
        ]
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
