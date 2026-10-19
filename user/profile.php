<?php
/**
 * User & Broker Profile Management
 * Edit Profile Info, Broker License & Fee Terms, Phone Verification Trigger
 */

$pageTitle = "My Profile";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/otp_service.php';

$user = requireAuth();
$pdo = getDB();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie();

    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');

    // Broker fields
    $agency   = trim($_POST['broker_agency'] ?? '');
    $license  = trim($_POST['broker_license_no'] ?? '');
    $feePct   = isset($_POST['broker_fee_percentage']) && is_numeric($_POST['broker_fee_percentage']) ? (float)$_POST['broker_fee_percentage'] : 2.0;
    $feeNotes = trim($_POST['broker_fee_notes'] ?? '');

    // Password change fields
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($fullName) || empty($phone)) {
        $error = "Name and Phone number are required.";
    } else {
        // If phone number changed, reset verification
        $phoneChanged = ($phone !== $user['phone']);
        $phoneVerified = $phoneChanged ? 0 : (int)$user['phone_verified'];

        // Update profile details
        $stmtUpdate = $pdo->prepare("
            UPDATE users SET 
                full_name = :name,
                phone = :phone,
                phone_verified = :verified,
                bio = :bio,
                broker_agency = :agency,
                broker_license_no = :license,
                broker_fee_percentage = :fee_pct,
                broker_fee_notes = :fee_notes
            WHERE id = :id
        ");

        $stmtUpdate->execute([
            'name'      => $fullName,
            'phone'     => $phone,
            'verified'  => $phoneVerified,
            'bio'       => $bio,
            'agency'    => $user['role'] === 'broker' ? $agency : null,
            'license'   => $user['role'] === 'broker' ? $license : null,
            'fee_pct'   => $user['role'] === 'broker' ? $feePct : null,
            'fee_notes' => $user['role'] === 'broker' ? $feeNotes : null,
            'id'        => $user['id']
        ]);

        // Handle Password Change if requested
        if (!empty($newPass)) {
            $stmtPassCheck = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
            $stmtPassCheck->execute(['id' => $user['id']]);
            $currentHash = $stmtPassCheck->fetchColumn();

            if (!empty($currentHash) && !password_verify($currentPass, $currentHash)) {
                $error = "Current password is incorrect.";
            } elseif (strlen($newPass) < 6) {
                $error = "New password must be at least 6 characters.";
            } elseif ($newPass !== $confirmPass) {
                $error = "New passwords do not match.";
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password_hash = :p WHERE id = :id")->execute(['p' => $newHash, 'id' => $user['id']]);
                $success = "Profile and password updated successfully!";
            }
        } else {
            $success = "Profile updated successfully!";
        }

        $user = refreshCurrentUser();

        // If phone was changed, send OTP code right away
        if ($phoneChanged) {
            sendPhoneOtp($user['id'], $phone);
            $_SESSION['flash_warning'] = "Phone number updated. Please enter the verification code sent to your new number.";
            header("Location: " . BASE_URL . "/auth/verify_phone.php");
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 30px auto 60px; padding: 0 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 28px; font-weight: 800; color: var(--secondary);">Account & Broker Profile</h1>
            <p style="color: var(--text-muted); font-size: 14px;">Manage contact details, mobile verification, and transparent broker fees.</p>
        </div>
        <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-secondary btn-sm">&larr; Back to Dashboard</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <div><?= htmlspecialchars($success) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; box-shadow: var(--shadow-sm);">
        <?= getCsrfInput() ?>

        <h3 style="font-size: 18px; font-weight: 700; color: var(--secondary); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">
            Personal Information
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div class="form-group">
                <label class="form-label" for="profName">Full Name *</label>
                <input type="text" id="profName" name="full_name" class="form-input" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address (Read-Only)</label>
                <input type="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background: #f1f5f9; cursor: not-allowed;">
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
            <label class="form-label" for="profPhone">
                Mobile Number *
                <?php if ($user['phone_verified']): ?>
                    <span class="badge badge-verified" style="margin-left: 8px;">Verified Checkmark</span>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/auth/verify_phone.php" style="margin-left: 8px; font-size: 12px; font-weight: 700; color: #dc2626;">Verify Now &rarr;</a>
                <?php endif; ?>
            </label>
            <input type="tel" id="profPhone" name="phone" class="form-input" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
            <small style="color: var(--text-muted); font-size: 12px;">Changing your phone number will require re-verifying with a 6-digit SMS OTP code.</small>
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
            <label class="form-label" for="profBio">Professional Bio</label>
            <textarea id="profBio" name="bio" rows="3" class="form-textarea" placeholder="Tell potential clients about your experience, neighborhood specializations, and background..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
        </div>

        <!-- Broker Specific Section -->
        <?php if ($user['role'] === 'broker'): ?>
            <h3 style="font-size: 18px; font-weight: 700; color: #92400e; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #fde68a;">
                Broker Credentials & Transparent Fee Policy
            </h3>

            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 20px; margin-bottom: 24px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="brokerAgency">Real Estate Brokerage / Agency</label>
                        <input type="text" id="brokerAgency" name="broker_agency" class="form-input" value="<?= htmlspecialchars($user['broker_agency'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="brokerLicense">Real Estate License Number</label>
                        <input type="text" id="brokerLicense" name="broker_license_no" class="form-input" value="<?= htmlspecialchars($user['broker_license_no'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label" for="brokerFeePct">Standard Sales Commission Rate (%)</label>
                    <input type="number" step="0.1" id="brokerFeePct" name="broker_fee_percentage" class="form-input" value="<?= htmlspecialchars((string)($user['broker_fee_percentage'] ?? '2.5')) ?>">
                    <small style="color: #78350f; font-size: 12px;">Displayed prominently on all your listings so clients know fees upfront.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="brokerFeeNotes">Broker Fee Disclosure Statement / Terms</label>
                    <textarea id="brokerFeeNotes" name="broker_fee_notes" rows="2" class="form-textarea"><?= htmlspecialchars($user['broker_fee_notes'] ?? 'Standard 2.5% commission upon closing. No hidden upfront fees.') ?></textarea>
                </div>
            </div>
        <?php endif; ?>

        <h3 style="font-size: 18px; font-weight: 700; color: var(--secondary); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">
            Security & Password Update (Leave blank to keep current)
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 30px;">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-input" placeholder="••••••••">
            </div>
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-input" placeholder="••••••••">
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-input" placeholder="••••••••">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">Save Profile Changes</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
