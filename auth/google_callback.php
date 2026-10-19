<?php
/**
 * Google OAuth 2.0 Callback & Setup Guide
 */

$pageTitle = "Google Sign-In Authentication";
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if credentials are not configured
if (!isGoogleAuthReady() || isset($_GET['notice'])) {
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="auth-wrapper">
        <div class="auth-card" style="max-width: 650px;">
            <div style="width: 56px; height: 56px; background: rgba(234, 67, 53, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <svg width="28" height="28" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
            </div>

            <h1 class="auth-title" style="text-align: center;">Google Sign-In Configuration</h1>
            <p class="auth-sub" style="text-align: center; margin-bottom: 24px;">
                OAuth 2.0 is fully built and ready, but requires your Google Cloud Client ID & Secret to exchange live tokens.
            </p>

            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 24px; font-size: 14px; line-height: 1.7;">
                <strong style="color: var(--secondary); display: block; margin-bottom: 8px;">To activate live Google Authentication:</strong>
                <ol style="margin-left: 20px; color: #475569;">
                    <li>Visit <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console &rarr; Credentials</a>.</li>
                    <li>Create an <strong>OAuth 2.0 Client ID</strong> (Application type: <em>Web application</em>).</li>
                    <li>Add this exact Authorized Redirect URI:
                        <br><code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; color: #0f172a; word-break: break-all;"><?= htmlspecialchars(GOOGLE_REDIRECT_URI) ?></code>
                    </li>
                    <li>Open <code>config/config.php</code> and fill in <code>GOOGLE_CLIENT_ID</code> and <code>GOOGLE_CLIENT_SECRET</code>.</li>
                </ol>
            </div>

            <div style="display: flex; gap: 12px;">
                <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary btn-block">Back to Sign In</a>
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-secondary btn-block">Go to Home</a>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Handle incoming OAuth callback from Google
if (!empty($_GET['code'])) {
    $code = $_GET['code'];

    // 1. Exchange authorization code for access token
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code'
    ];

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $tokenResponse = curl_exec($ch);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        $_SESSION['flash_error'] = "Google authentication failed: " . $curlErr;
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }

    $tokenJson = json_decode($tokenResponse, true);
    $accessToken = $tokenJson['access_token'] ?? null;

    if (!$accessToken) {
        $_SESSION['flash_error'] = "Could not obtain access token from Google: " . ($tokenJson['error_description'] ?? 'Unknown error');
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }

    // 2. Fetch User Profile
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch = curl_init($userInfoUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $userResponse = curl_exec($ch);
    curl_close($ch);

    $googleUser = json_decode($userResponse, true);

    if (empty($googleUser['email'])) {
        $_SESSION['flash_error'] = "Google did not return an email address.";
        header("Location: " . BASE_URL . "/auth/login.php");
        exit;
    }

    $email = $googleUser['email'];
    $googleId = $googleUser['id'] ?? null;
    $name = $googleUser['name'] ?? explode('@', $email)[0];
    $avatar = $googleUser['picture'] ?? null;

    $pdo = getDB();

    // Check if user exists by google_id or email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = :gid OR email = :email LIMIT 1");
    $stmt->execute(['gid' => $googleId, 'email' => $email]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        // Update google_id and avatar if missing
        $stmtUpdate = $pdo->prepare("UPDATE users SET google_id = :gid, avatar = COALESCE(avatar, :avatar) WHERE id = :id");
        $stmtUpdate->execute(['gid' => $googleId, 'avatar' => $avatar, 'id' => $existingUser['id']]);
        
        loginUser($existingUser);
        $_SESSION['flash_success'] = "Signed in with Google as " . htmlspecialchars($existingUser['full_name']) . "!";
    } else {
        // Create new user account via Google
        $stmtInsert = $pdo->prepare("
            INSERT INTO users (full_name, email, google_id, avatar, role, phone_verified)
            VALUES (:name, :email, :gid, :avatar, 'user', 0)
        ");
        $stmtInsert->execute([
            'name'   => $name,
            'email'  => $email,
            'gid'    => $googleId,
            'avatar' => $avatar
        ]);

        $newId = (int)$pdo->lastInsertId();
        $stmtFetch = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmtFetch->execute(['id' => $newId]);
        $newUser = $stmtFetch->fetch();

        loginUser($newUser);
        $_SESSION['flash_success'] = "Welcome to EstateCraft, " . htmlspecialchars($name) . "! Please verify your mobile number to complete your profile.";
        header("Location: " . BASE_URL . "/auth/verify_phone.php");
        exit;
    }

    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}

header("Location: " . BASE_URL . "/auth/login.php");
exit;
