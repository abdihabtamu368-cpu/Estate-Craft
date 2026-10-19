<?php
/**
 * Forgot Password - Recovery Request Page
 */

$pageTitle = "Forgot Password";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/csrf.php';

$error = '';
$success = '';
$devResetLink = '';
$devOtp = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie();

    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(24));
            $otp = (string)random_int(100000, 999999);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            // Clear old tokens for this email
            $pdo->prepare("DELETE FROM password_resets WHERE email = :email")->execute(['email' => $email]);

            // Insert fresh token
            $stmtInsert = $pdo->prepare("
                INSERT INTO password_resets (email, token, otp_code, expires_at)
                VALUES (:email, :token, :otp, :expires)
            ");
            $stmtInsert->execute([
                'email'   => $email,
                'token'   => $token,
                'otp'     => $otp,
                'expires' => $expiresAt
            ]);

            $devResetLink = BASE_URL . "/auth/reset_password.php?token=" . urlencode($token);
            $devOtp = $otp;

            $success = "A password reset link and verification code have been generated for {$email}.";
        } else {
            // Friendly message even if email not found for privacy
            $error = "No registered account found with that email address.";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 500px;">
        <div class="auth-header">
            <div style="width: 56px; height: 56px; background: rgba(37, 99, 235, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>
            <h1 class="auth-title"><?= __('reset_pass_title', 'Reset Your Password') ?></h1>
            <p class="auth-sub">Enter your email address and we'll help you securely reset your password.</p>
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

            <!-- Local WampServer Instant Test Link -->
            <div style="background: #eff6ff; border: 2px dashed #3b82f6; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <div style="font-weight: 700; color: #1e40af; font-size: 13px; margin-bottom: 6px;">
                    🔧 LOCAL WAMP DEVELOPMENT TEST LINK:
                </div>
                <p style="font-size: 13px; color: #1e3a8a; margin-bottom: 12px;">
                    In local testing mode, you can click below to set your new password immediately or use OTP code <strong><?= htmlspecialchars($devOtp) ?></strong>:
                </p>
                <a href="<?= htmlspecialchars($devResetLink) ?>" class="btn btn-primary btn-block btn-sm">
                    Open Password Reset Form &rarr;
                </a>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= getCsrfInput() ?>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" for="resetEmail">Your Registered Email Address</label>
                <input type="email" id="resetEmail" name="email" class="form-input" placeholder="you@domain.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitForgotBtn">
                <?= __('send_reset_code', 'Send Reset Link / OTP') ?>
            </button>
        </form>

        <p style="text-align: center; margin-top: 24px; font-size: 14px; color: var(--text-muted);">
            Remembered your password? 
            <a href="<?= BASE_URL ?>/auth/login.php" style="font-weight: 700;"><?= __('nav_signin', 'Sign In') ?></a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
