<?php
/**
 * Password Reset - Set New Password Page
 */

$pageTitle = "Set New Password";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/csrf.php';

$pdo = getDB();

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = '';
$validToken = false;
$resetRecord = null;

if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :t AND expires_at > NOW() LIMIT 1");
    $stmt->execute(['t' => $token]);
    $resetRecord = $stmt->fetch();
    if ($resetRecord) {
        $validToken = true;
    } else {
        $error = "This password reset link is invalid or has expired. Please request a new one.";
    }
} else {
    $error = "No reset token provided. Please request a password reset from the login page.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    validateCsrfOrDie();

    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters in length.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match. Please verify and try again.";
    } else {
        // Update user's password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmtUpdate = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
        $stmtUpdate->execute([
            'hash'  => $newHash,
            'email' => $resetRecord['email']
        ]);

        // Delete used token
        $pdo->prepare("DELETE FROM password_resets WHERE email = :email")->execute(['email' => $resetRecord['email']]);

        $_SESSION['flash_success'] = "🎉 Your password has been successfully reset! You can now sign in with your new password.";
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 500px;">
        <div class="auth-header">
            <h1 class="auth-title"><?= __('reset_pass_title', 'Reset Your Password') ?></h1>
            <p class="auth-sub">
                <?php if ($validToken): ?>
                    Resetting password for: <strong><?= htmlspecialchars($resetRecord['email']) ?></strong>
                <?php else: ?>
                    Invalid or Expired Link
                <?php endif; ?>
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($validToken): ?>
            <form method="POST" action="">
                <?= getCsrfInput() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="newPassword">New Password (Min. 6 chars) *</label>
                    <input type="password" id="newPassword" name="new_password" class="form-input" placeholder="••••••••" required autofocus>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="confirmPassword">Confirm New Password *</label>
                    <input type="password" id="confirmPassword" name="confirm_password" class="form-input" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitResetBtn">
                    Update Password & Sign In &rarr;
                </button>
            </form>
        <?php else: ?>
            <div style="text-align: center; margin-top: 16px;">
                <a href="<?= BASE_URL ?>/auth/forgot_password.php" class="btn btn-primary btn-block">
                    Request New Reset Link
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
