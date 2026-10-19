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

<section class="fb-landing-section" style="min-height: calc(100vh - 80px);">
    <div class="fb-landing-container">
        <!-- Left Column: Branding, Tagline & Photo Cards Collage -->
        <div class="fb-left-col">
            <div class="fb-brand-circle">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>

            <h1 class="fb-headline">
                Explore<br>
                the homes<br>
                <span class="accent-blue">you love.</span>
            </h1>

            <!-- Visual Collage -->
            <div class="fb-collage">
                <!-- Phone-style story card (Main top right) -->
                <div class="fb-card-story">
                    <div class="fb-story-time">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        16:45
                    </div>
                    <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=600&q=80" alt="Modern Villa in Addis Ababa">
                    <div class="fb-story-bottom-bar">
                        <div class="fb-story-circle"></div>
                        <div class="fb-story-circle"></div>
                        <div class="fb-story-circle"></div>
                    </div>
                </div>

                <!-- Middle left square card with star badge -->
                <div class="fb-card-square">
                    <div class="fb-card-badge">★</div>
                    <img src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=500&q=80" alt="Modern Interior">
                </div>

                <!-- Bottom card -->
                <div class="fb-card-bottom">
                    <img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=500&q=80" alt="Luxury Compound">
                </div>

                <!-- Avatar circle -->
                <div class="fb-avatar-circle">
                    <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=400&q=80" alt="Verified Broker">
                </div>

                <!-- Floating Reactions -->
                <div class="fb-reaction-laugh" title="Excited">😆</div>
                <div class="fb-reaction-heart" title="Love this home">❤️</div>
            </div>
        </div>

        <!-- Center Divider Line -->
        <div class="fb-center-divider"></div>

        <!-- Right Column: Login Card -->
        <div class="fb-right-col">
            <div class="fb-auth-box">
                <h2 class="fb-login-heading">Log into EstateCraft</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error" style="margin-bottom: 18px; border-radius: 8px;">
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <!-- Continue with Google Button -->
                <a href="<?= $googleAuthUrl ?>" class="google-auth-btn" id="btnGoogleSignIn" style="margin-bottom: 16px; border-radius: 9999px; height: 46px;">
                    <svg width="20" height="20" viewBox="0 0 48 48">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                    Continue with Google
                </a>

                <div class="auth-divider" style="margin-bottom: 16px;">
                    <span>or sign in with email</span>
                </div>

                <form method="POST" action="">
                    <?= getCsrfInput() ?>

                    <div class="fb-input-wrapper">
                        <input type="email" id="loginEmail" name="email" class="fb-field-input" placeholder="Email or mobile number" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="username">
                    </div>

                    <div class="fb-input-wrapper" style="position: relative;">
                        <input type="password" id="loginPassword" name="password" class="fb-field-input" placeholder="Password" required autocomplete="current-password" style="padding-right: 44px;">
                        <button type="button" onclick="togglePasswordVisibility()" style="position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; font-size: 16px;" id="togglePwBtn" title="Show/Hide Password">
                            👁️
                        </button>
                    </div>

                    <button type="submit" class="fb-submit-btn" id="submitLoginBtn">
                        Log in
                    </button>

                    <div class="fb-forgot-link-wrap">
                        <a href="<?= BASE_URL ?>/auth/forgot_password.php" class="fb-forgot-link">Forgot password?</a>
                    </div>

                    <div class="fb-line-sep"></div>

                    <a href="<?= BASE_URL ?>/auth/register.php" class="fb-create-acc-btn" id="btnRegisterNewAcc">
                        Create new account
                    </a>
                </form>

                <div class="fb-guest-link-wrap">
                    <a href="<?= BASE_URL ?>/index.php#propertiesCatalogSection" class="fb-guest-link">
                        Explore properties as guest &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
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
