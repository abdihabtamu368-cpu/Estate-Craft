<?php
/**
 * API Endpoint & Handler: Report a Listing
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

validateCsrfOrDie();

$propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
$reason = trim($_POST['reason'] ?? '');
$details = trim($_POST['details'] ?? '');
$reporterEmail = trim($_POST['reporter_email'] ?? '');

$allowedReasons = ['fraud_scam', 'incorrect_price', 'wrong_location', 'duplicate', 'offensive_content', 'other'];

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || (isset($_POST['is_ajax']));

if ($propertyId <= 0 || !in_array($reason, $allowedReasons, true)) {
    if ($isAjax) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid report parameters provided.']);
        exit;
    }
    $_SESSION['flash_error'] = "Please select a valid reason for reporting this listing.";
    header("Location: " . BASE_URL . "/properties/details.php?id=" . $propertyId);
    exit;
}

try {
    $pdo = getDB();

    $currentUser = getCurrentUser();
    $userId = $currentUser ? (int)$currentUser['id'] : null;
    if ($currentUser && empty($reporterEmail)) {
        $reporterEmail = $currentUser['email'];
    }

    // Insert Report
    $stmt = $pdo->prepare("
        INSERT INTO listing_reports (property_id, reporter_user_id, reporter_email, reason, details, status)
        VALUES (:pid, :uid, :email, :reason, :details, 'pending')
    ");
    $stmt->execute([
        'pid'     => $propertyId,
        'uid'     => $userId,
        'email'   => $reporterEmail,
        'reason'  => $reason,
        'details' => $details
    ]);

    // Check count of pending reports for this property. If >= 3, flag automatically for moderator safety!
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM listing_reports WHERE property_id = :pid AND status = 'pending'");
    $stmtCount->execute(['pid' => $propertyId]);
    $pendingCount = (int)$stmtCount->fetchColumn();

    if ($pendingCount >= 3) {
        $stmtFlag = $pdo->prepare("UPDATE properties SET status = 'flagged' WHERE id = :pid");
        $stmtFlag->execute(['pid' => $propertyId]);
    }

    $successMsg = "Thank you. Your report has been submitted for administrative review to protect our community.";

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $successMsg]);
        exit;
    }

    $_SESSION['flash_success'] = $successMsg;
    header("Location: " . BASE_URL . "/properties/details.php?id=" . $propertyId);
    exit;

} catch (Exception $e) {
    if ($isAjax) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
    $_SESSION['flash_error'] = "Could not submit report: " . $e->getMessage();
    header("Location: " . BASE_URL . "/properties/details.php?id=" . $propertyId);
    exit;
}
