<?php
/**
 * User & Broker Registration Page
 */

$pageTitle = "Create an Account";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/otp_service.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfOrDie();

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role     = 'user';

    // Validation
    if (empty($fullName) || empty($email) || empty($password) || empty($phone)) {
        $error = "Please fill in all required fields (Name, Email, Phone, and Password).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters in length.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $pdo = getDB();

        // Check if email already registered
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmtCheck->execute(['email' => $email]);
        if ($stmtCheck->fetch()) {
            $error = "An account with that email address already exists. Please sign in instead.";
        } else {
            // Hash password securely
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmtInsert = $pdo->prepare("
                INSERT INTO users (
                    full_name, email, password_hash, phone, phone_verified, role,
                    broker_agency, broker_license_no, broker_fee_percentage, broker_fee_notes
                ) VALUES (
                    :name, :email, :pass, :phone, 0, :role,
                    :agency, :license, :fee_pct, :fee_notes
                )
            ");

            $stmtInsert->execute([
                'name'      => $fullName,
                'email'     => $email,
                'pass'      => $passwordHash,
                'phone'     => $phone,
                'role'      => $role,
                'agency'    => $role === 'broker' ? $brokerAgency : null,
                'license'   => $role === 'broker' ? $brokerLicense : null,
                'fee_pct'   => $role === 'broker' ? $brokerFeePct : null,
                'fee_notes' => $role === 'broker' ? $brokerFeeNote : null,
            ]);

            $newUserId = (int)$pdo->lastInsertId();

            // Fetch created user and log in session
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmtUser->execute(['id' => $newUserId]);
            $newUser = $stmtUser->fetch();
            loginUser($newUser);

            // Automatically trigger phone verification OTP step
            sendPhoneOtp($newUserId, $phone);

            $_SESSION['flash_success'] = "Account created successfully! Please verify your phone number to complete setup.";
            header("Location: " . BASE_URL . "/auth/verify_phone.php");
            exit;
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
    <div class="auth-card" style="max-width: 580px;">
        <div class="auth-header">
            <h1 class="auth-title">Join EstateCraft</h1>
            <p class="auth-sub">Create your account to browse, buy, rent, or list homes</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- Continue with Google Button -->
        <a href="<?= $googleAuthUrl ?>" class="google-auth-btn" id="btnGoogleRegister">
            <svg width="20" height="20" viewBox="0 0 48 48">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Continue with Google
        </a>

        <div class="auth-divider">
            <span>or register with email and phone</span>
        </div>

        <form method="POST" action="">
            <?= getCsrfInput() ?>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" for="regName">Full Legal Name *</label>
                <input type="text" id="regName" name="full_name" class="form-input" placeholder="e.g. John Doe" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label" for="regEmail">Email Address *</label>
                    <input type="email" id="regEmail" name="email" class="form-input" placeholder="you@domain.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="regPhone">Mobile Phone (For OTP) *</label>
                    <input type="tel" id="regPhone" name="phone" class="form-input" placeholder="+1 (555) 000-0000" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div class="form-group">
                    <label class="form-label" for="regPassword">Password *</label>
                    <input type="password" id="regPassword" name="password" class="form-input" placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="regConfirmPassword">Confirm Password *</label>
                    <input type="password" id="regConfirmPassword" name="confirm_password" class="form-input" placeholder="Repeat password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitRegisterBtn">Create Account & Verify Mobile</button>
        </form>

        <p style="text-align: center; margin-top: 24px; font-size: 14px; color: var(--text-muted);">
            Already have an account? 
            <a href="<?= BASE_URL ?>/auth/login.php" style="font-weight: 700;">Sign In</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
