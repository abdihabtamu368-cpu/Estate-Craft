<?php
/**
 * Image Upload Management Helper
 * Validates, sanitizes, and persists uploaded property images
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Normalizes $_FILES array when multiple files are uploaded using name="images[]"
 */
function normalizeFilesArray(array $files): array {
    $normalized = [];
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $normalized;
    }

    $fileCount = count($files['name']);
    $fileKeys = array_keys($files);

    for ($i = 0; $i < $fileCount; $i++) {
        // Skip empty entries
        if (empty($files['name'][$i]) || $files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $item = [];
        foreach ($fileKeys as $key) {
            $item[$key] = $files[$key][$i];
        }
        $normalized[] = $item;
    }

    return $normalized;
}

/**
 * Handles multiple property images upload and registers them in the database
 * 
 * @param array $files Either $_FILES['images'] or normalized array
 * @param int $propertyId The property ID
 * @param PDO $pdo Active PDO instance
 * @return array ['success' => bool, 'uploaded' => int, 'errors' => array]
 */
function handlePropertyImagesUpload(array $files, int $propertyId, PDO $pdo): array {
    $result = [
        'success' => true,
        'uploaded' => 0,
        'errors' => []
    ];

    $items = isset($files['name']) && is_array($files['name']) ? normalizeFilesArray($files) : $files;
    if (empty($items)) {
        return $result;
    }

    // Ensure upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Check if property currently has a primary image
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM property_images WHERE property_id = :pid AND is_primary = 1");
    $stmtCheck->execute(['pid' => $propertyId]);
    $hasPrimary = (int)$stmtCheck->fetchColumn() > 0;

    $stmtInsert = $pdo->prepare("
        INSERT INTO property_images (property_id, image_path, is_primary, caption) 
        VALUES (:property_id, :image_path, :is_primary, :caption)
    ");

    $finfo = extension_loaded('fileinfo') ? new finfo(FILEINFO_MIME_TYPE) : null;

    foreach ($items as $index => $file) {
        $origName = $file['name'];
        $tmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        $error = $file['error'];

        if ($error !== UPLOAD_ERR_OK) {
            $result['errors'][] = "File '{$origName}' encountered an upload error code {$error}.";
            continue;
        }

        if ($fileSize > MAX_FILE_SIZE) {
            $mbLimit = MAX_FILE_SIZE / (1024 * 1024);
            $result['errors'][] = "File '{$origName}' exceeds the maximum allowed size of {$mbLimit}MB.";
            continue;
        }

        // Validate extension
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
            $result['errors'][] = "File '{$origName}' has an invalid format. Allowed: " . implode(', ', ALLOWED_EXTENSIONS);
            continue;
        }

        // Validate MIME type
        $mime = $finfo ? $finfo->file($tmpPath) : mime_content_type($tmpPath);
        if (!in_array($mime, ALLOWED_MIME_TYPES, true)) {
            $result['errors'][] = "File '{$origName}' is not a valid image format (detected {$mime}).";
            continue;
        }

        // Generate safe unique filename
        $safeName = 'prop_' . $propertyId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = UPLOAD_DIR . $safeName;

        if (move_uploaded_file($tmpPath, $destPath)) {
            // First image uploaded becomes primary if no primary exists
            $isPrimary = (!$hasPrimary && $index === 0) ? 1 : 0;
            if ($isPrimary) {
                $hasPrimary = true;
            }

            // Store relative web path in database
            $webPath = 'uploads/properties/' . $safeName;
            $stmtInsert->execute([
                'property_id' => $propertyId,
                'image_path'  => $webPath,
                'is_primary'  => $isPrimary,
                'caption'     => pathinfo($origName, PATHINFO_FILENAME)
            ]);

            $result['uploaded']++;
        } else {
            $result['errors'][] = "Could not save uploaded file '{$origName}' to destination.";
        }
    }

    if (!empty($result['errors'])) {
        $result['success'] = false;
    }

    return $result;
}

/**
 * Returns full URL for an image path (handles external URLs or local uploads)
 */
function getPropertyImageUrl(?string $path): string {
    if (empty($path)) {
        return BASE_URL . '/assets/images/property-placeholder.svg';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return BASE_URL . '/' . ltrim($path, '/');
}
