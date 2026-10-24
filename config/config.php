<?php
/**
 * Application Configuration
 * Real Estate Portal (Buy, Sell & Rent)
 */

// Error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------------
// Database Settings (Standard WampServer defaults)
// -------------------------------------------------------------
// In WampServer default:
// User: root
// Password: (empty string)
// Port: 3306 (or 3308 if MySQL alternate port in Wamp)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306'); 
define('DB_NAME', 'realestate_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// -------------------------------------------------------------
// Site Identity & Currency
// -------------------------------------------------------------
define('SITE_NAME', 'EstateCraft Ethiopia');
define('SITE_TAGLINE', 'Find, Buy, Sell & Rent Exceptional Homes Across Ethiopia');
define('CURRENCY_SYMBOL', 'ETB ');

// -------------------------------------------------------------
// Dynamic Base URL Detection
// Works seamlessly whether placed in c:\wamp64\www\buy-and-sell\
// or run directly via PHP development server
// -------------------------------------------------------------
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Find root of the project by stripping subdirectories if inside auth/, properties/, user/, api/, admin/
$cleanDir = preg_replace('#/(auth|properties|user|api|config|includes|admin)$#i', '', $scriptDir);
$cleanDir = rtrim($cleanDir, '/');

// Properly encode path segments (e.g. "buy and sell" -> "buy%20and%20sell") for valid RFC URLs
$encodedSegments = array_map('rawurlencode', explode('/', $cleanDir));
$cleanDirEncoded = implode('/', $encodedSegments);

define('BASE_URL', $protocol . $host . $cleanDirEncoded);
define('ROOT_PATH', realpath(__DIR__ . '/..'));
define('UPLOAD_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'properties' . DIRECTORY_SEPARATOR);
define('UPLOAD_URL', BASE_URL . '/uploads/properties/');

// -------------------------------------------------------------
// File Upload Security Constraints
// -------------------------------------------------------------
define('MAX_FILE_SIZE', 6 * 1024 * 1024); // 6MB limit per image
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// -------------------------------------------------------------
// Google Sign-In (OAuth 2.0)
// Replace with your credentials from Google Cloud Console:
// https://console.cloud.google.com/apis/credentials
// -------------------------------------------------------------
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', BASE_URL . '/auth/google_callback.php');

// Helper to check if Google Auth is configured
function isGoogleAuthReady(): bool {
    return GOOGLE_CLIENT_ID !== 'YOUR_GOOGLE_CLIENT_ID_HERE.apps.googleusercontent.com' 
        && !empty(GOOGLE_CLIENT_ID) 
        && !empty(GOOGLE_CLIENT_SECRET);
}

// -------------------------------------------------------------
// SMS OTP Service Configuration (e.g., Twilio)
// Mode: 'dev' (displays OTP in alert banner for local testing)
// Mode: 'twilio' (sends real SMS using Twilio REST API)
// -------------------------------------------------------------
define('SMS_MODE', 'dev'); // 'dev' or 'twilio'
define('TWILIO_SID', 'YOUR_TWILIO_ACCOUNT_SID');
define('TWILIO_TOKEN', 'YOUR_TWILIO_AUTH_TOKEN');
define('TWILIO_FROM', '+1234567890');

function isTwilioReady(): bool {
    return SMS_MODE === 'twilio' 
        && TWILIO_SID !== 'YOUR_TWILIO_ACCOUNT_SID' 
        && !empty(TWILIO_SID);
}
