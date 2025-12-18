<?php
/**
 * Wespee Web Configuration
 *
 * This file contains all configuration constants for the Wespee web interface
 */

// API Configuration
define('API_BASE_URL', 'https://recorder-wespee-api.bicentsafe.com/api/v1');
define('API_CLIENT_ID', 'a2f43234317d8ebb28a810660d4ba73ed6d42adb7f1c75820e693cd88595a03d');
define('API_CLIENT_SECRET', 'df809470293b2ca6d5d135560deb6b3e5fbb000e160cf6d8cffe35acf657af10b4f792d5a2bc8e62ed0919d0164b183c038da34969cf256ca20154b6936950c9');

// App Store URLs (to be configured)
define('IOS_APP_STORE_URL', ''); // TODO: Add iOS App Store URL
define('ANDROID_PLAY_STORE_URL', ''); // TODO: Add Android Play Store URL

// Website Configuration
define('SITE_NAME', 'Wespee');
define('SITE_URL', 'https://wespee.me');
define('SITE_DESCRIPTION', 'Envoyez et recevez de l\'argent facilement avec Wespee');

// Social Media Links
define('SOCIAL_YOUTUBE', 'https://youtube.com/@wespee');
define('SOCIAL_TWITTER', 'https://twitter.com/wespee');
define('SOCIAL_FACEBOOK', 'https://facebook.com/wespee');
define('SOCIAL_LINKEDIN', 'https://linkedin.com/company/wespee');
define('SOCIAL_INSTAGRAM', 'https://instagram.com/wespee');
define('SOCIAL_TIKTOK', 'https://tiktok.com/@wespee');

// Legal Links
define('PRIVACY_POLICY_URL', SITE_URL . '/privacy');
define('TERMS_OF_SERVICE_URL', SITE_URL . '/terms');

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
