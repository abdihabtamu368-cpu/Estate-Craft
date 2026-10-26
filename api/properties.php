<?php
/**
 * EstateCraft Ethiopia - Properties API
 * Secure REST API for Property Listings:
 * - Public: View published listings and listing details
 * - Admin-Only: Create, Edit, and Delete property listings (Role-Based Access Control)
 */

require_once __DIR__ . '/../includes/cors.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';

try {
    $pdo = getDB();

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? ($_POST['action'] ?? '');

    // -------------------------------------------------------------------------
    // PUBLIC GET: List Properties or View Specific Property Details
    // -------------------------------------------------------------------------
    if ($method === 'GET' && empty($action)) {
        $propId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($propId > 0) {
            // Single Property Detail View
            $stmt = $pdo->prepare("
                SELECT p.*,
                       u.full_name as seller_name, u.email as seller_email, u.phone as seller_phone, u.role as seller_role,
                       img.image_path as primary_image
                FROM properties p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN property_images img ON (p.id = img.property_id AND img.is_primary = 1)
                WHERE p.id = :id
                LIMIT 1
            ");
            $stmt->execute(['id' => $propId]);
            $prop = $stmt->fetch();

            if (!$prop) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'not_found', 'message' => 'Property listing not found.']);
                exit;
            }

            $stmtImages = $pdo->prepare("SELECT id, image_path, is_primary, caption FROM property_images WHERE property_id = :pid ORDER BY is_primary DESC, id ASC");
            $stmtImages->execute(['pid' => $propId]);
            $images = $stmtImages->fetchAll();

            $prop['image_url'] = getPropertyImageUrl($prop['primary_image']);
            $prop['images'] = array_map(function($img) {
                return [
                    'id' => (int)$img['id'],
                    'url' => getPropertyImageUrl($img['image_path']),
                    'is_primary' => (int)$img['is_primary'] === 1
                ];
            }, $images);

            echo json_encode(['success' => true, 'property' => $prop]);
            exit;
        }

        // Multiple Properties List
        $where = ["p.status = 'active'"];
        $params = [];

        if (!empty($_GET['purpose']) && in_array($_GET['purpose'], ['sale', 'rent'])) {
            $where[] = "p.purpose = :purpose";
            $params['purpose'] = $_GET['purpose'];
        }
        if (!empty($_GET['type'])) {
            $where[] = "p.property_type = :type";
            $params['type'] = $_GET['type'];
        }
        if (!empty($_GET['location'])) {
            $loc = '%' . trim($_GET['location']) . '%';
            $where[] = "(p.city LIKE :loc1 OR p.state LIKE :loc2 OR p.address LIKE :loc3 OR p.title LIKE :loc4)";
            $params['loc1'] = $loc;
            $params['loc2'] = $loc;
            $params['loc3'] = $loc;
            $params['loc4'] = $loc;
        }
        if (!empty($_GET['min_price']) && is_numeric($_GET['min_price'])) {
            $where[] = "p.price >= :min_price";
            $params['min_price'] = (float)$_GET['min_price'];
        }
        if (!empty($_GET['max_price']) && is_numeric($_GET['max_price'])) {
            $where[] = "p.price <= :max_price";
            $params['max_price'] = (float)$_GET['max_price'];
        }

        $sql = "
            SELECT p.id, p.title, p.slug, p.purpose, p.property_type, p.price, p.price_frequency,
                   p.bedrooms, p.bathrooms, p.area_sqft, p.address, p.city, p.state,
                   p.latitude, p.longitude, p.description, p.has_broker, p.broker_fee_display,
                   u.full_name as seller_name, u.phone as seller_phone, u.role as seller_role,
                   img.image_path as primary_image
            FROM properties p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN property_images img ON (p.id = img.property_id AND img.is_primary = 1)
            WHERE " . implode(' AND ', $where) . "
            GROUP BY p.id
            ORDER BY p.featured DESC, p.created_at DESC
            LIMIT 100
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $output = [];
        foreach ($rows as $row) {
            $row['image_url'] = getPropertyImageUrl($row['primary_image']);
            $output[] = $row;
        }

        echo json_encode(['success' => true, 'count' => count($output), 'properties' => $output]);
        exit;
    }

    // -------------------------------------------------------------------------
    // BACKEND ROLE-BASED ACCESS CONTROL (RBAC)
    // Strictly enforces admin-only checks for Create, Edit, and Delete
    // -------------------------------------------------------------------------
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        $candidateEmail = $_SERVER['HTTP_X_ADMIN_EMAIL'] ?? ($_POST['admin_email'] ?? '');
        if (!empty($candidateEmail)) {
            $stmtAdm = $pdo->prepare("SELECT * FROM users WHERE email = :email AND (email = 'abdihabtamu368@gmail.com' OR role = 'admin') LIMIT 1");
            $stmtAdm->execute(['email' => strtolower(trim($candidateEmail))]);
            $foundAdm = $stmtAdm->fetch();
            if ($foundAdm) {
                $currentUser = $foundAdm;
            }
        }
    }

    $isAdmin = $currentUser && (
        strtolower(trim($currentUser['email'] ?? '')) === 'abdihabtamu368@gmail.com' ||
        strtolower(trim($currentUser['role'] ?? '')) === 'admin'
    );

    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'forbidden',
            'message' => 'Access denied: Only administrator Abdi Habtamu (abdihabtamu368@gmail.com) can publish, edit, or delete property listings.'
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // ADMIN ACTION: DELETE PROPERTY
    // -------------------------------------------------------------------------
    if ($action === 'delete' || $method === 'DELETE') {
        $propId = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
        if ($propId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'invalid_id', 'message' => 'Property ID is required for deletion.']);
            exit;
        }

        // Verify property exists
        $stmtCheck = $pdo->prepare("SELECT id, title FROM properties WHERE id = :id LIMIT 1");
        $stmtCheck->execute(['id' => $propId]);
        $prop = $stmtCheck->fetch();

        if (!$prop) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'not_found', 'message' => 'Property listing does not exist.']);
            exit;
        }

        // Delete associated image files
        $stmtImgs = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = :pid");
        $stmtImgs->execute(['pid' => $propId]);
        $images = $stmtImgs->fetchAll();
        foreach ($images as $img) {
            if (!preg_match('#^https?://#i', $img['image_path'])) {
                $filePath = ROOT_PATH . '/' . ltrim($img['image_path'], '/');
                if (file_exists($filePath)) @unlink($filePath);
            }
        }

        // Delete property (cascades to property_images)
        $stmtDel = $pdo->prepare("DELETE FROM properties WHERE id = :id");
        $stmtDel->execute(['id' => $propId]);

        echo json_encode([
            'success' => true,
            'message' => "Listing '{$prop['title']}' has been permanently deleted.",
            'deleted_id' => $propId
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // ADMIN ACTION: CREATE OR EDIT PROPERTY
    // -------------------------------------------------------------------------
    $propId = (int)($_POST['id'] ?? 0);
    $isEdit = ($action === 'edit' || $propId > 0);

    // Read and sanitize input fields
    $title       = trim($_POST['title'] ?? '');
    $purpose     = ($_POST['purpose'] ?? 'sale') === 'rent' ? 'rent' : 'sale';
    $propType    = strtolower(trim($_POST['property_type'] ?? 'villa'));
    $price       = isset($_POST['price']) && is_numeric($_POST['price']) ? (float)$_POST['price'] : 0;
    $frequency   = $_POST['price_frequency'] ?? ($purpose === 'rent' ? 'monthly' : 'total');
    $bedrooms    = isset($_POST['bedrooms']) ? max(0, (int)$_POST['bedrooms']) : 1;
    $bathrooms   = isset($_POST['bathrooms']) ? max(0, (int)$_POST['bathrooms']) : 1;
    $areaSqft    = isset($_POST['area_sqft']) ? max(0, (int)$_POST['area_sqft']) : 1000;
    $city        = trim($_POST['city'] ?? 'Addis Ababa');
    $address     = trim($_POST['address'] ?? $city);
    $state       = trim($_POST['state'] ?? 'Addis Ababa');
    $latitude    = isset($_POST['latitude']) && is_numeric($_POST['latitude']) ? (float)$_POST['latitude'] : 9.0108;
    $longitude   = isset($_POST['longitude']) && is_numeric($_POST['longitude']) ? (float)$_POST['longitude'] : 38.7613;
    $description = trim($_POST['description'] ?? '');
    $imageUrl    = trim($_POST['image_url'] ?? '');

    // Validation
    $errors = [];
    if (strlen($title) < 5) $errors[] = "Title must be at least 5 characters long.";
    if ($price <= 0) $errors[] = "Price must be greater than zero.";
    if (empty($city)) $errors[] = "City or neighborhood location is required.";

    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'validation_failed', 'messages' => $errors]);
        exit;
    }

    // Handle primary image upload if file provided
    $uploadedImagePath = null;
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadPropertyImage($_FILES['image_file']);
        if ($uploadResult['success']) {
            $uploadedImagePath = $uploadResult['path'];
        }
    } elseif (!empty($imageUrl)) {
        $uploadedImagePath = $imageUrl;
    }

    if ($isEdit) {
        // Edit existing listing
        $stmtCheck = $pdo->prepare("SELECT id FROM properties WHERE id = :id LIMIT 1");
        $stmtCheck->execute(['id' => $propId]);
        if (!$stmtCheck->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'not_found', 'message' => 'Property to edit was not found.']);
            exit;
        }

        $stmtUpdate = $pdo->prepare("
            UPDATE properties
            SET title = :title, purpose = :purpose, property_type = :type, price = :price,
                price_frequency = :freq, bedrooms = :beds, bathrooms = :baths, area_sqft = :sqft,
                address = :address, city = :city, state = :state, latitude = :lat, longitude = :lng,
                description = :description, updated_at = NOW()
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            'title'       => $title,
            'purpose'     => $purpose,
            'type'        => $propType,
            'price'       => $price,
            'freq'        => $frequency,
            'beds'        => $bedrooms,
            'baths'       => $bathrooms,
            'sqft'        => $areaSqft,
            'address'     => $address,
            'city'        => $city,
            'state'       => $state,
            'lat'         => $latitude,
            'lng'         => $longitude,
            'description' => $description,
            'id'          => $propId
        ]);

        if ($uploadedImagePath) {
            // Update or set primary image
            $stmtDelImg = $pdo->prepare("DELETE FROM property_images WHERE property_id = :pid AND is_primary = 1");
            $stmtDelImg->execute(['pid' => $propId]);

            $stmtInsImg = $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_primary) VALUES (:pid, :img, 1)");
            $stmtInsImg->execute(['pid' => $propId, 'img' => $uploadedImagePath]);
        }

        echo json_encode([
            'success' => true,
            'action' => 'updated',
            'message' => "Property '{$title}' updated successfully.",
            'property_id' => $propId
        ]);
        exit;

    } else {
        // Create new listing
        $slugBase = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = $slugBase . '-' . time();

        $defaultImage = $uploadedImagePath ?: 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1200&q=80';

        $stmtInsert = $pdo->prepare("
            INSERT INTO properties (
                user_id, title, slug, description, purpose, property_type, price, price_frequency,
                bedrooms, bathrooms, area_sqft, address, city, state, postal_code, country,
                latitude, longitude, status, featured, contact_name, contact_phone, contact_email,
                has_broker, broker_fee_display, views_count, created_at, updated_at
            ) VALUES (
                :uid, :title, :slug, :description, :purpose, :type, :price, :freq,
                :beds, :baths, :sqft, :address, :city, :state, '1000', 'Ethiopia',
                :lat, :lng, 'active', 1, :cname, :cphone, :cemail,
                0, 'Direct Owner & Verified Admin Listing', 0, NOW(), NOW()
            )
        ");
        $stmtInsert->execute([
            'uid'         => (int)$currentUser['id'],
            'title'       => $title,
            'slug'        => $slug,
            'description' => $description,
            'purpose'     => $purpose,
            'type'        => $propType,
            'price'       => $price,
            'freq'        => $frequency,
            'beds'        => $bedrooms,
            'baths'       => $bathrooms,
            'sqft'        => $areaSqft,
            'address'     => $address,
            'city'        => $city,
            'state'       => $state,
            'lat'         => $latitude,
            'lng'         => $longitude,
            'cname'       => $currentUser['full_name'],
            'cphone'      => $currentUser['phone'] ?? '+251 90 909 0909',
            'cemail'      => $currentUser['email']
        ]);
        $newId = (int)$pdo->lastInsertId();

        // Insert primary image
        $stmtImg = $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_primary) VALUES (:pid, :img, 1)");
        $stmtImg->execute(['pid' => $newId, 'img' => $defaultImage]);

        echo json_encode([
            'success' => true,
            'action' => 'created',
            'message' => "Property '{$title}' published successfully.",
            'property_id' => $newId,
            'property' => [
                'id' => $newId,
                'title' => $title,
                'purpose' => $purpose,
                'type' => $propType,
                'price' => $price,
                'city' => $city,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'areaSqft' => $areaSqft,
                'image' => getPropertyImageUrl($defaultImage),
                'description' => $description
            ]
        ]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'server_error',
        'message' => $e->getMessage()
    ]);
}
