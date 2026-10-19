<?php
/**
 * Phone Verification OTP Page
 */

$pageTitle = "Verify Mobile Number";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/otp_service.php';

$user = requireAuth();

$error = '';
$success = '';

// Handle Resend OTP Request
if (isset($_POST['action']) && $_POST['action'] === 'resend') {
    validateCsrfOrDie();
    $newPhone = trim($_POST['phone'] ?? $user['phone'] ?? '');
    
    if (empty($newPhone)) {
        $error = "Please specify a phone number to send the OTP code.";
    } else {
        $sendResult = sendPhoneOtp($user['id'], $newPhone);
        if ($sendResult['success']) {
            $success = "A fresh 6-digit verification code has been dispatched!";
            $user = refreshCurrentUser();
        } else {
            $error = $sendResult['message'];
        }
    }
}

// Handle OTP Submission
if (isset($_POST['action']) && $_POST['action'] === 'verify') {
    validateCsrfOrDie();
    $code = trim($_POST['otp_code'] ?? '');

    if (empty($code) || strlen($code) < 4) {
        $error = "Please enter the 6-digit verification code sent to your phone.";
    } else {
        $verifyResult = verifyUserOtp($user['id'], $code);
        if ($verifyResult['success']) {
            $_SESSION['flash_success'] = "🎉 Mobile number verified successfully! Your profile badge is now updated.";
            header("Location: " . BASE_URL . "/user/dashboard.php");
            exit;
        } else {
            $error = $verifyResult['message'];
        }
    }
}

// Auto-generate code if user currently has no OTP pending and is not verified
if (empty($user['phone_verified']) && empty($_SESSION['dev_otp_code']) && !empty($user['phone'])) {
    sendPhoneOtp($user['id'], $user['phone']);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 520px;">
        <div class="auth-header">
            <div style="width: 56px; height: 56px; background: rgba(37, 99, 235, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <h1 class="auth-title">Verify Phone Number</h1>
            <p class="auth-sub">
                <?php if ($user['phone_verified']): ?>
                    Your mobile number is verified with a trusted checkmark badge.
                <?php else: ?>
                    A 6-digit one-time passcode (OTP) has been dispatched to <strong><?= htmlspecialchars($user['phone'] ?? 'your phone') ?></strong>.
                <?php endif; ?>
            </p>
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

        <?php if (!empty($_SESSION['dev_otp_code'])): ?>
            <!-- Development Mode Transparent Notice (As required by prompt) -->
            <div style="background: #eff6ff; border: 2px dashed #3b82f6; border-radius: 12px; padding: 18px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: #1e40af; font-size: 13px; margin-bottom: 6px;">
                    <span>🔧 LOCAL TESTING / DEV MODE</span>
                </div>
                <p style="font-size: 13px; color: #1e3a8a; margin-bottom: 10px;">
                    Twilio SMS API is not configured in <code>config/config.php</code>. In local development mode, your generated OTP code is displayed below:
                </p>
                <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; padding: 10px 16px; border-radius: 8px; border: 1px solid #bfdbfe;">
                    <span style="font-size: 13px; color: #64748b; font-weight: 600;">Simulated SMS Code:</span>
                    <strong style="font-size: 24px; color: #2563eb; letter-spacing: 4px; font-family: monospace;"><?= htmlspecialchars($_SESSION['dev_otp_code']) ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($user['phone_verified']): ?>
            <div class="alert alert-success" style="justify-content: center; text-align: center;">
                <strong>✅ Phone Number (<?= htmlspecialchars($user['phone']) ?>) is Verified</strong>
            </div>
            <a href="<?= BASE_URL ?>/user/dashboard.php" class="btn btn-primary btn-block">Go to Dashboard</a>
        <?php else: ?>
            <form method="POST" action="">
                <?= getCsrfInput() ?>
                <input type="hidden" name="action" value="verify">

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label" for="otpInput" style="text-align: center; display: block; font-size: 14px;">Enter 6-Digit Verification Code</label>
                    <input type="text" id="otpInput" name="otp_code" class="form-input" placeholder="••••••" maxlength="6" style="text-align: center; font-size: 26px; letter-spacing: 6px; font-weight: 800; height: 60px;" required autofocus value="<?= htmlspecialchars($_SESSION['dev_otp_code'] ?? '') ?>">
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" id="verifyOtpBtn">Verify & Activate Account</button>
            </form>

            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center;">
                <form method="POST" action="" style="display: inline-block;">
                    <?= getCsrfInput() ?>
                    <input type="hidden" name="action" value="resend">
                    <span style="font-size: 13px; color: var(--text-muted);">Didn't receive the SMS? </span>
                    <button type="submit" class="btn btn-outline btn-sm" style="margin-left: 6px;">Resend Code</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
