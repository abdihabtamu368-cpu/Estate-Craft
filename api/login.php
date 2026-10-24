<?php
/**
 * API Endpoint: Log In User
 * Output: JSON
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please provide both email/phone and password.'
    ]);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR phone = :phone LIMIT 1");
    $stmt->execute(['email' => $email, 'phone' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        loginUser($user);
        echo json_encode([
            'success' => true,
            'message' => "Welcome back, {$user['full_name']}!",
            'user' => [
                'id' => (int)$user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
        exit;
    }

    echo json_encode([
        'success' => false,
        'message' => 'Invalid email or password.'
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
