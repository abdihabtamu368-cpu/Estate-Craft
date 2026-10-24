<?php
/**
 * EstateCraft Ethiopia - Master Administrative Portal
 * Separate, fully-featured Admin Dashboard connected directly to the database.
 * Capabilities:
 *  - Member / User Management (View, Search, Role Change, Direct Password Reset, Deletion)
 *  - Identity & Broker Verification (Approve / Revoke phone & broker licenses)
 *  - Password Reset Request Approvals (Accept, Approve & Set Password, Dismiss)
 *  - Property Listings Inventory (Approve, Flag, Feature, Delete, Filter)
 *  - Safety / Fraud Moderation Reports
 *  - Customer Feedback & Inquiries
 */

$pageTitle = "Admin Control Portal";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/i18n.php';

// Strict Admin Privileges Check
$admin = requireAdmin();
$pdo = getDB();

$activeTab = $_GET['tab'] ?? 'members';
$searchQuery = trim($_GET['q'] ?? '');
$filterRole = $_GET['role'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';

// =========================================================================
// POST ACTION HANDLERS
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie();
    $action = $_POST['action'] ?? '';

    // 1. Toggle Member Phone Verification
    if ($action === 'toggle_verification') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newStatus = (int)($_POST['new_status'] ?? 0);
        if ($userId > 0) {
            $stmt = $pdo->prepare("UPDATE users SET phone_verified = :v WHERE id = :id");
            $stmt->execute(['v' => $newStatus, 'id' => $userId]);
            $_SESSION['flash_success'] = "Member #{$userId} verification status updated to " . ($newStatus ? "Verified ✅" : "Unverified ⚠️");
        }
    }

    // 2. Change Member Role
    elseif ($action === 'change_role') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newRole = $_POST['role'] ?? 'user';
        if ($userId > 0 && in_array($newRole, ['user', 'broker', 'admin'], true)) {
            if ($userId === (int)$admin['id'] && $newRole !== 'admin') {
                $_SESSION['flash_error'] = "You cannot demote your own active administrator account.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET role = :r WHERE id = :id");
                $stmt->execute(['r' => $newRole, 'id' => $userId]);
                $_SESSION['flash_success'] = "Member #{$userId} role changed to " . ucfirst($newRole);
            }
        }
    }

    // 3. Admin Direct Password Reset for Any Member
    elseif ($action === 'admin_set_password') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newPass = $_POST['new_password'] ?? '';
        if ($userId > 0 && strlen($newPass) >= 6) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
            $stmt->execute(['h' => $hash, 'id' => $userId]);
            
            // Clear any pending reset tokens for this user
            $stmtEmail = $pdo->prepare("SELECT email FROM users WHERE id = :id");
            $stmtEmail->execute(['id' => $userId]);
            $uEmail = $stmtEmail->fetchColumn();
            if ($uEmail) {
                $pdo->prepare("DELETE FROM password_resets WHERE email = :e")->execute(['e' => $uEmail]);
            }

            $_SESSION['flash_success'] = "Password for Member #{$userId} successfully updated to: " . htmlspecialchars($newPass);
        } else {
            $_SESSION['flash_error'] = "Password must be at least 6 characters in length.";
        }
    }

    // 4. Delete Member
    elseif ($action === 'delete_user') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === (int)$admin['id']) {
            $_SESSION['flash_error'] = "You cannot delete your own administrator account.";
        } elseif ($userId > 0) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $_SESSION['flash_success'] = "Member #{$userId} and their associated listings have been removed.";
        }
    }

    // 5. Add New Member by Admin
    elseif ($action === 'add_user') {
        $name     = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $pass     = $_POST['password'] ?? 'Password123!';
        $role     = $_POST['role'] ?? 'user';
        $verified = isset($_POST['phone_verified']) ? 1 : 0;
        $agency   = trim($_POST['broker_agency'] ?? '');

        if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = "Please provide a valid name and email address.";
        } else {
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :e");
            $stmtCheck->execute(['e' => $email]);
            if ($stmtCheck->fetch()) {
                $_SESSION['flash_error'] = "An account with the email {$email} already exists.";
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmtIns = $pdo->prepare("
                    INSERT INTO users (full_name, email, phone, password_hash, role, phone_verified, broker_agency)
                    VALUES (:name, :email, :phone, :hash, :role, :verified, :agency)
                ");
                $stmtIns->execute([
                    'name'     => $name,
                    'email'    => $email,
                    'phone'    => $phone,
                    'hash'     => $hash,
                    'role'     => $role,
                    'verified' => $verified,
                    'agency'   => $role === 'broker' ? $agency : null
                ]);
                $_SESSION['flash_success'] = "New " . ucfirst($role) . " '{$name}' created successfully (Password: {$pass}).";
            }
        }
    }

    // 6. Accept / Approve Password Reset Request
    elseif ($action === 'approve_password_reset') {
        $resetId = (int)($_POST['reset_id'] ?? 0);
        $email   = trim($_POST['email'] ?? '');
        $newPass = $_POST['new_password'] ?? 'Password123!';

        if (!empty($email) && strlen($newPass) >= 6) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmtUp = $pdo->prepare("UPDATE users SET password_hash = :h WHERE email = :e");
            $stmtUp->execute(['h' => $hash, 'e' => $email]);

            // Remove the approved token
            $pdo->prepare("DELETE FROM password_resets WHERE id = :id OR email = :e")->execute(['id' => $resetId, 'e' => $email]);
            $_SESSION['flash_success'] = "Password reset request for <strong>" . htmlspecialchars($email) . "</strong> accepted! Password set to: <code>" . htmlspecialchars($newPass) . "</code>";
        } else {
            $_SESSION['flash_error'] = "Invalid email or password too short.";
        }
    }

    // 7. Dismiss / Clear Password Reset Request
    elseif ($action === 'dismiss_password_reset') {
        $resetId = (int)($_POST['reset_id'] ?? 0);
        if ($resetId > 0) {
            $pdo->prepare("DELETE FROM password_resets WHERE id = :id")->execute(['id' => $resetId]);
            $_SESSION['flash_success'] = "Password reset request #{$resetId} cleared.";
        }
    }

    // 8. Update Property Status (Active, Pending, Sold, Flagged)
    elseif ($action === 'update_property_status') {
        $propId = (int)($_POST['property_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        if ($propId > 0 && in_array($status, ['active', 'pending_review', 'sold', 'rented', 'flagged'], true)) {
            $stmt = $pdo->prepare("UPDATE properties SET status = :s WHERE id = :id");
            $stmt->execute(['s' => $status, 'id' => $propId]);
            $_SESSION['flash_success'] = "Property #{$propId} status changed to " . ucfirst(str_replace('_', ' ', $status));
        }
    }

    // 9. Toggle Featured Property
    elseif ($action === 'toggle_property_featured') {
        $propId = (int)($_POST['property_id'] ?? 0);
        $featured = (int)($_POST['featured'] ?? 0);
        if ($propId > 0) {
            $stmt = $pdo->prepare("UPDATE properties SET featured = :f WHERE id = :id");
            $stmt->execute(['f' => $featured, 'id' => $propId]);
            $_SESSION['flash_success'] = "Property #{$propId} " . ($featured ? "featured on homepage ⭐" : "unfeatured");
        }
    }

    // 10. Delete Property Listing
    elseif ($action === 'delete_property') {
        $propId = (int)($_POST['property_id'] ?? 0);
        if ($propId > 0) {
            $stmt = $pdo->prepare("DELETE FROM properties WHERE id = :id");
            $stmt->execute(['id' => $propId]);
            $_SESSION['flash_success'] = "Property #{$propId} and gallery photos permanently removed.";
        }
    }

    // 11. Handle Safety Report
    elseif ($action === 'handle_report') {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $propId   = (int)($_POST['property_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';

        if ($decision === 'dismiss' && $reportId > 0) {
            $pdo->prepare("UPDATE listing_reports SET status = 'dismissed', moderator_notes = 'Dismissed by admin' WHERE id = :id")->execute(['id' => $reportId]);
            $_SESSION['flash_success'] = "Report #{$reportId} dismissed.";
        } elseif ($decision === 'flag_listing' && $propId > 0) {
            $pdo->prepare("UPDATE properties SET status = 'flagged' WHERE id = :pid")->execute(['pid' => $propId]);
            $pdo->prepare("UPDATE listing_reports SET status = 'action_taken' WHERE property_id = :pid")->execute(['pid' => $propId]);
            $_SESSION['flash_warning'] = "Property #{$propId} flagged and removed from public search.";
        } elseif ($decision === 'delete_listing' && $propId > 0) {
            $pdo->prepare("DELETE FROM properties WHERE id = :pid")->execute(['pid' => $propId]);
            $_SESSION['flash_success'] = "Fraudulent listing #{$propId} permanently deleted.";
        }
    }

    // 12. Handle Feedback
    elseif ($action === 'handle_feedback') {
        $feedbackId = (int)($_POST['feedback_id'] ?? 0);
        $status     = $_POST['status'] ?? 'resolved';
        if ($status === 'delete') {
            $pdo->prepare("DELETE FROM user_feedback WHERE id = :id")->execute(['id' => $feedbackId]);
            $_SESSION['flash_success'] = "Feedback entry #{$feedbackId} deleted.";
        } else {
            $pdo->prepare("UPDATE user_feedback SET status = :s WHERE id = :id")->execute(['s' => $status, 'id' => $feedbackId]);
            $_SESSION['flash_success'] = "Feedback entry #{$feedbackId} marked as " . ucfirst($status);
        }
    }

    // 13. National ID Verification: Accept & Verify
    elseif ($action === 'accept_national_id') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET national_id_status = 'verified', 
                    national_id_verified_at = NOW(), 
                    national_id_rejection_reason = NULL,
                    phone_verified = 1
                WHERE id = :id
            ");
            $stmt->execute(['id' => $userId]);
            $_SESSION['flash_success'] = "National ID for Member #{$userId} accepted! Account is now officially Verified 🛡️";
        }
    }

    // 14. National ID Verification: Reject
    elseif ($action === 'reject_national_id') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $reason = trim($_POST['rejection_reason'] ?? 'Submitted document is blurry, expired, or does not match account name.');
        if ($userId > 0) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET national_id_status = 'rejected', 
                    national_id_rejection_reason = :reason 
                WHERE id = :id
            ");
            $stmt->execute(['reason' => $reason, 'id' => $userId]);
            $_SESSION['flash_warning'] = "National ID for Member #{$userId} was rejected. Reason recorded: " . htmlspecialchars($reason);
        }
    }

    // 15. National ID Verification: Reset to Unverified
    elseif ($action === 'reset_national_id') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET national_id_status = 'unverified', 
                    national_id_verified_at = NULL, 
                    national_id_rejection_reason = NULL 
                WHERE id = :id
            ");
            $stmt->execute(['id' => $userId]);
            $_SESSION['flash_info'] = "National ID status for Member #{$userId} reset to Unverified.";
        }
    }

    header("Location: " . BASE_URL . "/admin/index.php?tab=" . urlencode($activeTab));
    exit;
}

// =========================================================================
// FETCH METRICS & TAB COUNTS
// =========================================================================
$countUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$countBrokers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'broker'")->fetchColumn();
$countUnverified = $pdo->query("SELECT COUNT(*) FROM users WHERE phone_verified = 0")->fetchColumn();
$countPendingId = $pdo->query("SELECT COUNT(*) FROM users WHERE national_id_status = 'pending'")->fetchColumn();
$countVerifiedId = $pdo->query("SELECT COUNT(*) FROM users WHERE national_id_status = 'verified'")->fetchColumn();
$countProperties = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$countPendingProps = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'pending_review' OR status = 'flagged'")->fetchColumn();
$countResets = $pdo->query("SELECT COUNT(*) FROM password_resets WHERE expires_at > NOW()")->fetchColumn();
$countReports = $pdo->query("SELECT COUNT(*) FROM listing_reports WHERE status = 'pending'")->fetchColumn();
$countFeedback = $pdo->query("SELECT COUNT(*) FROM user_feedback WHERE status = 'new'")->fetchColumn();

// =========================================================================
// FETCH DATA FOR ACTIVE TAB
// =========================================================================

// Tab: National ID Approvals
$idFilterStatus = $_GET['id_status'] ?? 'all';
$nationalIdRequests = [];
if ($activeTab === 'national_id') {
    $sql = "
        SELECT id, full_name, email, phone, phone_verified, role, broker_agency,
               national_id_number, national_id_type, national_id_document,
               national_id_status, national_id_submitted_at, national_id_verified_at, national_id_rejection_reason
        FROM users 
        WHERE (national_id_status != 'unverified' OR national_id_number IS NOT NULL)
    ";
    $params = [];
    if ($idFilterStatus !== 'all') {
        $sql .= " AND national_id_status = :st";
        $params['st'] = $idFilterStatus;
    }
    if (!empty($searchQuery)) {
        $sql .= " AND (full_name LIKE :q OR email LIKE :q OR national_id_number LIKE :q)";
        $params['q'] = "%{$searchQuery}%";
    }
    $sql .= " ORDER BY CASE national_id_status WHEN 'pending' THEN 1 WHEN 'rejected' THEN 2 WHEN 'verified' THEN 3 ELSE 4 END, national_id_submitted_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $nationalIdRequests = $stmt->fetchAll();
}

// Tab: Members
$members = [];
if ($activeTab === 'members') {
    $sql = "SELECT id, full_name, email, phone, phone_verified, role, broker_agency, national_id_status, national_id_type, national_id_number, created_at FROM users WHERE 1=1";
    $params = [];
    if (!empty($searchQuery)) {
        $sql .= " AND (full_name LIKE :q OR email LIKE :q OR phone LIKE :q OR broker_agency LIKE :q)";
        $params['q'] = "%{$searchQuery}%";
    }
    if ($filterRole !== 'all') {
        $sql .= " AND role = :role";
        $params['role'] = $filterRole;
    }
    $sql .= " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $members = $stmt->fetchAll();
}

// Tab: Verifications
$verifications = [];
if ($activeTab === 'verifications') {
    $verifications = $pdo->query("
        SELECT id, full_name, email, phone, phone_verified, role, broker_agency, broker_license_no, broker_fee_percentage, broker_fixed_fee, created_at 
        FROM users 
        WHERE phone_verified = 0 OR role = 'broker'
        ORDER BY phone_verified ASC, role DESC, id DESC
    ")->fetchAll();
}

// Tab: Password Resets
$resets = [];
if ($activeTab === 'resets') {
    $resets = $pdo->query("
        SELECT pr.*, u.id as user_id, u.full_name, u.phone, u.role
        FROM password_resets pr
        LEFT JOIN users u ON pr.email = u.email
        ORDER BY pr.created_at DESC
    ")->fetchAll();
}

// Tab: Properties
$properties = [];
if ($activeTab === 'properties') {
    $sql = "
        SELECT p.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone, u.role as owner_role,
               img.image_path as primary_image
        FROM properties p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN property_images img ON (p.id = img.property_id AND img.is_primary = 1)
        WHERE 1=1
    ";
    $params = [];
    if (!empty($searchQuery)) {
        $sql .= " AND (p.title LIKE :q OR p.city LIKE :q OR p.address LIKE :q OR u.full_name LIKE :q)";
        $params['q'] = "%{$searchQuery}%";
    }
    if ($filterStatus !== 'all') {
        $sql .= " AND p.status = :status";
        $params['status'] = $filterStatus;
    }
    $sql .= " ORDER BY p.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $properties = $stmt->fetchAll();
}

// Tab: Reports
$reports = [];
if ($activeTab === 'reports') {
    $reports = $pdo->query("
        SELECT r.*, p.title as property_title, p.price as property_price, p.city as property_city, p.status as property_status,
               u.full_name as reporter_name
        FROM listing_reports r
        LEFT JOIN properties p ON r.property_id = p.id
        LEFT JOIN users u ON r.reporter_user_id = u.id
        ORDER BY r.status = 'pending' DESC, r.created_at DESC
    ")->fetchAll();
}

// Tab: Feedback
$feedbacks = [];
if ($activeTab === 'feedback') {
    $feedbacks = $pdo->query("SELECT * FROM user_feedback ORDER BY status = 'new' DESC, created_at DESC")->fetchAll();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-container" style="max-width: 1320px; margin: 30px auto; padding: 0 20px;">
    
    <!-- Admin Portal Header -->
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 16px; padding: 28px 32px; color: #ffffff; margin-bottom: 28px; box-shadow: var(--shadow-lg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <span style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                    🛡️ Administrator Control Hub
                </span>
                <span style="font-size: 13px; color: #94a3b8;">Active Session: <strong><?= htmlspecialchars($admin['full_name']) ?></strong></span>
            </div>
            <h1 style="font-size: 26px; font-weight: 800; color: #ffffff; margin: 0; letter-spacing: -0.02em;">
                EstateCraft System Management
            </h1>
            <p style="color: #94a3b8; font-size: 14px; margin: 6px 0 0 0;">
                Direct access to members, identity verification, password recovery approvals, listings, reports, and inquiries.
            </p>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addMemberModal')">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Add New Member
            </button>
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-secondary btn-sm" style="background: rgba(8, 102, 255, 0.15); border-color: rgba(8, 102, 255, 0.4); color: #93c5fd; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                🚀 Open Create Account Page
            </a>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-secondary btn-sm" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: #ffffff;">
                🌐 View Main Site
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 28px;">
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 18px; box-shadow: var(--shadow-sm);">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Members</div>
            <div style="font-size: 28px; font-weight: 800; color: var(--secondary); margin-top: 4px;"><?= number_format($countUsers) ?></div>
            <div style="font-size: 12px; color: #10b981; margin-top: 4px;">👥 <?= number_format($countBrokers) ?> Brokers</div>
        </div>

        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 18px; box-shadow: var(--shadow-sm);">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">ID Verifications</div>
            <div style="font-size: 28px; font-weight: 800; color: <?= $countPendingId > 0 ? '#ef4444' : '#10b981' ?>; margin-top: 4px;"><?= number_format($countPendingId) ?></div>
            <div style="font-size: 12px; color: #10b981; margin-top: 4px;">🛡️ <?= number_format($countVerifiedId) ?> Verified Citizens</div>
        </div>

        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 18px; box-shadow: var(--shadow-sm);">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Unverified Users</div>
            <div style="font-size: 28px; font-weight: 800; color: <?= $countUnverified > 0 ? '#f59e0b' : '#10b981' ?>; margin-top: 4px;"><?= number_format($countUnverified) ?></div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Pending phone approval</div>
        </div>

        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 18px; box-shadow: var(--shadow-sm);">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Password Resets</div>
            <div style="font-size: 28px; font-weight: 800; color: <?= $countResets > 0 ? '#ef4444' : 'var(--secondary)' ?>; margin-top: 4px;"><?= number_format($countResets) ?></div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Pending acceptance</div>
        </div>

        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 18px; box-shadow: var(--shadow-sm);">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">All Properties</div>
            <div style="font-size: 28px; font-weight: 800; color: var(--primary); margin-top: 4px;"><?= number_format($countProperties) ?></div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Entered in system</div>
        </div>

        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 18px; box-shadow: var(--shadow-sm);">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Safety Reports</div>
            <div style="font-size: 28px; font-weight: 800; color: <?= $countReports > 0 ? '#ef4444' : '#10b981' ?>; margin-top: 4px;"><?= number_format($countReports) ?></div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Awaiting moderation</div>
        </div>

        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 18px; box-shadow: var(--shadow-sm);">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">New Feedback</div>
            <div style="font-size: 28px; font-weight: 800; color: var(--secondary); margin-top: 4px;"><?= number_format($countFeedback) ?></div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Inquiries & ratings</div>
        </div>
    </div>

    <!-- Admin Portal Navigation Tabs -->
    <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color); margin-bottom: 24px; overflow-x: auto; padding-bottom: 2px;">
        <a href="?tab=national_id" class="btn btn-sm <?= $activeTab === 'national_id' ? 'btn-primary' : 'btn-secondary' ?>" style="border-radius: 8px 8px 0 0; padding: 10px 18px; font-weight: 700; position: relative;">
            🆔 National ID Approvals
            <?php if ($countPendingId > 0): ?>
                <span style="background: #ef4444; color: #fff; font-size: 10px; padding: 1px 7px; border-radius: 9999px; margin-left: 6px; font-weight: 800;"><?= $countPendingId ?> PENDING</span>
            <?php endif; ?>
        </a>
        <a href="?tab=members" class="btn btn-sm <?= $activeTab === 'members' ? 'btn-primary' : 'btn-secondary' ?>" style="border-radius: 8px 8px 0 0; padding: 10px 18px; font-weight: 700;">
            👥 Members (<?= $countUsers ?>)
        </a>
        <a href="?tab=verifications" class="btn btn-sm <?= $activeTab === 'verifications' ? 'btn-primary' : 'btn-secondary' ?>" style="border-radius: 8px 8px 0 0; padding: 10px 18px; font-weight: 700;">
            🛡️ Brokers & Phone (<?= $countUnverified ?>)
        </a>
        <a href="?tab=resets" class="btn btn-sm <?= $activeTab === 'resets' ? 'btn-primary' : 'btn-secondary' ?>" style="border-radius: 8px 8px 0 0; padding: 10px 18px; font-weight: 700; position: relative;">
            🔑 Password Resets
            <?php if ($countResets > 0): ?>
                <span style="background: #ef4444; color: #fff; font-size: 10px; padding: 1px 6px; border-radius: 9999px; margin-left: 4px;"><?= $countResets ?></span>
            <?php endif; ?>
        </a>
        <a href="?tab=properties" class="btn btn-sm <?= $activeTab === 'properties' ? 'btn-primary' : 'btn-secondary' ?>" style="border-radius: 8px 8px 0 0; padding: 10px 18px; font-weight: 700;">
            🏠 All Listings (<?= $countProperties ?>)
        </a>
        <a href="?tab=reports" class="btn btn-sm <?= $activeTab === 'reports' ? 'btn-primary' : 'btn-secondary' ?>" style="border-radius: 8px 8px 0 0; padding: 10px 18px; font-weight: 700;">
            🚨 Safety Reports (<?= $countReports ?>)
        </a>
        <a href="?tab=feedback" class="btn btn-sm <?= $activeTab === 'feedback' ? 'btn-primary' : 'btn-secondary' ?>" style="border-radius: 8px 8px 0 0; padding: 10px 18px; font-weight: 700;">
            💬 Customer Feedback (<?= $countFeedback ?>)
        </a>
    </div>

    <!-- ===================================================================
         TAB: NATIONAL ID IDENTITY VERIFICATION APPROVALS
         =================================================================== -->
    <?php if ($activeTab === 'national_id'): ?>
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 24px; box-shadow: var(--shadow-sm);">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
                <div>
                    <h2 style="font-size: 18px; font-weight: 800; color: var(--secondary); margin: 0 0 4px 0; display: flex; align-items: center; gap: 8px;">
                        🛡️ National ID Account Verifications (Fayda / Kebele / Passport)
                        <?php if ($countPendingId > 0): ?>
                            <span style="background: #ef4444; color: #fff; font-size: 12px; padding: 2px 8px; border-radius: 9999px;"><?= $countPendingId ?> Awaiting Action</span>
                        <?php endif; ?>
                    </h2>
                    <p style="font-size: 13px; color: var(--text-muted); margin: 0;">
                        Review submitted Ethiopian National IDs and document scans. Click <strong>Accept &amp; Verify</strong> to grant verified badges across their listings, or <strong>Reject</strong> with reason.
                    </p>
                </div>
            </div>

            <!-- Filter Bar -->
            <form method="GET" action="" style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="national_id">
                <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search citizen name, email, or ID number..." class="form-input" style="max-width: 340px;">
                <select name="id_status" class="form-select" style="max-width: 190px;" onchange="this.form.submit()">
                    <option value="all" <?= $idFilterStatus === 'all' ? 'selected' : '' ?>>All Applications</option>
                    <option value="pending" <?= $idFilterStatus === 'pending' ? 'selected' : '' ?>>⏳ Pending Review (<?= $countPendingId ?>)</option>
                    <option value="verified" <?= $idFilterStatus === 'verified' ? 'selected' : '' ?>>🛡️ Verified (<?= $countVerifiedId ?>)</option>
                    <option value="rejected" <?= $idFilterStatus === 'rejected' ? 'selected' : '' ?>>❌ Rejected</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <?php if (!empty($searchQuery) || $idFilterStatus !== 'all'): ?>
                    <a href="?tab=national_id" class="btn btn-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </form>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 12px; text-transform: uppercase;">
                            <th style="padding: 12px 10px;">Citizen / Member</th>
                            <th style="padding: 12px 10px;">ID Type</th>
                            <th style="padding: 12px 10px;">ID Number</th>
                            <th style="padding: 12px 10px;">Document Scan</th>
                            <th style="padding: 12px 10px;">Submitted</th>
                            <th style="padding: 12px 10px;">Current Status</th>
                            <th style="padding: 12px 10px; text-align: right;">Moderator Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($nationalIdRequests)): ?>
                            <tr>
                                <td colspan="7" style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                                    <div style="font-size: 32px; margin-bottom: 8px;">🎉</div>
                                    <div style="font-weight: 700; font-size: 16px; color: var(--secondary);">No verification submissions found</div>
                                    <div style="font-size: 13px; margin-top: 4px;">Users who insert their National ID on the verification page will appear here for review.</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($nationalIdRequests as $req): ?>
                                <?php
                                    $typeMap = [
                                        'fayda_national_id' => '🇪🇹 Fayda National ID',
                                        'kebele_id'         => '🪪 Kebele Resident ID',
                                        'passport'          => '🛂 Ethiopian Passport',
                                        'driver_license'    => "🚗 Driver's License"
                                    ];
                                    $typeName = $typeMap[$req['national_id_type']] ?? 'Official ID';
                                    $docUrl = !empty($req['national_id_document']) ? (str_starts_with($req['national_id_document'], 'http') ? $req['national_id_document'] : BASE_URL . '/' . ltrim($req['national_id_document'], '/')) : '';
                                    $isPdf = $docUrl && str_ends_with(strtolower($docUrl), '.pdf');
                                ?>
                                <tr style="border-bottom: 1px solid var(--border-color); <?= $req['national_id_status'] === 'pending' ? 'background: rgba(245, 158, 11, 0.04);' : '' ?>">
                                    <td style="padding: 14px 10px;">
                                        <div style="font-weight: 700; color: var(--secondary); font-size: 15px;"><?= htmlspecialchars($req['full_name']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                            <a href="mailto:<?= htmlspecialchars($req['email']) ?>" style="color: var(--primary);"><?= htmlspecialchars($req['email']) ?></a>
                                            <?php if (!empty($req['phone'])): ?>
                                                &bull; <?= htmlspecialchars($req['phone']) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div style="margin-top: 4px;">
                                            <span class="badge badge-<?= $req['role'] === 'broker' ? 'broker' : 'sale' ?>" style="font-size: 11px;">
                                                <?= ucfirst($req['role']) ?>
                                            </span>
                                            <?php if (!empty($req['broker_agency'])): ?>
                                                <span style="font-size: 11px; color: var(--text-muted);">🏢 <?= htmlspecialchars($req['broker_agency']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <div style="font-weight: 600; color: var(--secondary); font-size: 13px;"><?= $typeName ?></div>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($req['national_id_type'] ?: 'fayda_national_id') ?></div>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <code style="background: var(--bg-main); padding: 4px 8px; border-radius: 6px; border: 1px solid var(--border-color); font-weight: 700; font-size: 13px; color: var(--secondary); letter-spacing: 0.04em;">
                                            <?= htmlspecialchars($req['national_id_number'] ?: 'Not Entered') ?>
                                        </code>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <?php if ($docUrl): ?>
                                            <?php if ($isPdf): ?>
                                                <a href="<?= htmlspecialchars($docUrl) ?>" target="_blank" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; padding: 5px 10px;">
                                                    📄 View PDF Scan
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                                </a>
                                            <?php else: ?>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <a href="<?= htmlspecialchars($docUrl) ?>" target="_blank" title="Click to view full size image">
                                                        <img src="<?= htmlspecialchars($docUrl) ?>" alt="ID Document Scan" style="width: 54px; height: 38px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                                    </a>
                                                    <a href="<?= htmlspecialchars($docUrl) ?>" target="_blank" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px;">
                                                        🔎 Enlarge
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: var(--text-muted); font-style: italic;">No file attached</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 10px; font-size: 12px; color: var(--text-muted);">
                                        <?= !empty($req['national_id_submitted_at']) ? date('M j, Y H:i', strtotime($req['national_id_submitted_at'])) : '—' ?>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <?php if ($req['national_id_status'] === 'verified'): ?>
                                            <span class="badge" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                Verified
                                            </span>
                                            <?php if (!empty($req['national_id_verified_at'])): ?>
                                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                                    <?= date('M j, Y', strtotime($req['national_id_verified_at'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($req['national_id_status'] === 'pending'): ?>
                                            <span class="badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-weight: 700;">
                                                ⏳ Pending Review
                                            </span>
                                        <?php elseif ($req['national_id_status'] === 'rejected'): ?>
                                            <span class="badge" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; font-weight: 700;">
                                                ❌ Rejected
                                            </span>
                                            <?php if (!empty($req['national_id_rejection_reason'])): ?>
                                                <div style="font-size: 11px; color: #b91c1c; margin-top: 3px; max-width: 220px; word-break: break-word;">
                                                    "<?= htmlspecialchars($req['national_id_rejection_reason']) ?>"
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: var(--text-muted);">Unverified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 10px; text-align: right;">
                                        <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
                                            
                                            <!-- Accept & Verify Form -->
                                            <?php if ($req['national_id_status'] !== 'verified'): ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <?= getCsrfInput() ?>
                                                    <input type="hidden" name="action" value="accept_national_id">
                                                    <input type="hidden" name="user_id" value="<?= $req['id'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" style="background: #059669; border-color: #059669; font-weight: 700;" title="Accept National ID and mark account Verified">
                                                        ✅ Accept &amp; Verify
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- Reject Button (Modal Trigger) -->
                                            <?php if ($req['national_id_status'] !== 'rejected'): ?>
                                                <button type="button" class="btn btn-outline btn-sm" style="color: #ef4444; border-color: #fca5a5; font-weight: 600;" onclick="openRejectIdModal(<?= $req['id'] ?>, '<?= htmlspecialchars(addslashes($req['full_name'])) ?>', '<?= htmlspecialchars(addslashes($req['national_id_number'] ?? '')) ?>')" title="Reject document and specify reason">
                                                    ❌ Reject
                                                </button>
                                            <?php endif; ?>

                                            <!-- Revoke / Reset to Unverified -->
                                            <?php if ($req['national_id_status'] === 'verified'): ?>
                                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Revoke verification for this user?');">
                                                    <?= getCsrfInput() ?>
                                                    <input type="hidden" name="action" value="reset_national_id">
                                                    <input type="hidden" name="user_id" value="<?= $req['id'] ?>">
                                                    <button type="submit" class="btn btn-secondary btn-sm" style="color: #64748b; font-size: 11px;">
                                                        Reset
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===================================================================
         TAB 1: MEMBERS / USERS MANAGEMENT
         =================================================================== -->
    <?php if ($activeTab === 'members'): ?>
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 24px; box-shadow: var(--shadow-sm);">
            
            <!-- Filter Bar -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
                <form method="GET" action="" style="display: flex; gap: 12px; flex-wrap: wrap; flex: 1;">
                    <input type="hidden" name="tab" value="members">
                    <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search by name, email, or phone..." class="form-input" style="max-width: 320px;">
                    <select name="role" class="form-select" style="max-width: 150px;" onchange="this.form.submit()">
                        <option value="all" <?= $filterRole === 'all' ? 'selected' : '' ?>>All Roles</option>
                        <option value="user" <?= $filterRole === 'user' ? 'selected' : '' ?>>Users / Buyers</option>
                        <option value="broker" <?= $filterRole === 'broker' ? 'selected' : '' ?>>Brokers</option>
                        <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admins</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <?php if (!empty($searchQuery) || $filterRole !== 'all'): ?>
                        <a href="?tab=members" class="btn btn-secondary btn-sm">Reset</a>
                    <?php endif; ?>
                </form>

                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addMemberModal')" style="font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                        ➕ Add / Create Account
                    </button>
                    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                        📝 Open Sign-Up Form
                    </a>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 12px; text-transform: uppercase;">
                            <th style="padding: 12px 10px;">ID</th>
                            <th style="padding: 12px 10px;">Member</th>
                            <th style="padding: 12px 10px;">Contact</th>
                            <th style="padding: 12px 10px;">Role</th>
                            <th style="padding: 12px 10px;">Verified</th>
                            <th style="padding: 12px 10px;">Joined</th>
                            <th style="padding: 12px 10px; text-align: right;">Admin Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($members)): ?>
                            <tr>
                                <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                    No members found matching your filter criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($members as $m): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 14px 10px; font-weight: 700; color: var(--text-muted);">#<?= $m['id'] ?></td>
                                    <td style="padding: 14px 10px;">
                                        <div style="font-weight: 700; color: var(--secondary);"><?= htmlspecialchars($m['full_name']) ?></div>
                                        <?php if (!empty($m['broker_agency'])): ?>
                                            <div style="font-size: 11px; color: var(--primary);">🏢 <?= htmlspecialchars($m['broker_agency']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <div><a href="mailto:<?= htmlspecialchars($m['email']) ?>" style="color: var(--primary);"><?= htmlspecialchars($m['email']) ?></a></div>
                                        <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($m['phone'] ?? 'No phone') ?></div>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <span class="badge badge-<?= $m['role'] === 'admin' ? 'sold' : ($m['role'] === 'broker' ? 'broker' : 'sale') ?>">
                                            <?= ucfirst($m['role']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <?php if (($m['national_id_status'] ?? '') === 'verified'): ?>
                                            <span style="color: #047857; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                                🛡️ ID Verified
                                            </span>
                                        <?php elseif (($m['national_id_status'] ?? '') === 'pending'): ?>
                                            <a href="?tab=national_id" style="color: #b45309; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; text-decoration: none;" title="National ID awaiting admin review">
                                                ⏳ ID Pending
                                            </a>
                                        <?php elseif (($m['national_id_status'] ?? '') === 'rejected'): ?>
                                            <span style="color: #b91c1c; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;" title="ID rejected">
                                                ❌ ID Rejected
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 11px;">No National ID</span>
                                        <?php endif; ?>
                                        <div style="font-size: 11px; margin-top: 4px; color: <?= $m['phone_verified'] ? '#10b981' : '#94a3b8' ?>;">
                                            📱 <?= $m['phone_verified'] ? 'Phone Verified' : 'Phone Unverified' ?>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 10px; font-size: 12px; color: var(--text-muted);">
                                        <?= date('M j, Y', strtotime($m['created_at'])) ?>
                                    </td>
                                    <td style="padding: 14px 10px; text-align: right;">
                                        <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                            
                                            <!-- Verification Toggle Form -->
                                            <form method="POST" action="" style="display: inline;">
                                                <?= getCsrfInput() ?>
                                                <input type="hidden" name="action" value="toggle_verification">
                                                <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                                                <input type="hidden" name="new_status" value="<?= $m['phone_verified'] ? 0 : 1 ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm" title="<?= $m['phone_verified'] ? 'Revoke Verification' : 'Approve & Verify' ?>">
                                                    <?= $m['phone_verified'] ? 'Revoke' : 'Verify' ?>
                                                </button>
                                            </form>

                                            <!-- Direct Password Reset Button (Modal Trigger) -->
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="openResetModal(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['email'])) ?>')" title="Set Password Directly">
                                                🔑 Reset Pass
                                            </button>

                                            <!-- Role Dropdown Form -->
                                            <form method="POST" action="" style="display: inline;">
                                                <?= getCsrfInput() ?>
                                                <input type="hidden" name="action" value="change_role">
                                                <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                                                <select name="role" onchange="if(confirm('Change role for <?= htmlspecialchars(addslashes($m['full_name'])) ?>?')) this.form.submit(); else this.value='<?= $m['role'] ?>';" class="form-select" style="padding: 4px 8px; font-size: 12px; width: auto; height: 32px;">
                                                    <option value="user" <?= $m['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                                    <option value="broker" <?= $m['role'] === 'broker' ? 'selected' : '' ?>>Broker</option>
                                                    <option value="admin" <?= $m['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                                </select>
                                            </form>

                                            <!-- Delete User Form -->
                                            <?php if ($m['id'] !== (int)$admin['id']): ?>
                                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('WARNING: Are you sure you want to permanently delete member <?= htmlspecialchars(addslashes($m['full_name'])) ?> and all their listings?');">
                                                    <?= getCsrfInput() ?>
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                                                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger); padding: 4px 8px;" title="Delete User">
                                                        🗑️
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===================================================================
         TAB 2: VERIFICATIONS & BROKER APPROVAL
         =================================================================== -->
    <?php if ($activeTab === 'verifications'): ?>
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 24px; box-shadow: var(--shadow-sm);">
            <div style="margin-bottom: 20px;">
                <h2 style="font-size: 18px; font-weight: 800; color: var(--secondary); margin-bottom: 4px;">Identity & Broker Licensing Verifications</h2>
                <p style="font-size: 13px; color: var(--text-muted); margin: 0;">Review pending phone validations and official broker certifications for safety approval.</p>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 12px; text-transform: uppercase;">
                            <th style="padding: 12px 10px;">ID</th>
                            <th style="padding: 12px 10px;">Applicant</th>
                            <th style="padding: 12px 10px;">Account Type</th>
                            <th style="padding: 12px 10px;">Broker Agency & License</th>
                            <th style="padding: 12px 10px;">Phone Status</th>
                            <th style="padding: 12px 10px; text-align: right;">Verification Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($verifications)): ?>
                            <tr>
                                <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                    🎉 All active users and brokers are currently verified!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($verifications as $v): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 14px 10px; font-weight: 700;">#<?= $v['id'] ?></td>
                                    <td style="padding: 14px 10px;">
                                        <div style="font-weight: 700; color: var(--secondary);"><?= htmlspecialchars($v['full_name']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($v['email']) ?> | <?= htmlspecialchars($v['phone'] ?? 'No phone') ?></div>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <span class="badge badge-<?= $v['role'] === 'broker' ? 'broker' : 'sale' ?>">
                                            <?= ucfirst($v['role']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <?php if ($v['role'] === 'broker'): ?>
                                            <div style="font-weight: 700; color: var(--primary);"><?= htmlspecialchars($v['broker_agency'] ?: 'Independent Broker') ?></div>
                                            <div style="font-size: 12px; color: var(--text-muted);">License: <code><?= htmlspecialchars($v['broker_license_no'] ?: 'Not Provided') ?></code></div>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 12px;">Standard Client Account</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <?php if ($v['phone_verified']): ?>
                                            <span style="color: #10b981; font-weight: 700; font-size: 13px;">✅ Verified</span>
                                        <?php else: ?>
                                            <span style="color: #ef4444; font-weight: 700; font-size: 13px;">⚠️ Unverified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 10px; text-align: right;">
                                        <form method="POST" action="" style="display: inline;">
                                            <?= getCsrfInput() ?>
                                            <input type="hidden" name="action" value="toggle_verification">
                                            <input type="hidden" name="user_id" value="<?= $v['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= $v['phone_verified'] ? 0 : 1 ?>">
                                            <?php if ($v['phone_verified']): ?>
                                                <button type="submit" class="btn btn-secondary btn-sm" style="color: #ef4444;">
                                                    Revoke Verification
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    ✅ Approve & Verify
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===================================================================
         TAB 3: PASSWORD RESET REQUESTS ("ACCEPT PASSWORD RESET ACCEPT")
         =================================================================== -->
    <?php if ($activeTab === 'resets'): ?>
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 24px; box-shadow: var(--shadow-sm);">
            <div style="margin-bottom: 20px;">
                <h2 style="font-size: 18px; font-weight: 800; color: var(--secondary); margin-bottom: 4px;">Password Reset Approval Queue</h2>
                <p style="font-size: 13px; color: var(--text-muted); margin: 0;">Users requesting password resets through the site. You can accept the reset request, assign a new password, or dismiss the ticket.</p>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 12px; text-transform: uppercase;">
                            <th style="padding: 12px 10px;">ID</th>
                            <th style="padding: 12px 10px;">Account Email</th>
                            <th style="padding: 12px 10px;">Member Info</th>
                            <th style="padding: 12px 10px;">OTP / Token</th>
                            <th style="padding: 12px 10px;">Expires</th>
                            <th style="padding: 12px 10px; text-align: right;">Admin Acceptance Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($resets)): ?>
                            <tr>
                                <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                    🎉 No pending password reset requests at this time.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($resets as $r): 
                                $isExpired = strtotime($r['expires_at']) < time();
                            ?>
                                <tr style="border-bottom: 1px solid var(--border-color); background: <?= $isExpired ? '#fef2f2' : 'transparent' ?>;">
                                    <td style="padding: 14px 10px; font-weight: 700;">#<?= $r['id'] ?></td>
                                    <td style="padding: 14px 10px;">
                                        <div style="font-weight: 700; color: var(--primary);"><?= htmlspecialchars($r['email']) ?></div>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <div style="font-weight: 600;"><?= htmlspecialchars($r['full_name'] ?: 'Unknown User') ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($r['phone'] ?: '') ?></div>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <div style="font-size: 13px;">OTP Code: <strong style="color: #2563eb; background: rgba(37,99,235,0.1); padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($r['otp_code'] ?: 'None') ?></strong></div>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px; font-family: monospace;">Token: <?= substr($r['token'], 0, 16) ?>...</div>
                                    </td>
                                    <td style="padding: 14px 10px; font-size: 13px;">
                                        <?php if ($isExpired): ?>
                                            <span style="color: #ef4444; font-weight: 700;">Expired</span>
                                        <?php else: ?>
                                            <span style="color: #10b981; font-weight: 700;">Active</span> (<?= date('M j, g:i a', strtotime($r['expires_at'])) ?>)
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 10px; text-align: right;">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <!-- Quick Accept & Reset to Password123! -->
                                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Accept reset and set password to Password123! for <?= htmlspecialchars(addslashes($r['email'])) ?>?');">
                                                <?= getCsrfInput() ?>
                                                <input type="hidden" name="action" value="approve_password_reset">
                                                <input type="hidden" name="reset_id" value="<?= $r['id'] ?>">
                                                <input type="hidden" name="email" value="<?= htmlspecialchars($r['email']) ?>">
                                                <input type="hidden" name="new_password" value="Password123!">
                                                <button type="submit" class="btn btn-primary btn-sm" title="Accept & set password to Password123!">
                                                    ✅ Accept & Reset
                                                </button>
                                            </form>

                                            <!-- Custom Password Modal Button -->
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="openAcceptResetModal(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['email'])) ?>')">
                                                Custom Pass...
                                            </button>

                                            <!-- Dismiss Button -->
                                            <form method="POST" action="" style="display: inline;">
                                                <?= getCsrfInput() ?>
                                                <input type="hidden" name="action" value="dismiss_password_reset">
                                                <input type="hidden" name="reset_id" value="<?= $r['id'] ?>">
                                                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--text-muted);" title="Dismiss request">
                                                    ✕
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===================================================================
         TAB 4: ALL PROPERTIES / LISTINGS ENTERED ON WEBSITE
         =================================================================== -->
    <?php if ($activeTab === 'properties'): ?>
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 24px; box-shadow: var(--shadow-sm);">
            
            <!-- Properties Filter Bar -->
            <form method="GET" action="" style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
                <input type="hidden" name="tab" value="properties">
                <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search title, location, or owner..." class="form-input" style="max-width: 320px;">
                <select name="status" class="form-select" style="max-width: 170px;" onchange="this.form.submit()">
                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Statuses</option>
                    <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="pending_review" <?= $filterStatus === 'pending_review' ? 'selected' : '' ?>>Pending Review</option>
                    <option value="sold" <?= $filterStatus === 'sold' ? 'selected' : '' ?>>Sold</option>
                    <option value="rented" <?= $filterStatus === 'rented' ? 'selected' : '' ?>>Rented</option>
                    <option value="flagged" <?= $filterStatus === 'flagged' ? 'selected' : '' ?>>Flagged / Hidden</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <?php if (!empty($searchQuery) || $filterStatus !== 'all'): ?>
                    <a href="?tab=properties" class="btn btn-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </form>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 12px; text-transform: uppercase;">
                            <th style="padding: 12px 10px;">ID</th>
                            <th style="padding: 12px 10px;">Property</th>
                            <th style="padding: 12px 10px;">Price</th>
                            <th style="padding: 12px 10px;">Submitted By</th>
                            <th style="padding: 12px 10px;">Status</th>
                            <th style="padding: 12px 10px;">Featured</th>
                            <th style="padding: 12px 10px; text-align: right;">Admin Moderation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($properties)): ?>
                            <tr>
                                <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                    No property listings found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($properties as $p): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 14px 10px; font-weight: 700; color: var(--text-muted);">#<?= $p['id'] ?></td>
                                    <td style="padding: 14px 10px;">
                                        <div style="display: flex; gap: 12px; align-items: center;">
                                            <?php if (!empty($p['primary_image'])): ?>
                                                <img src="<?= htmlspecialchars($p['primary_image']) ?>" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-color);">
                                            <?php else: ?>
                                                <div style="width: 50px; height: 50px; border-radius: 8px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 20px;">🏡</div>
                                            <?php endif; ?>
                                            <div>
                                                <a href="<?= BASE_URL ?>/properties/details.php?id=<?= $p['id'] ?>" target="_blank" style="font-weight: 700; color: var(--secondary); text-decoration: none;">
                                                    <?= htmlspecialchars($p['title']) ?>
                                                </a>
                                                <div style="font-size: 12px; color: var(--text-muted);">
                                                    📍 <?= htmlspecialchars($p['city']) ?> &bull; <?= ucfirst($p['property_type']) ?> &bull; <?= ucfirst($p['purpose']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <div style="font-weight: 800; color: var(--primary);">
                                            <?= CURRENCY_SYMBOL . number_format($p['price']) ?>
                                        </div>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?= ucfirst($p['price_frequency']) ?></div>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <div style="font-weight: 600;"><?= htmlspecialchars($p['contact_name'] ?: $p['owner_name']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($p['contact_phone'] ?: $p['owner_phone']) ?></div>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <form method="POST" action="" style="display: inline;">
                                            <?= getCsrfInput() ?>
                                            <input type="hidden" name="action" value="update_property_status">
                                            <input type="hidden" name="property_id" value="<?= $p['id'] ?>">
                                            <select name="status" onchange="this.form.submit()" class="form-select" style="padding: 4px 8px; font-size: 12px; height: 30px; width: auto; font-weight: 700;">
                                                <option value="active" <?= $p['status'] === 'active' ? 'selected' : '' ?>>Active ✅</option>
                                                <option value="pending_review" <?= $p['status'] === 'pending_review' ? 'selected' : '' ?>>Pending ⏳</option>
                                                <option value="sold" <?= $p['status'] === 'sold' ? 'selected' : '' ?>>Sold 🤝</option>
                                                <option value="rented" <?= $p['status'] === 'rented' ? 'selected' : '' ?>>Rented 🔑</option>
                                                <option value="flagged" <?= $p['status'] === 'flagged' ? 'selected' : '' ?>>Flagged ⚠️</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <form method="POST" action="" style="display: inline;">
                                            <?= getCsrfInput() ?>
                                            <input type="hidden" name="action" value="toggle_property_featured">
                                            <input type="hidden" name="property_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="featured" value="<?= $p['featured'] ? 0 : 1 ?>">
                                            <button type="submit" class="btn btn-sm" style="background: none; border: none; cursor: pointer; font-size: 18px;" title="<?= $p['featured'] ? 'Unmark Featured' : 'Mark as Featured' ?>">
                                                <?= $p['featured'] ? '⭐' : '☆' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="padding: 14px 10px; text-align: right;">
                                        <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                            <a href="<?= BASE_URL ?>/properties/edit.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="Edit Listing">
                                                Edit
                                            </a>
                                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Permanently delete property #<?= $p['id'] ?>?');">
                                                <?= getCsrfInput() ?>
                                                <input type="hidden" name="action" value="delete_property">
                                                <input type="hidden" name="property_id" value="<?= $p['id'] ?>">
                                                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger); padding: 4px 8px;" title="Delete">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===================================================================
         TAB 5: SAFETY REPORTS & MODERATION
         =================================================================== -->
    <?php if ($activeTab === 'reports'): ?>
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 24px; box-shadow: var(--shadow-sm);">
            <div style="margin-bottom: 20px;">
                <h2 style="font-size: 18px; font-weight: 800; color: var(--secondary); margin-bottom: 4px;">User Safety & Fraud Reports</h2>
                <p style="font-size: 13px; color: var(--text-muted); margin: 0;">Reports submitted by visitors regarding fraudulent pricing, duplicate posts, or misleading broker fees.</p>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 12px; text-transform: uppercase;">
                            <th style="padding: 12px 10px;">ID</th>
                            <th style="padding: 12px 10px;">Reported Property</th>
                            <th style="padding: 12px 10px;">Reason</th>
                            <th style="padding: 12px 10px;">Details & Notes</th>
                            <th style="padding: 12px 10px;">Status</th>
                            <th style="padding: 12px 10px; text-align: right;">Moderator Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                    🎉 No pending safety reports to review!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reports as $rep): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 14px 10px; font-weight: 700;">#<?= $rep['id'] ?></td>
                                    <td style="padding: 14px 10px;">
                                        <?php if (!empty($rep['property_title'])): ?>
                                            <a href="<?= BASE_URL ?>/properties/details.php?id=<?= $rep['property_id'] ?>" target="_blank" style="font-weight: 700; color: var(--secondary);">
                                                <?= htmlspecialchars($rep['property_title']) ?>
                                            </a>
                                            <div style="font-size: 12px; color: var(--text-muted);">Property #<?= $rep['property_id'] ?> (<?= htmlspecialchars($rep['property_city'] ?? '') ?>)</div>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-style: italic;">Deleted Property #<?= $rep['property_id'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <span class="badge" style="background: #fee2e2; color: #b91c1c;">
                                            <?= ucfirst(str_replace('_', ' ', $rep['reason'])) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 10px; max-width: 280px;">
                                        <div style="font-size: 13px;"><?= htmlspecialchars($rep['details'] ?: 'No additional notes') ?></div>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Reporter: <?= htmlspecialchars($rep['reporter_email'] ?: ($rep['reporter_name'] ?: 'Anonymous')) ?></div>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <span class="badge badge-<?= $rep['status'] === 'pending' ? 'rent' : 'verified' ?>">
                                            <?= ucfirst(str_replace('_', ' ', $rep['status'])) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 10px; text-align: right;">
                                        <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                            <form method="POST" action="" style="display: inline;">
                                                <?= getCsrfInput() ?>
                                                <input type="hidden" name="action" value="handle_report">
                                                <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">
                                                <input type="hidden" name="decision" value="dismiss">
                                                <button type="submit" class="btn btn-secondary btn-sm" title="Dismiss Report">Dismiss</button>
                                            </form>

                                            <?php if ($rep['property_id']): ?>
                                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Flag listing #<?= $rep['property_id'] ?> as suspicious?');">
                                                    <?= getCsrfInput() ?>
                                                    <input type="hidden" name="action" value="handle_report">
                                                    <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">
                                                    <input type="hidden" name="property_id" value="<?= $rep['property_id'] ?>">
                                                    <input type="hidden" name="decision" value="flag_listing">
                                                    <button type="submit" class="btn btn-primary btn-sm" style="background: #f59e0b;" title="Flag Property">Flag</button>
                                                </form>

                                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Permanently delete fraudulent listing #<?= $rep['property_id'] ?>?');">
                                                    <?= getCsrfInput() ?>
                                                    <input type="hidden" name="action" value="handle_report">
                                                    <input type="hidden" name="report_id" value="<?= $rep['id'] ?>">
                                                    <input type="hidden" name="property_id" value="<?= $rep['property_id'] ?>">
                                                    <input type="hidden" name="decision" value="delete_listing">
                                                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger);" title="Delete Property">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===================================================================
         TAB 6: USER FEEDBACK & SUGGESTIONS
         =================================================================== -->
    <?php if ($activeTab === 'feedback'): ?>
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 24px; box-shadow: var(--shadow-sm);">
            <div style="margin-bottom: 20px;">
                <h2 style="font-size: 18px; font-weight: 800; color: var(--secondary); margin-bottom: 4px;">User Inquiries & Suggestions</h2>
                <p style="font-size: 13px; color: var(--text-muted); margin: 0;">Feedback submitted via settings, search experience, or broker review forms.</p>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted); font-size: 12px; text-transform: uppercase;">
                            <th style="padding: 12px 10px;">ID</th>
                            <th style="padding: 12px 10px;">Customer</th>
                            <th style="padding: 12px 10px;">Category</th>
                            <th style="padding: 12px 10px;">Rating</th>
                            <th style="padding: 12px 10px;">Message</th>
                            <th style="padding: 12px 10px;">Status</th>
                            <th style="padding: 12px 10px; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($feedbacks)): ?>
                            <tr>
                                <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                    No feedback received yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($feedbacks as $fb): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 14px 10px; font-weight: 700;">#<?= $fb['id'] ?></td>
                                    <td style="padding: 14px 10px;">
                                        <div style="font-weight: 700; color: var(--secondary);"><?= htmlspecialchars($fb['name']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($fb['email']) ?></div>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <span class="badge badge-sale">
                                            <?= ucfirst(str_replace('_', ' ', $fb['category'])) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 10px; font-size: 14px; color: #f59e0b;">
                                        <?= str_repeat('⭐', max(1, min(5, (int)$fb['rating']))) ?>
                                    </td>
                                    <td style="padding: 14px 10px; max-width: 320px;">
                                        <div style="font-size: 13px;"><?= nl2br(htmlspecialchars($fb['message'])) ?></div>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;"><?= date('M j, Y g:i a', strtotime($fb['created_at'])) ?></div>
                                    </td>
                                    <td style="padding: 14px 10px;">
                                        <span class="badge badge-<?= $fb['status'] === 'new' ? 'rent' : 'verified' ?>">
                                            <?= ucfirst($fb['status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 14px 10px; text-align: right;">
                                        <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                            <?php if ($fb['status'] !== 'resolved'): ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <?= getCsrfInput() ?>
                                                    <input type="hidden" name="action" value="handle_feedback">
                                                    <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                                                    <input type="hidden" name="status" value="resolved">
                                                    <button type="submit" class="btn btn-secondary btn-sm">Mark Resolved</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <?= getCsrfInput() ?>
                                                <input type="hidden" name="action" value="handle_feedback">
                                                <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                                                <input type="hidden" name="status" value="delete">
                                                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); border-color: var(--danger);">🗑️</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- ===================================================================
     MODAL 1: ADD NEW MEMBER DIRECTLY BY ADMIN
     =================================================================== -->
<div class="modal-overlay" id="addMemberModal">
    <div class="modal-card" style="max-width: 520px;">
        <div class="modal-header">
            <div class="modal-title">➕ Add New Member to Platform</div>
            <button type="button" class="modal-close" onclick="closeAdminModal('addMemberModal')">&times;</button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <form method="POST" action="">
                <?= getCsrfInput() ?>
                <input type="hidden" name="action" value="add_user">

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-input" placeholder="e.g. Abebe Bikila" required>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="e.g. user@example.com" required>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Phone Number (Ethiopia)</label>
                    <input type="text" name="phone" class="form-input" placeholder="+251 91 234 5678">
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Default Password</label>
                    <input type="text" name="password" class="form-input" value="Password123!" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Account Role</label>
                        <select name="role" id="newMemberRole" class="form-select" onchange="toggleBrokerAgencyField(this.value)">
                            <option value="user">User / Buyer / Seller</option>
                            <option value="broker">Verified Broker</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div class="form-group" style="display: flex; flex-direction: column; justify-content: flex-end;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; cursor: pointer; margin-bottom: 10px;">
                            <input type="checkbox" name="phone_verified" value="1" checked style="width: 18px; height: 18px;">
                            Pre-Verify Phone
                        </label>
                    </div>
                </div>

                <div class="form-group" id="agencyFieldGroup" style="margin-bottom: 20px; display: none;">
                    <label class="form-label">Broker Agency Name</label>
                    <input type="text" name="broker_agency" class="form-input" placeholder="e.g. Addis Premier Realty">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="width: 100%;">Create Member</button>
            </form>
        </div>
    </div>
</div>

<!-- ===================================================================
     MODAL 2: DIRECT PASSWORD RESET FOR MEMBER
     =================================================================== -->
<div class="modal-overlay" id="directResetModal">
    <div class="modal-card" style="max-width: 460px;">
        <div class="modal-header">
            <div class="modal-title">🔑 Direct Password Reset</div>
            <button type="button" class="modal-close" onclick="closeAdminModal('directResetModal')">&times;</button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                Directly set a new password for member: <strong id="resetTargetEmail" style="color: var(--primary);"></strong>
            </p>
            <form method="POST" action="">
                <?= getCsrfInput() ?>
                <input type="hidden" name="action" value="admin_set_password">
                <input type="hidden" name="user_id" id="resetUserIdField" value="0">

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">New Password</label>
                    <div style="position: relative;">
                        <input type="text" name="new_password" id="adminNewPassInput" class="form-input" value="Password123!" required style="padding-right: 70px;">
                        <button type="button" onclick="document.getElementById('adminNewPassInput').value='Password123!'" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-size: 11px; background: #e2e8f0; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
                            Default
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="width: 100%;">Save New Password</button>
            </form>
        </div>
    </div>
</div>

<!-- ===================================================================
     MODAL 3: ACCEPT & APPROVE PASSWORD RESET REQUEST
     =================================================================== -->
<div class="modal-overlay" id="acceptResetModal">
    <div class="modal-card" style="max-width: 460px;">
        <div class="modal-header">
            <div class="modal-title">✅ Accept Password Reset Request</div>
            <button type="button" class="modal-close" onclick="closeAdminModal('acceptResetModal')">&times;</button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                Accept password reset request for: <strong id="acceptTargetEmail" style="color: var(--primary);"></strong>
            </p>
            <form method="POST" action="">
                <?= getCsrfInput() ?>
                <input type="hidden" name="action" value="approve_password_reset">
                <input type="hidden" name="reset_id" id="acceptResetIdField" value="0">
                <input type="hidden" name="email" id="acceptEmailField" value="">

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Assign Password</label>
                    <input type="text" name="new_password" class="form-input" value="Password123!" required>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">The user can immediately log in with this password once approved.</div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="width: 100%;">Approve & Save Password</button>
            </form>
        </div>
    </div>
</div>

<!-- ===================================================================
     MODAL 4: REJECT NATIONAL ID VERIFICATION
     =================================================================== -->
<div class="modal-overlay" id="rejectIdModal">
    <div class="modal-card" style="max-width: 500px;">
        <div class="modal-header">
            <div class="modal-title" style="color: #ef4444; display: flex; align-items: center; gap: 8px;">
                ❌ Reject National ID Verification
            </div>
            <button type="button" class="modal-close" onclick="closeAdminModal('rejectIdModal')">&times;</button>
        </div>
        <div class="modal-body" style="padding: 24px;">
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
                You are rejecting the ID verification for <strong id="rejectIdTargetName" style="color: var(--secondary);"></strong> (<code id="rejectIdTargetNumber"></code>).
            </p>
            <form method="POST" action="">
                <?= getCsrfInput() ?>
                <input type="hidden" name="action" value="reject_national_id">
                <input type="hidden" name="user_id" id="rejectIdUserIdField" value="0">

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Predefined Reason</label>
                    <select class="form-select" onchange="document.getElementById('rejectReasonInput').value = this.value">
                        <option value="Uploaded ID document scan is blurry, dark, or illegible.">Document is blurry / illegible</option>
                        <option value="The registered account name does not match the legal name on the National ID.">Name mismatch with ID</option>
                        <option value="The National ID document has expired. Please submit a valid unexpired ID.">Expired National ID</option>
                        <option value="Invalid Fayda FAN/FIN or Kebele registration number entered.">Invalid ID number</option>
                        <option value="Document corners are cropped or incomplete. Please upload full front/back scan.">Cropped / Incomplete photo</option>
                        <option value="">-- Custom Reason Below --</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Reason Explanation (Visible to Member)</label>
                    <textarea name="rejection_reason" id="rejectReasonInput" class="form-textarea" rows="3" required style="width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; font-family: inherit; font-size: 13px;">Uploaded ID document scan is blurry, dark, or illegible.</textarea>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">This message will be shown to the user on their dashboard so they can rectify and resubmit.</div>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeAdminModal('rejectIdModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444;">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.add('active');
}

function closeAdminModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.classList.remove('active');
}

function openResetModal(userId, email) {
    document.getElementById('resetUserIdField').value = userId;
    document.getElementById('resetTargetEmail').textContent = email;
    openModal('directResetModal');
}

function openAcceptResetModal(resetId, email) {
    document.getElementById('acceptResetIdField').value = resetId;
    document.getElementById('acceptEmailField').value = email;
    document.getElementById('acceptTargetEmail').textContent = email;
    openModal('acceptResetModal');
}

function openRejectIdModal(userId, fullName, idNumber) {
    document.getElementById('rejectIdUserIdField').value = userId;
    document.getElementById('rejectIdTargetName').textContent = fullName;
    document.getElementById('rejectIdTargetNumber').textContent = idNumber || 'No ID Number';
    openModal('rejectIdModal');
}

function toggleBrokerAgencyField(role) {
    const group = document.getElementById('agencyFieldGroup');
    if (group) {
        group.style.display = (role === 'broker') ? 'block' : 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
