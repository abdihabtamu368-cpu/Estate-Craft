<?php
/**
 * User & Broker Login Page
 */

$pageTitle = "Sign In";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please provide both your email address and password.";
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
            loginUser($user);
            $_SESSION['flash_success'] = "Welcome back, " . htmlspecialchars($user['full_name']) . "!";
            
            $redirectTo = $_SESSION['redirect_after_login'] ?? (BASE_URL . '/user/dashboard.php');
            unset($_SESSION['redirect_after_login']);
            header("Location: " . $redirectTo);
            exit;
        } else {
            $error = "Invalid email address or password. Please verify and try again.";
        }
    }
}

// Google OAuth Login URL
$googleAuthUrl = isGoogleAuthReady()
    ? "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'email profile',
        'access_type'   => 'online',
        'prompt'        => 'select_account'
    ])
    : BASE_URL . "/auth/google_callback.php?notice=config_required";

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-sub">Sign in to manage your property listings and searches</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- Continue with Google Button -->
        <a href="<?= $googleAuthUrl ?>" class="google-auth-btn" id="btnGoogleSignIn">
            <svg width="20" height="20" viewBox="0 0 48 48">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Continue with Google
        </a>

        <div class="auth-divider">
            <span>or sign in with email</span>
        </div>

        <form method="POST" action="">
            <?= getCsrfInput() ?>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" for="loginEmail">Email Address</label>
                <input type="email" id="loginEmail" name="email" class="form-input" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label class="form-label" for="loginPassword" style="margin-bottom: 0;">Password</label>
                    <a href="<?= BASE_URL ?>/auth/forgot_password.php" style="font-size: 13px; font-weight: 600;">Forgot Password?</a>
                </div>
                <div style="position: relative; margin-top: 6px;">
                    <input type="password" id="loginPassword" name="password" class="form-input" placeholder="••••••••" required style="padding-right: 42px;">
                    <button type="button" onclick="togglePasswordVisibility()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 16px;" id="togglePwBtn" title="Show/Hide Password">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitLoginBtn">Sign In</button>
        </form>

        <p style="text-align: center; margin-top: 24px; font-size: 14px; color: var(--text-muted);">
            Don't have an account yet? 
            <a href="<?= BASE_URL ?>/auth/register.php" style="font-weight: 700;">Create Account</a>
        </p>

        <!-- Quick Demo Accounts Helper for Local WampServer Testing -->
        <div style="margin-top: 30px; padding: 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px;">
            <div style="font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase;">
                ⚡ Quick Fill (Password: <code>Password123!</code>):
            </div>
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="quickFill('abdihabtamu368@gmail.com')">
                    Fill My Account (Abdi Habtamu)
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="quickFill('sara.broker@realestate.local')">
                    Fill Verified Broker (Sara Tefera - Addis Premier)
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="quickFill('dawit.owner@example.com')">
                    Fill Direct Owner (Dawit Bekele)
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="quickFill('admin@realestate.local')">
                    Fill Safety Moderator / Admin
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function quickFill(email) {
    document.getElementById('loginEmail').value = email;
    document.getElementById('loginPassword').value = 'Password123!';
}

function togglePasswordVisibility() {
    const pw = document.getElementById('loginPassword');
    const btn = document.getElementById('togglePwBtn');
    if (pw.type === 'password') {
        pw.type = 'text';
        btn.textContent = '🙈';
    } else {
        pw.type = 'password';
        btn.textContent = '👁️';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
