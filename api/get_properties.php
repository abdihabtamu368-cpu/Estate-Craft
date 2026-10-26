<?php
/**
 * API Endpoint: Get Properties for Leaflet Map and Dynamic Search
 * Output: JSON
 */

require_once __DIR__ . '/../includes/cors.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/upload.php';

try {
    $pdo = getDB();

    $where = ["p.status = 'active'"];
    $params = [];

    // Filter: Purpose (sale / rent)
    if (!empty($_GET['purpose']) && in_array($_GET['purpose'], ['sale', 'rent'])) {
        $where[] = "p.purpose = :purpose";
        $params['purpose'] = $_GET['purpose'];
    }

    // Filter: Property Type
    if (!empty($_GET['type'])) {
        $where[] = "p.property_type = :type";
        $params['type'] = $_GET['type'];
    }

    // Filter: City or Location Keyword
    if (!empty($_GET['location'])) {
        $locTerm = '%' . trim($_GET['location']) . '%';
        $where[] = "(p.city LIKE :loc1 OR p.state LIKE :loc2 OR p.address LIKE :loc3 OR p.title LIKE :loc4)";
        $params['loc1'] = $locTerm;
        $params['loc2'] = $locTerm;
        $params['loc3'] = $locTerm;
        $params['loc4'] = $locTerm;
    }

    // Filter: Min Price
    if (!empty($_GET['min_price']) && is_numeric($_GET['min_price'])) {
        $where[] = "p.price >= :min_price";
        $params['min_price'] = (float)$_GET['min_price'];
    }

    // Filter: Max Price
    if (!empty($_GET['max_price']) && is_numeric($_GET['max_price'])) {
        $where[] = "p.price <= :max_price";
        $params['max_price'] = (float)$_GET['max_price'];
    }

    // Filter: Bedrooms
    if (!empty($_GET['bedrooms']) && is_numeric($_GET['bedrooms'])) {
        $where[] = "p.bedrooms >= :beds";
        $params['beds'] = (int)$_GET['bedrooms'];
    }

    $sql = "
        SELECT 
            p.id, p.title, p.slug, p.purpose, p.property_type, p.price, p.price_frequency,
            p.bedrooms, p.bathrooms, p.area_sqft, p.address, p.city, p.state,
            p.latitude, p.longitude, p.has_broker, p.broker_fee_display,
            img.image_path as primary_image
        FROM properties p
        LEFT JOIN property_images img ON (p.id = img.property_id AND img.is_primary = 1)
        WHERE " . implode(' AND ', $where) . "
        GROUP BY p.id
        ORDER BY p.featured DESC, p.created_at DESC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $properties = $stmt->fetchAll();

    $output = [];
    foreach ($properties as $row) {
        $row['currency'] = CURRENCY_SYMBOL;
        $row['image_url'] = getPropertyImageUrl($row['primary_image']);
        $row['details_url'] = BASE_URL . '/properties/details.php?id=' . $row['id'];
        $output[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'count' => count($output),
        'properties' => $output
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
