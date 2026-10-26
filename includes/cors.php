<?php
/**
 * Cross-Origin Resource Sharing (CORS) & Preflight Handler
 * Allows secure requests from Vercel deployments (*.vercel.app),
 * local development environments, and configured production domains.
 */

// Retrieve requesting origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Default whitelist & pattern matching
$isAllowedOrigin = false;

if (!empty($origin)) {
    // 1. Check localhost and 127.0.0.1 on any port (Vite, Next.js, Live Server, etc.)
    if (preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#i', $origin)) {
        $isAllowedOrigin = true;
    }
    // 2. Check Vercel deployments (production domain, branch previews, custom subdomains)
    elseif (preg_match('#^https://[a-z0-9_-]+\.vercel\.app$#i', $origin)) {
        $isAllowedOrigin = true;
    }
    // 3. Check GitHub Pages
    elseif (preg_match('#^https://[a-z0-9_-]+\.github\.io$#i', $origin)) {
        $isAllowedOrigin = true;
    }
    // 4. Check custom environment whitelist if specified
    elseif (!empty($_ENV['ALLOWED_ORIGINS'])) {
        $customOrigins = array_map('trim', explode(',', $_ENV['ALLOWED_ORIGINS']));
        if (in_array($origin, $customOrigins, true)) {
            $isAllowedOrigin = true;
        }
    }
    // 5. Check config constant if defined
    elseif (defined('ALLOWED_ORIGINS') && is_array(ALLOWED_ORIGINS)) {
        if (in_array($origin, ALLOWED_ORIGINS, true)) {
            $isAllowedOrigin = true;
        }
    }
}

// Set CORS response headers if origin is validated
if ($isAllowedOrigin) {
    header("Access-Control-Allow-Origin: {$origin}");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
    header("Access-Control-Max-Age: 86400"); // 24 hours preflight cache
}

// Handle preflight OPTIONS request immediately
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
