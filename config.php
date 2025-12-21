<?php
/**
 * Wespee Web Configuration
 *
 * This file contains all configuration constants for the Wespee web interface
 */

// Load .env file if it exists (for non-Replit hosting)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// API Configuration - Load from environment variables
define('API_BASE_URL', getenv('API_BASE_URL') ?: 'https://recorder-wespee-api.bicentsafe.com/api/v1');
define('API_CLIENT_ID', getenv('x_public_client_id') ?: getenv('API_CLIENT_ID') ?: '');
define('API_CLIENT_SECRET', getenv('x_public_client_secret') ?: getenv('API_CLIENT_SECRET') ?: '');

// App Store URLs (to be configured)
define('IOS_APP_STORE_URL', ''); // TODO: Add iOS App Store URL
define('ANDROID_PLAY_STORE_URL', ''); // TODO: Add Android Play Store URL

// Website Configuration
define('SITE_NAME', 'Wespee');
define('SITE_URL', 'https://wespee.app');
define('SITE_DESCRIPTION', 'Envoyez et recevez de l\'argent facilement avec Wespee');

// Social Media Links
define('SOCIAL_YOUTUBE', 'https://youtube.com/@wespee');
define('SOCIAL_TWITTER', 'https://twitter.com/wespee');
define('SOCIAL_FACEBOOK', 'https://facebook.com/wespee');
define('SOCIAL_LINKEDIN', 'https://linkedin.com/company/wespee');
define('SOCIAL_INSTAGRAM', 'https://instagram.com/wespee');
define('SOCIAL_TIKTOK', 'https://tiktok.com/@wespee');

// Legal Links
define('PRIVACY_POLICY_URL', 'https://wespee.app/values/politique');
define('TERMS_OF_SERVICE_URL', 'https://wespee.app/values/termes');

// Branding Colors
define('COLOR_PRIMARY', '#06D432');
define('COLOR_PRIMARY_LIGHT', '#D4F7E0');
define('COLOR_BACKGROUND', '#FAFAFA');
define('COLOR_TEXT_DARK', '#1A1A1A');
define('COLOR_TEXT_GRAY', '#6B7280');

// Error Messages
define('ERROR_USER_NOT_FOUND', 'Cet utilisateur n\'existe pas');
define('ERROR_NETWORK', 'Erreur de connexion. Veuillez réessayer.');
define('ERROR_INVALID_USERNAME', 'Nom d\'utilisateur invalide');

// Cache Configuration (in seconds)
define('CACHE_DURATION', 300); // 5 minutes
define('CACHE_ENABLED', false); // Set to true to enable caching
