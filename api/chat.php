<?php
/**
 * EstateCraft Ethiopia - Live Chat API
 * Handles real-time messaging between Users and Admin (abdihabtamu368@gmail.com),
 * including Property Attachment Linkage.
 */

require_once __DIR__ . '/../includes/cors.php';
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload.php';

try {
    $pdo = getDB();

    // Auto-create chat tables if they don't exist yet (Self-healing schema)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `chat_conversations` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT UNSIGNED NOT NULL,
          `admin_id` INT UNSIGNED NOT NULL DEFAULT 5,
          `property_id` INT UNSIGNED NULL,
          `last_message` TEXT NULL,
          `last_message_at` DATETIME NULL,
          `user_unread_count` INT UNSIGNED DEFAULT 0,
          `admin_unread_count` INT UNSIGNED DEFAULT 0,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          INDEX `idx_user_id` (`user_id`),
          INDEX `idx_admin_id` (`admin_id`),
          INDEX `idx_property_id` (`property_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `chat_messages` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `conversation_id` INT UNSIGNED NOT NULL,
          `sender_id` INT UNSIGNED NOT NULL,
          `sender_role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
          `property_id` INT UNSIGNED NULL,
          `message` TEXT NOT NULL,
          `attachment_type` ENUM('none', 'image', 'audio', 'file') DEFAULT 'none',
          `attachment_url` TEXT NULL,
          `attachment_name` VARCHAR(255) NULL,
          `attachment_size` VARCHAR(50) NULL,
          `is_read` TINYINT(1) DEFAULT 0,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_conv_id` (`conversation_id`),
          INDEX `idx_sender_id` (`sender_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Self-healing schema update for existing tables
    try {
        $pdo->exec("ALTER TABLE `chat_messages` ADD COLUMN `attachment_type` ENUM('none', 'image', 'audio', 'file') DEFAULT 'none' AFTER `message`");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE `chat_messages` ADD COLUMN `attachment_url` TEXT NULL AFTER `attachment_type`");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE `chat_messages` ADD COLUMN `attachment_name` VARCHAR(255) NULL AFTER `attachment_url`");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE `chat_messages` ADD COLUMN `attachment_size` VARCHAR(50) NULL AFTER `attachment_name`");
    } catch (Exception $e) {}

    $currentUser = getCurrentUser();
    if (!$currentUser) {
        echo json_encode([
            'success' => false,
            'error' => 'unauthenticated',
            'message' => 'Please log in to chat with the administrator.'
        ]);
        exit;
    }

    $currentUserId = (int)$currentUser['id'];
    $isAdmin = (strtolower(trim($currentUser['email'] ?? '')) === 'abdihabtamu368@gmail.com');

    // Retrieve the official Admin user record
    $stmtAdmin = $pdo->prepare("SELECT id, full_name, email, phone FROM users WHERE email = 'abdihabtamu368@gmail.com' LIMIT 1");
    $stmtAdmin->execute();
    $adminUser = $stmtAdmin->fetch();
    if (!$adminUser) {
        // Fallback to first admin
        $stmtAdmin = $pdo->query("SELECT id, full_name, email, phone FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
        $adminUser = $stmtAdmin->fetch() ?: ['id' => 5, 'full_name' => 'Abdi Habtamu', 'email' => 'abdihabtamu368@gmail.com', 'phone' => '+251 90 909 0909'];
    }
    $adminId = (int)$adminUser['id'];

    $action = $_GET['action'] ?? ($_POST['action'] ?? 'get_conversation');

    // Helper: Fetch property summary card data
    function getPropertyChatCard(PDO $pdo, ?int $propId): ?array {
        if (!$propId || $propId <= 0) return null;
        $stmt = $pdo->prepare("
            SELECT p.id, p.title, p.price, p.price_frequency, p.purpose, p.property_type,
                   p.bedrooms, p.bathrooms, p.area_sqft, p.address, p.city, p.state, p.slug,
                   img.image_path as primary_image
            FROM properties p
            LEFT JOIN property_images img ON (p.id = img.property_id AND img.is_primary = 1)
            WHERE p.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $propId]);
        $prop = $stmt->fetch();
        if ($prop) {
            $prop['image_url'] = getPropertyImageUrl($prop['primary_image']);
        }
        return $prop ?: null;
    }

    // -------------------------------------------------------------------------
    // ACTION: get_threads (For Admin to view all user inquiries)
    // -------------------------------------------------------------------------
    if ($action === 'get_threads') {
        if (!$isAdmin) {
            echo json_encode(['success' => false, 'error' => 'forbidden']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT c.*, 
                   u.full_name as user_name, u.email as user_email, u.phone as user_phone, u.avatar as user_avatar,
                   p.title as property_title, p.price as property_price, p.city as property_city
            FROM chat_conversations c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN properties p ON c.property_id = p.id
            ORDER BY c.last_message_at DESC, c.id DESC
        ");
        $stmt->execute();
        $threads = $stmt->fetchAll();

        foreach ($threads as &$t) {
            $t['property'] = getPropertyChatCard($pdo, (int)($t['property_id'] ?? 0));
        }

        echo json_encode(['success' => true, 'threads' => $threads]);
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: get_conversation (Loads active chat thread + property link + messages)
    // -------------------------------------------------------------------------
    if ($action === 'get_conversation') {
        $convId = (int)($_GET['conversation_id'] ?? 0);
        $propId = (int)($_GET['property_id'] ?? 0);
        $targetUserId = (int)($_GET['user_id'] ?? 0);

        $conversation = null;

        if ($isAdmin) {
            // Admin viewing a thread
            if ($convId > 0) {
                $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $convId]);
                $conversation = $stmt->fetch();
            } elseif ($targetUserId > 0) {
                $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE user_id = :uid LIMIT 1");
                $stmt->execute(['uid' => $targetUserId]);
                $conversation = $stmt->fetch();
            }

            if (!$conversation && $targetUserId > 0) {
                // Initialize conversation for this user
                $stmt = $pdo->prepare("
                    INSERT INTO chat_conversations (user_id, admin_id, property_id, last_message_at)
                    VALUES (:uid, :aid, :pid, NOW())
                ");
                $stmt->execute([
                    'uid' => $targetUserId,
                    'aid' => $adminId,
                    'pid' => $propId > 0 ? $propId : null
                ]);
                $convId = (int)$pdo->lastInsertId();
                $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $convId]);
                $conversation = $stmt->fetch();
            }
        } else {
            // Regular user chatting with Admin Abdi
            $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE user_id = :uid LIMIT 1");
            $stmt->execute(['uid' => $currentUserId]);
            $conversation = $stmt->fetch();

            if (!$conversation) {
                $stmt = $pdo->prepare("
                    INSERT INTO chat_conversations (user_id, admin_id, property_id, last_message, last_message_at)
                    VALUES (:uid, :aid, :pid, 'Conversation started', NOW())
                ");
                $stmt->execute([
                    'uid' => $currentUserId,
                    'aid' => $adminId,
                    'pid' => $propId > 0 ? $propId : null
                ]);
                $convId = (int)$pdo->lastInsertId();

                // Welcome message from Admin
                $welcomeText = "Hello " . htmlspecialchars($currentUser['full_name']) . "! Welcome to EstateCraft Ethiopia. I am Abdi Habtamu, your administrator. How can I assist you with finding or purchasing your home today?";
                $pdo->prepare("
                    INSERT INTO chat_messages (conversation_id, sender_id, sender_role, property_id, message, is_read, created_at)
                    VALUES (:cid, :aid, 'admin', :pid, :msg, 0, NOW())
                ")->execute([
                    'cid' => $convId,
                    'aid' => $adminId,
                    'pid' => $propId > 0 ? $propId : null,
                    'msg' => $welcomeText
                ]);

                $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $convId]);
                $conversation = $stmt->fetch();
            } else {
                // If the user arrived inquiring about a specific property, link it to the conversation
                if ($propId > 0 && (int)$conversation['property_id'] !== $propId) {
                    $pdo->prepare("UPDATE chat_conversations SET property_id = :pid WHERE id = :id")
                        ->execute(['pid' => $propId, 'id' => $conversation['id']]);
                    $conversation['property_id'] = $propId;
                }
            }
        }

        if (!$conversation) {
            echo json_encode(['success' => false, 'error' => 'not_found', 'message' => 'No active conversation found.']);
            exit;
        }

        $activeConvId = (int)$conversation['id'];

        // Mark messages as read for viewer
        if ($isAdmin) {
            $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = :cid AND sender_role = 'user'")
                ->execute(['cid' => $activeConvId]);
            $pdo->prepare("UPDATE chat_conversations SET admin_unread_count = 0 WHERE id = :cid")
                ->execute(['cid' => $activeConvId]);
        } else {
            $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = :cid AND sender_role = 'admin'")
                ->execute(['cid' => $activeConvId]);
            $pdo->prepare("UPDATE chat_conversations SET user_unread_count = 0 WHERE id = :cid")
                ->execute(['cid' => $activeConvId]);
        }

        // Fetch messages
        $stmtMsg = $pdo->prepare("
            SELECT m.*, u.full_name as sender_name, u.avatar as sender_avatar
            FROM chat_messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = :cid
            ORDER BY m.created_at ASC, m.id ASC
        ");
        $stmtMsg->execute(['cid' => $activeConvId]);
        $messages = $stmtMsg->fetchAll();

        // Fetch linked user details
        $stmtUser = $pdo->prepare("SELECT id, full_name, email, phone, avatar FROM users WHERE id = :uid LIMIT 1");
        $stmtUser->execute(['uid' => $conversation['user_id']]);
        $chatUser = $stmtUser->fetch();

        // Fetch linked property details
        $activePropId = $propId > 0 ? $propId : (int)($conversation['property_id'] ?? 0);
        $linkedProperty = getPropertyChatCard($pdo, $activePropId);

        echo json_encode([
            'success' => true,
            'conversation' => $conversation,
            'admin' => [
                'id' => $adminId,
                'full_name' => $adminUser['full_name'],
                'email' => $adminUser['email'],
                'phone' => $adminUser['phone'] ?? '+251 90 909 0909'
            ],
            'user' => $chatUser,
            'is_admin' => $isAdmin,
            'property' => $linkedProperty,
            'messages' => $messages
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: send_message (User or Admin posts a message, sound, file, or image)
    // -------------------------------------------------------------------------
    if ($action === 'send_message') {
        $convId = (int)($_POST['conversation_id'] ?? 0);
        $messageText = trim($_POST['message'] ?? '');
        $propId = (int)($_POST['property_id'] ?? 0);

        // Attachment handling (Sound, File, Image)
        $attachmentType = 'none';
        $attachmentUrl = null;
        $attachmentName = null;
        $attachmentSize = null;

        if (isset($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['attachment_file'];
            $origName = basename($file['name']);
            $sizeBytes = (int)$file['size'];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if ($sizeBytes >= 1048576) {
                $attachmentSize = round($sizeBytes / 1048576, 1) . ' MB';
            } else {
                $attachmentSize = max(1, round($sizeBytes / 1024)) . ' KB';
            }

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $attachmentType = 'image';
            } elseif (in_array($ext, ['mp3', 'wav', 'ogg', 'm4a', 'webm', 'aac'])) {
                $attachmentType = 'audio';
            } else {
                $attachmentType = 'file';
            }

            $uploadChatDir = __DIR__ . '/../uploads/chat/';
            if (!is_dir($uploadChatDir)) {
                mkdir($uploadChatDir, 0755, true);
            }

            $safeFileName = 'chat_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $targetPath = $uploadChatDir . $safeFileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $attachmentUrl = BASE_URL . '/uploads/chat/' . $safeFileName;
                $attachmentName = $origName;
            }
        } elseif (!empty($_POST['attachment_data']) && !empty($_POST['attachment_type'])) {
            $attachmentType = in_array($_POST['attachment_type'], ['image', 'audio', 'file']) ? $_POST['attachment_type'] : 'none';
            $attachmentUrl = $_POST['attachment_data'];
            $attachmentName = trim($_POST['attachment_name'] ?? 'attachment');
            $attachmentSize = trim($_POST['attachment_size'] ?? '');
        }

        if ($messageText === '' && $attachmentType === 'none') {
            echo json_encode(['success' => false, 'error' => 'empty_message', 'message' => 'Message or attachment cannot be empty.']);
            exit;
        }

        $summaryText = $messageText;
        if ($summaryText === '') {
            if ($attachmentType === 'image') $summaryText = '📷 Photo';
            elseif ($attachmentType === 'audio') $summaryText = '🎙️ Voice Note';
            elseif ($attachmentType === 'file') $summaryText = '📄 ' . ($attachmentName ?: 'Document');
        }

        // Resolve or create conversation
        if ($convId <= 0) {
            if ($isAdmin) {
                $targetUserId = (int)($_POST['user_id'] ?? 0);
                if ($targetUserId <= 0) {
                    echo json_encode(['success' => false, 'error' => 'missing_user_id']);
                    exit;
                }
                $stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE user_id = :uid LIMIT 1");
                $stmt->execute(['uid' => $targetUserId]);
                $convId = (int)$stmt->fetchColumn();
                if (!$convId) {
                    $pdo->prepare("INSERT INTO chat_conversations (user_id, admin_id, property_id, last_message_at) VALUES (:uid, :aid, :pid, NOW())")
                        ->execute(['uid' => $targetUserId, 'aid' => $adminId, 'pid' => $propId > 0 ? $propId : null]);
                    $convId = (int)$pdo->lastInsertId();
                }
            } else {
                $stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE user_id = :uid LIMIT 1");
                $stmt->execute(['uid' => $currentUserId]);
                $convId = (int)$stmt->fetchColumn();
                if (!$convId) {
                    $pdo->prepare("INSERT INTO chat_conversations (user_id, admin_id, property_id, last_message_at) VALUES (:uid, :aid, :pid, NOW())")
                        ->execute(['uid' => $currentUserId, 'aid' => $adminId, 'pid' => $propId > 0 ? $propId : null]);
                    $convId = (int)$pdo->lastInsertId();
                }
            }
        }

        $senderRole = $isAdmin ? 'admin' : 'user';

        // Insert message with attachment details
        $stmtInsert = $pdo->prepare("
            INSERT INTO chat_messages (conversation_id, sender_id, sender_role, property_id, message, attachment_type, attachment_url, attachment_name, attachment_size, is_read, created_at)
            VALUES (:cid, :sid, :role, :pid, :msg, :att_type, :att_url, :att_name, :att_size, 0, NOW())
        ");
        $stmtInsert->execute([
            'cid' => $convId,
            'sid' => $currentUserId,
            'role' => $senderRole,
            'pid' => $propId > 0 ? $propId : null,
            'msg' => $messageText,
            'att_type' => $attachmentType,
            'att_url' => $attachmentUrl,
            'att_name' => $attachmentName,
            'att_size' => $attachmentSize
        ]);
        $newMsgId = (int)$pdo->lastInsertId();

        // Update conversation summary
        if ($isAdmin) {
            $pdo->prepare("
                UPDATE chat_conversations 
                SET last_message = :msg, last_message_at = NOW(), user_unread_count = user_unread_count + 1
                WHERE id = :cid
            ")->execute(['msg' => $summaryText, 'cid' => $convId]);
        } else {
            $updateSql = "
                UPDATE chat_conversations 
                SET last_message = :msg, last_message_at = NOW(), admin_unread_count = admin_unread_count + 1
            ";
            $params = ['msg' => $summaryText, 'cid' => $convId];
            if ($propId > 0) {
                $updateSql .= ", property_id = :pid ";
                $params['pid'] = $propId;
            }
            $updateSql .= " WHERE id = :cid";
            $pdo->prepare($updateSql)->execute($params);
        }

        echo json_encode([
            'success' => true,
            'conversation_id' => $convId,
            'message_id' => $newMsgId,
            'attachment' => $attachmentType !== 'none' ? [
                'type' => $attachmentType,
                'url' => $attachmentUrl,
                'name' => $attachmentName,
                'size' => $attachmentSize
            ] : null,
            'sent_at' => date('Y-m-d H:i:s')
        ]);
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: unread_count
    // -------------------------------------------------------------------------
    if ($action === 'unread_count') {
        if ($isAdmin) {
            $stmt = $pdo->query("SELECT SUM(admin_unread_count) FROM chat_conversations");
            $count = (int)$stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("SELECT user_unread_count FROM chat_conversations WHERE user_id = :uid LIMIT 1");
            $stmt->execute(['uid' => $currentUserId]);
            $count = (int)$stmt->fetchColumn();
        }
        echo json_encode(['success' => true, 'unread_count' => $count]);
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: mark_as_read
    // -------------------------------------------------------------------------
    if ($action === 'mark_as_read') {
        $convId = (int)($_POST['conversation_id'] ?? ($_GET['conversation_id'] ?? 0));
        if ($convId > 0) {
            if ($isAdmin) {
                $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = :cid AND sender_role = 'user'")
                    ->execute(['cid' => $convId]);
                $pdo->prepare("UPDATE chat_conversations SET admin_unread_count = 0 WHERE id = :cid")
                    ->execute(['cid' => $convId]);
            } else {
                $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE conversation_id = :cid AND sender_role = 'admin'")
                    ->execute(['cid' => $convId]);
                $pdo->prepare("UPDATE chat_conversations SET user_unread_count = 0 WHERE id = :cid AND user_id = :uid")
                    ->execute(['cid' => $convId, 'uid' => $currentUserId]);
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'invalid_action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'server_error',
        'message' => $e->getMessage()
    ]);
}
