<?php
/**
 * Delete Property Listing Handler
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = requireAuth();
$pdo = getDB();

$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($propertyId <= 0) {
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}

// Fetch property to verify ownership
$stmt = $pdo->prepare("SELECT id, user_id, title FROM properties WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $propertyId]);
$property = $stmt->fetch();

if (!$property) {
    $_SESSION['flash_error'] = "Listing not found.";
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}

if ($property['user_id'] != $user['id'] && $user['role'] !== 'admin') {
    $_SESSION['flash_error'] = "You are not authorized to delete this property listing.";
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}

// Delete local image files
$stmtImgs = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = :pid");
$stmtImgs->execute(['pid' => $propertyId]);
$images = $stmtImgs->fetchAll();

foreach ($images as $img) {
    if (!preg_match('#^https?://#i', $img['image_path'])) {
        $filePath = ROOT_PATH . '/' . ltrim($img['image_path'], '/');
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}

// Delete property from database (foreign key cascades to property_images and listing_reports)
$stmtDel = $pdo->prepare("DELETE FROM properties WHERE id = :id");
$stmtDel->execute(['id' => $propertyId]);

$_SESSION['flash_success'] = "Listing '" . htmlspecialchars($property['title']) . "' was successfully deleted.";
header("Location: " . BASE_URL . "/user/dashboard.php");
exit;
