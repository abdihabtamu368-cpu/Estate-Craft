<?php
/**
 * OTP Phone Verification Service
 * Supports Twilio REST API and Local Development/Demo Simulation
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Sends or simulates an OTP verification code to the given user ID and phone
 */
function sendPhoneOtp(int $userId, string $phoneNumber): array {
    $pdo = getDB();
    
    // Generate a secure 6-digit numeric code
    $otp = (string)random_int(100000, 999999);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Save code and expiry to database
    $stmt = $pdo->prepare("UPDATE users SET phone = :phone, otp_code = :otp, otp_expires_at = :expires WHERE id = :id");
    $stmt->execute([
        'phone'   => $phoneNumber,
        'otp'     => $otp,
        'expires' => $expiresAt,
        'id'      => $userId
    ]);

    // Check if real SMS gateway is enabled and configured
    if (isTwilioReady()) {
        $smsResult = sendTwilioSms($phoneNumber, "Your " . SITE_NAME . " verification code is: {$otp}. Valid for 10 minutes.");
        if ($smsResult['success']) {
            return [
                'success' => true,
                'mode'    => 'twilio',
                'message' => 'Verification code sent via SMS to ' . htmlspecialchars($phoneNumber) . '.'
            ];
        } else {
            return [
                'success' => false,
                'mode'    => 'twilio',
                'message' => 'Failed to send SMS via Twilio: ' . htmlspecialchars($smsResult['error'])
            ];
        }
    }

    // Default Local / Development Simulation
    $_SESSION['dev_otp_code'] = $otp;
    $_SESSION['dev_otp_phone'] = $phoneNumber;

    return [
        'success'  => true,
        'mode'     => 'dev',
        'otp'      => $otp,
        'message'  => 'Development mode: Verification code generated.'
    ];
}

/**
 * Validates the user submitted OTP code
 */
function verifyUserOtp(int $userId, string $code): array {
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT otp_code, otp_expires_at, phone FROM users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || empty($user['otp_code'])) {
        return ['success' => false, 'message' => 'No verification code was requested or active.'];
    }

    if (strtotime($user['otp_expires_at']) < time()) {
        return ['success' => false, 'message' => 'Verification code has expired. Please request a new one.'];
    }

    if (trim($user['otp_code']) !== trim($code)) {
        return ['success' => false, 'message' => 'Invalid verification code. Please check and try again.'];
    }

    // Mark verified and clear OTP
    $stmtUpdate = $pdo->prepare("UPDATE users SET phone_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = :id");
    $stmtUpdate->execute(['id' => $userId]);

    // Clear dev session
    unset($_SESSION['dev_otp_code'], $_SESSION['dev_otp_phone']);

    // Refresh session data
    if (isset($_SESSION['user_data'])) {
        $_SESSION['user_data']['phone_verified'] = 1;
        $_SESSION['user_data']['phone'] = $user['phone'];
    }

    return ['success' => true, 'message' => 'Phone number successfully verified!'];
}

/**
 * cURL helper for Twilio SMS REST API
 */
function sendTwilioSms(string $to, string $body): array {
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_SID . '/Messages.json';
    
    $data = [
        'From' => TWILIO_FROM,
        'To'   => $to,
        'Body' => $body
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID . ':' . TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['success' => false, 'error' => $curlErr];
    }

    $json = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => true, 'sid' => $json['sid'] ?? ''];
    }

    return ['success' => false, 'error' => $json['message'] ?? 'Twilio API Error (' . $httpCode . ')'];
}
