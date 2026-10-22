<?php
/**
 * EstateCraft Ethiopia - National ID Account Verification
 * Allows users and brokers to submit their official National ID (Fayda / Kebele / Passport)
 * for administrative review and verified account badge.
 */

$pageTitle = "National ID Verification";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$user = requireAuth();
$pdo = getDB();

// Refresh current user info from database to get latest verification status
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $user['id']]);
$user = $stmt->fetch() ?: $user;

$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie();

    $legalName = trim($_POST['legal_name'] ?? '');
    $idType = trim($_POST['id_type'] ?? 'fayda_national_id');
    $idNumber = trim($_POST['id_number'] ?? '');

    // Allowed ID types
    $validTypes = ['fayda_national_id', 'kebele_id', 'passport', 'driver_license'];
    if (!in_array($idType, $validTypes, true)) {
        $idType = 'fayda_national_id';
    }

    if (empty($idNumber)) {
        $error = "Please provide your National ID or Document Registration number.";
    } elseif (strlen($idNumber) < 4) {
        $error = "National ID number must be at least 4 characters long.";
    }

    // Handle Document Upload
    $documentPath = $user['national_id_document'] ?? '';
    if (empty($error) && isset($_FILES['id_document']) && $_FILES['id_document']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['id_document'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = "File upload failed with error code: " . $file['error'];
        } else {
            // File size validation (Max 8MB)
            if ($file['size'] > 8 * 1024 * 1024) {
                $error = "Document file is too large. Maximum allowed size is 8MB.";
            }

            // Extension & MIME verification
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($fileExt, $allowedExts, true)) {
                $error = "Invalid file type. Please upload a JPG, PNG, WEBP, or PDF image of your ID.";
            }

            if (empty($error)) {
                $uploadDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'verifications' . DIRECTORY_SEPARATOR;
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = 'id_' . $user['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                $targetFile = $uploadDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $documentPath = 'uploads/verifications/' . $fileName;
                } else {
                    $error = "Could not save uploaded document. Please check server permissions.";
                }
            }
        }
    } elseif (empty($documentPath) && empty($error)) {
        $error = "Please upload a clear scan or photo of your official National ID.";
    }

    // Update Database if validation passed
    if (empty($error)) {
        $stmtUpdate = $pdo->prepare("
            UPDATE users 
            SET national_id_number = :id_num,
                national_id_type = :id_type,
                national_id_document = :id_doc,
                national_id_status = 'pending',
                national_id_submitted_at = NOW(),
                national_id_rejection_reason = NULL
            WHERE id = :uid
        ");
        $stmtUpdate->execute([
            'id_num'  => $idNumber,
            'id_type' => $idType,
            'id_doc'  => $documentPath,
            'uid'     => $user['id']
        ]);

        // Refresh user record
        $stmt->execute(['id' => $user['id']]);
        $user = $stmt->fetch();

        $success = "National ID submitted successfully! Our administrative team will review your document.";
    }
}

// Format ID type label
$typeLabels = [
    'fayda_national_id' => 'Ethiopian Fayda National ID (FAN/FIN)',
    'kebele_id'         => 'Kebele Resident ID Card',
    'passport'          => 'Ethiopian Passport',
    'driver_license'    => "Driver's License"
];

$idStatus = $user['national_id_status'] ?? 'unverified';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-container" style="max-width: 860px; margin: 30px auto; padding: 0 16px;">

    <!-- Breadcrumb & Back -->
    <div style="margin-bottom: 20px;">
        <a href="<?= BASE_URL ?>/user/dashboard.php" style="color: var(--text-muted); text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
            &larr; Back to My Dashboard
        </a>
    </div>

    <!-- Page Title Header -->
    <div style="margin-bottom: 28px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
            <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(37, 99, 235, 0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 22px;">
                🛡️
            </div>
            <div>
                <h1 style="font-size: 26px; font-weight: 800; color: var(--secondary); letter-spacing: -0.02em; margin: 0;">
                    National ID Verification
                </h1>
                <p style="color: var(--text-muted); font-size: 14px; margin: 2px 0 0;">
                    Verify your identity with official Ethiopian Government documentation to earn the Verified Seller / Broker shield.
                </p>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($error)): ?>
        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 16px; margin-bottom: 24px; color: #dc2626; font-size: 14.5px; display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; margin-bottom: 24px; color: #16a34a; font-size: 14.5px; display: flex; align-items: center; gap: 10px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <!-- =========================================================================
         STATE 1: VERIFIED ACCOUNT CARD
         ========================================================================= -->
    <?php if ($idStatus === 'verified'): ?>
        <div style="background: #ffffff; border: 1px solid #10b981; border-radius: 16px; padding: 32px; box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.12); margin-bottom: 28px; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -10px; right: -10px; width: 100px; height: 100px; background: rgba(16, 185, 129, 0.08); border-radius: 50%; pointer-events: none;"></div>
            
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: #10b981; color: #ffffff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <div>
                        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 9999px; background: rgba(16, 185, 129, 0.12); color: #059669; font-weight: 700; font-size: 12px; margin-bottom: 4px;">
                            Official Verified Identity
                        </div>
                        <h2 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0;">
                            Account Verified with National ID
                        </h2>
                    </div>
                </div>
                
                <span class="badge badge-verified" style="padding: 8px 14px; font-size: 13px;">
                    Verified &bull; Level 2 ID
                </span>
            </div>

            <!-- Verified Details Grid -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
                <div>
                    <span style="display: block; font-size: 12px; color: var(--text-muted); font-weight: 600;">Full Legal Name</span>
                    <strong style="color: #0f172a; font-size: 15px;"><?= htmlspecialchars($user['full_name']) ?></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 12px; color: var(--text-muted); font-weight: 600;">ID Document Type</span>
                    <strong style="color: #0f172a; font-size: 15px;"><?= htmlspecialchars($typeLabels[$user['national_id_type']] ?? 'National ID') ?></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 12px; color: var(--text-muted); font-weight: 600;">National ID / Reg #</span>
                    <strong style="color: #0f172a; font-size: 15px; font-family: monospace;">
                        <?= htmlspecialchars(substr($user['national_id_number'] ?? '', 0, 4) . '••••' . substr($user['national_id_number'] ?? '', -4)) ?>
                    </strong>
                </div>
                <div>
                    <span style="display: block; font-size: 12px; color: var(--text-muted); font-weight: 600;">Verified Date</span>
                    <strong style="color: #10b981; font-size: 15px;">
                        <?= !empty($user['national_id_verified_at']) ? date('M d, Y', strtotime($user['national_id_verified_at'])) : 'Verified' ?>
                    </strong>
                </div>
            </div>

            <!-- Verified Privileges -->
            <div style="border-top: 1px solid #e2e8f0; padding-top: 18px;">
                <h4 style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">Active Verified Privileges:</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 10px; font-size: 13px; color: #475569;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: #10b981;">✔</span> Verified Seller Shield on all property listings
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: #10b981;">✔</span> 3x higher buyer inquiry rate from direct buyers
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: #10b981;">✔</span> Priority placement in Addis Ababa map explorer
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: #10b981;">✔</span> Protected against fraudulent impersonation
                    </div>
                </div>
            </div>
        </div>

    <!-- =========================================================================
         STATE 2: PENDING ADMIN APPROVAL CARD
         ========================================================================= -->
    <?php elseif ($idStatus === 'pending'): ?>
        <div style="background: #ffffff; border: 1px solid #f59e0b; border-radius: 16px; padding: 32px; box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.12); margin-bottom: 28px;">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 56px; height: 56px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 26px;">
                        ⏳
                    </div>
                    <div>
                        <div style="display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; border-radius: 9999px; background: rgba(245, 158, 11, 0.12); color: #d97706; font-weight: 700; font-size: 12px; margin-bottom: 4px;">
                            Under Administrative Review
                        </div>
                        <h2 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0;">
                            National ID Pending Verification
                        </h2>
                    </div>
                </div>
                
                <span class="badge" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 8px 14px; font-size: 13px;">
                    In Review Queue
                </span>
            </div>

            <p style="color: var(--text-muted); font-size: 14.5px; line-height: 1.6; margin-bottom: 22px;">
                Your National ID submission has been securely delivered to our trust & safety moderation team. An administrator will review your document within <strong>2–24 business hours</strong>. You will receive an immediate confirmation once your status is accepted.
            </p>

            <!-- Submitted Details Preview -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; font-size: 13.5px; margin-bottom: 20px;">
                <div>
                    <span style="display: block; font-size: 11.5px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Document Type</span>
                    <strong><?= htmlspecialchars($typeLabels[$user['national_id_type']] ?? 'National ID') ?></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 11.5px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Submitted ID #</span>
                    <strong style="font-family: monospace;"><?= htmlspecialchars($user['national_id_number'] ?? 'N/A') ?></strong>
                </div>
                <div>
                    <span style="display: block; font-size: 11.5px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Submitted At</span>
                    <strong><?= !empty($user['national_id_submitted_at']) ? date('M d, Y H:i', strtotime($user['national_id_submitted_at'])) : 'Recently' ?></strong>
                </div>
                <?php if (!empty($user['national_id_document'])): ?>
                    <?php 
                        $docPreviewUrl = str_starts_with($user['national_id_document'], 'http') ? $user['national_id_document'] : BASE_URL . '/' . ltrim($user['national_id_document'], '/');
                    ?>
                    <div>
                        <span style="display: block; font-size: 11.5px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Document Scan</span>
                        <a href="<?= htmlspecialchars($docPreviewUrl) ?>" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                            <span>👁️ View Uploaded ID</span> &rarr;
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('reuploadFormSection').style.display='block'; this.style.display='none';">
                    Update / Re-upload Document
                </button>
            </div>
        </div>

    <!-- =========================================================================
         STATE 3: REJECTED NOTICE CARD
         ========================================================================= -->
    <?php elseif ($idStatus === 'rejected'): ?>
        <div style="background: #ffffff; border: 1px solid #ef4444; border-radius: 16px; padding: 28px; box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.12); margin-bottom: 28px;">
            <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 14px;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    ⚠️
                </div>
                <div>
                    <h3 style="font-size: 19px; font-weight: 800; color: #991b1b; margin: 0;">
                        Verification Submission Rejected
                    </h3>
                    <p style="color: #b91c1c; font-size: 13.5px; margin: 2px 0 0;">
                        Reason provided by moderator: <strong><?= htmlspecialchars($user['national_id_rejection_reason'] ?: 'The document was blurry or details did not match.') ?></strong>
                    </p>
                </div>
            </div>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 0;">
                Please review your documentation, ensure all text and edges are clearly visible in high lighting, and re-submit using the form below.
            </p>
        </div>
    <?php endif; ?>

    <!-- =========================================================================
         STATE 4: SUBMISSION / RE-SUBMISSION FORM
         ========================================================================= -->
    <div id="reuploadFormSection" style="<?= ($idStatus === 'pending' || $idStatus === 'verified') ? 'display: none;' : 'display: block;' ?>">
        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 16px; padding: 32px; box-shadow: var(--shadow-sm);">
            
            <h2 style="font-size: 20px; font-weight: 700; color: var(--secondary); margin-bottom: 6px;">
                <?= ($idStatus === 'rejected') ? 'Re-Submit National ID for Verification' : 'Submit Your Official Identification' ?>
            </h2>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">
                Fill in your legal details and upload a photo of your valid Ethiopian identification card.
            </p>

            <form action="<?= BASE_URL ?>/user/verify_id.php" method="POST" enctype="multipart/form-data">
                <?= getCsrfInput() ?>

                <!-- Full Legal Name -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: var(--secondary); margin-bottom: 6px;">
                        Full Legal Name (as shown on official ID) <span style="color: #ef4444;">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="legal_name" 
                        class="form-control" 
                        value="<?= htmlspecialchars($user['full_name']) ?>" 
                        required 
                        style="width: 100%; height: 46px; border: 1px solid var(--border-color); border-radius: 8px; padding: 0 14px; font-size: 14.5px;"
                    />
                </div>

                <!-- ID Document Type -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: var(--secondary); margin-bottom: 6px;">
                        Select Document Type <span style="color: #ef4444;">*</span>
                    </label>
                    <select 
                        name="id_type" 
                        class="form-control" 
                        required 
                        style="width: 100%; height: 46px; border: 1px solid var(--border-color); border-radius: 8px; padding: 0 14px; font-size: 14.5px; background: #fff;"
                    >
                        <option value="fayda_national_id" <?= ($user['national_id_type'] ?? '') === 'fayda_national_id' ? 'selected' : '' ?>>
                            🇪🇹 Ethiopian Fayda National ID (FAN / FIN Digital ID)
                        </option>
                        <option value="kebele_id" <?= ($user['national_id_type'] ?? '') === 'kebele_id' ? 'selected' : '' ?>>
                            🏛️ Kebele Resident Identification Card
                        </option>
                        <option value="passport" <?= ($user['national_id_type'] ?? '') === 'passport' ? 'selected' : '' ?>>
                            ✈️ Ethiopian Passport
                        </option>
                        <option value="driver_license" <?= ($user['national_id_type'] ?? '') === 'driver_license' ? 'selected' : '' ?>>
                            🚗 Ethiopian Driver's License
                        </option>
                    </select>
                </div>

                <!-- National ID / Registration Number -->
                <div class="form-group" style="margin-bottom: 22px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: var(--secondary); margin-bottom: 6px;">
                        National ID / Document Registration Number <span style="color: #ef4444;">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="id_number" 
                        class="form-control" 
                        placeholder="e.g., ET-FAYDA-88392-AA or Kebele ID #" 
                        value="<?= htmlspecialchars($user['national_id_number'] ?? '') ?>" 
                        required 
                        style="width: 100%; height: 46px; border: 1px solid var(--border-color); border-radius: 8px; padding: 0 14px; font-size: 14.5px; font-family: monospace;"
                    />
                    <small style="color: var(--text-muted); font-size: 12px; margin-top: 4px; display: block;">
                        Your ID number is strictly confidential and only accessed by verified compliance officers.
                    </small>
                </div>

                <!-- Document Photo / File Upload Zone -->
                <div class="form-group" style="margin-bottom: 28px;">
                    <label style="display: block; font-size: 13.5px; font-weight: 600; color: var(--secondary); margin-bottom: 6px;">
                        Upload ID Photo or Scan (Front Side) <span style="color: #ef4444;">*</span>
                    </label>
                    
                    <div style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 28px 20px; text-align: center; background: #f8fafc; cursor: pointer; transition: border-color 0.2s;" onclick="document.getElementById('id_document_input').click()">
                        <input 
                            type="file" 
                            name="id_document" 
                            id="id_document_input" 
                            accept="image/jpeg,image/png,image/webp,application/pdf" 
                            style="display: none;" 
                            onchange="previewIdUpload(this)"
                        />
                        <div style="font-size: 32px; margin-bottom: 8px;">📸</div>
                        <div style="font-size: 14.5px; font-weight: 700; color: var(--secondary); margin-bottom: 4px;">
                            Click to upload or drag & drop your National ID
                        </div>
                        <div style="font-size: 12.5px; color: var(--text-muted);">
                            Supports JPG, PNG, WEBP, or PDF (High resolution, all 4 corners visible, Max 8MB)
                        </div>
                        <div id="previewFileName" style="margin-top: 10px; font-weight: 700; color: var(--primary); font-size: 13.5px;"></div>
                    </div>

                    <!-- Live Image Preview Thumbnail -->
                    <div id="idImgPreviewContainer" style="display: none; margin-top: 12px; text-align: center;">
                        <img id="idImgPreview" src="" alt="ID Preview" style="max-height: 200px; max-width: 100%; border-radius: 8px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);" />
                    </div>
                </div>

                <!-- Security & Privacy Declaration -->
                <div style="background: rgba(37, 99, 235, 0.05); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: 10px; padding: 14px 18px; margin-bottom: 24px; font-size: 12.5px; color: #1e40af; line-height: 1.5;">
                    🔒 <strong>Privacy Guarantee:</strong> EstateCraft complies with the Ethiopian Personal Data & Identity Protection guidelines. Your document will never be shared publicly or sold. It is stored securely and viewed solely by administrator accounts for verification audits.
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" style="width: 100%; height: 48px; font-size: 15px; font-weight: 700; border-radius: 10px;">
                    Submit National ID for Verification &rarr;
                </button>
            </form>
        </div>
    </div>

</div>

<script>
function previewIdUpload(input) {
    const file = input.files[0];
    const nameEl = document.getElementById('previewFileName');
    const previewContainer = document.getElementById('idImgPreviewContainer');
    const previewImg = document.getElementById('idImgPreview');

    if (file) {
        nameEl.textContent = `Selected: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
