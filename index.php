<?php
/**
 * Wespee Profile Web Interface
 * Main entry point for user profile pages
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

/**
 * Clean username by removing @ prefix if present
 */
function cleanUsername($username) {
    $cleaned = trim($username);
    return (strpos($cleaned, '@') === 0) ? substr($cleaned, 1) : $cleaned;
}

/**
 * Validate username format (alphanumeric and underscore only)
 */
function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]+$/', $username);
}

/**
 * Generate user initials from username
 */
function getInitials($username) {
    // Get first 2 characters of username in uppercase
    return strtoupper(substr($username, 0, 2));
}

/**
 * Fetch user profile from API
 */
function getUserProfile($username) {
    $cleanedUsername = cleanUsername($username);

    // Prepare API request - New route format
    $url = API_BASE_URL . '/public/users/' . urlencode($cleanedUsername);

    // Debug: Log API URL
    error_log("API Request URL: " . $url);

    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-public-client-id: ' . API_CLIENT_ID,
        'x-public-client-secret: ' . API_CLIENT_SECRET,
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    // SSL configuration for Windows
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for testing
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    // curl_close() is deprecated since PHP 8.5 (no-op since PHP 8.0)

    // Debug: Log API response
    error_log("API Response Code: " . $httpCode);
    error_log("API Response Body: " . $response);
    if ($error) {
        error_log("cURL Error: " . $error);
    }

    // Handle errors
    if ($error) {
        return ['error' => ERROR_NETWORK, 'details' => $error];
    }

    if ($httpCode !== 200) {
        return ['error' => ERROR_USER_NOT_FOUND, 'code' => $httpCode];
    }

    // Parse response - New API structure
    $result = json_decode($response, true);

    if (!$result) {
        return ['error' => ERROR_NETWORK];
    }

    // Check for error message from API
    if (isset($result['errorMessage']) && $result['errorMessage'] !== null) {
        return ['error' => $result['errorMessage']];
    }

    // Check if user is available (isAvailable: true means user exists)
    if (!isset($result['isAvailable']) || $result['isAvailable'] !== true) {
        return ['error' => ERROR_USER_NOT_FOUND];
    }

    // Return user data with new structure
    return [
        'success' => true,
        'username' => $result['username'] ?? $cleanedUsername,
        'firstName' => $result['firstName'] ?? '',
        'lastName' => $result['lastName'] ?? '',
        'profilePictureUrl' => $result['profilePictureUrl'] ?? '',
        'publicProfileUrl' => $result['publicProfileUrl'] ?? '',
        'qrCode' => $result['qrCode'] ?? '',
        'deepLink' => $result['deepLink'] ?? '',
    ];
}

// Get username from URL
$username = isset($_GET['username']) ? $_GET['username'] : '';

// If no username, redirect to main site or show homepage
if (empty($username)) {
    header('Location: ' . SITE_URL);
    exit;
}

// Validate username format
if (!isValidUsername($username)) {
    include '404.php';
    exit;
}

// Fetch user profile
$profile = getUserProfile($username);

// Handle errors
if (isset($profile['error'])) {
    include '404.php';
    exit;
}

// Prepare data for frontend
$username = htmlspecialchars($profile['username']);
$firstName = htmlspecialchars($profile['firstName'] ?? '');
$lastName = htmlspecialchars($profile['lastName'] ?? '');
// Build display name from firstName + lastName, fallback to username
$displayName = trim($firstName . ' ' . $lastName);
if (empty($displayName)) {
    $displayName = ucfirst($username);
}
$profilePictureUrl = htmlspecialchars($profile['profilePictureUrl']);
$initials = getInitials($username);
$profileUrl = SITE_URL . '/' . $username;
$qrCodeData = $profile['qrCode'] ?? ''; // QR code is now provided by API as base64
$deepLink = 'https://asset.wespee.me/' . $username; // Deep link format

// Meta tags for SEO and social sharing
$pageTitle = 'Payer ' . $displayName . ' avec ' . SITE_NAME;
$pageDescription = 'Envoyez de l\'argent à ' . $displayName . ' instantanément avec ' . SITE_NAME;
$ogImageParams = !empty($profilePictureUrl) ? '?avatar=' . urlencode($profilePictureUrl) : '';
$ogImage = SITE_URL . '/og-image/' . $username . $ogImageParams;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- SEO Meta Tags -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="<?php echo $pageDescription; ?>">
    <meta name="author" content="<?php echo SITE_NAME; ?>">

    <!-- Open Graph / Facebook / WhatsApp / LinkedIn -->
    <meta property="og:type" content="profile">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    <meta property="og:url" content="<?php echo $profileUrl; ?>">
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo $pageDescription; ?>">
    <meta property="og:image" content="<?php echo $ogImage; ?>">
    <meta property="og:image:secure_url" content="<?php echo $ogImage; ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="2400">
    <meta property="og:image:height" content="1260">
    <meta property="og:image:alt" content="Profil <?php echo SITE_NAME; ?> de @<?php echo $username; ?>">
    <meta property="og:locale" content="fr_FR">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@wespee">
    <meta name="twitter:url" content="<?php echo $profileUrl; ?>">
    <meta name="twitter:title" content="<?php echo $pageTitle; ?>">
    <meta name="twitter:description" content="<?php echo $pageDescription; ?>">
    <meta name="twitter:image" content="<?php echo $ogImage; ?>">
    <meta name="twitter:image:alt" content="Profil <?php echo SITE_NAME; ?> de @<?php echo $username; ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <link rel="apple-touch-icon" href="/assets/images/favicon.png">
    <meta property="og:logo" content="<?php echo SITE_URL; ?>/assets/images/favicon.png">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Styles -->
    <link rel="stylesheet" href="/assets/css/styles.css">

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'wespee-green': '<?php echo COLOR_PRIMARY; ?>',
                        'wespee-green-light': '<?php echo COLOR_PRIMARY_LIGHT; ?>',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Main Container -->
    <div class="flex flex-col items-center px-4 pt-8 pb-32">
        <!-- Logo -->
        <div class="mb-8">
            <img src="/assets/images/wespee.svg" alt="Wespee" class="h-10">
        </div>

        <!-- Profile Card -->
        <div class="w-full max-w-md">
            <!-- Profile Avatar -->
            <div class="flex flex-col items-center mb-6">
                <?php if (!empty($profilePictureUrl)): ?>
                    <div class="w-32 h-32 rounded-full overflow-hidden bg-gray-200 mb-4">
                        <img src="<?php echo $profilePictureUrl; ?>" alt="<?php echo $displayName; ?>" class="w-full h-full object-cover">
                    </div>
                <?php else: ?>
                    <div class="w-32 h-32 rounded-full bg-gray-200 flex items-center justify-center mb-4">
                        <span class="text-4xl font-semibold text-gray-600"><?php echo $initials; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Name -->
                <h1 class="text-2xl font-semibold text-gray-900 mb-2"><?php echo $displayName; ?></h1>

                <!-- Username Badge -->
                <div class="bg-wespee-green-light px-4 py-1 rounded-full">
                    <span class="text-gray-800 font-medium">@<?php echo $username; ?></span>
                </div>
            </div>

            <!-- Action Buttons Card -->
            <div class="bg-white rounded-[30px] p-5 shadow-sm mb-6">
                <div class="grid grid-cols-3 gap-2">
                    <!-- Copy Link Button -->
                    <button onclick="copyProfileLink()" class="flex flex-col items-center justify-center gap-3 py-4">
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center">
                            <img src="/assets/images/Vector (1).svg" alt="Link icon" class="w-6 h-6">
                        </div>
                        <span class="text-sm text-center font-medium text-gray-900 leading-tight">Copier le lien<br>du profil</span>
                    </button>

                    <!-- Share Button -->
                    <button onclick="shareProfile()" class="flex flex-col items-center justify-center gap-3 py-4">
                        <div class="w-12 h-12 rounded-full bg-wespee-green flex items-center justify-center">
                            <img src="/assets/images/share-08.svg" alt="Share icon" class="w-6 h-6">
                        </div>
                        <span class="text-sm text-center font-medium text-gray-900 leading-tight">Partager le<br>profil</span>
                    </button>

                    <!-- QR Code Button -->
                    <button onclick="showQRCode()" class="flex flex-col items-center justify-center gap-3 py-4">
                        <div class="w-12 h-12 rounded-full bg-gray-200 bg-opacity-50 flex items-center justify-center">
                            <img src="/assets/images/qr-code 1.svg" alt="QR code icon" class="w-5 h-5">
                        </div>
                        <span class="text-sm text-center font-medium text-gray-900 leading-tight">Afficher le QR<br>code</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fixed Pay Now Button -->
    <div class="fixed bottom-0 left-0 right-0 bg-white px-4 py-4 shadow-lg z-40">
        <button onclick="payNow()" class="w-full max-w-md mx-auto block py-4 bg-wespee-green text-gray-900 font-semibold rounded-full hover:opacity-90 transition-opacity text-lg">
            Payer maintenant
        </button>
    </div>

    <!-- Footer -->
    <footer class="py-8 px-4 mt-24 pb-24">
        <div class="max-w-4xl mx-auto">
            <!-- Footer Logo -->
            <div class="flex justify-center mb-4">
                <img src="/assets/images/wespee_black.svg" alt="Wespee">
            </div>

            <!-- Footer Links -->
            <div class="flex flex-col items-center gap-2 mb-3">
                <a href="<?php echo PRIVACY_POLICY_URL; ?>" class="text-sm text-gray-700 hover:text-gray-900 transition-colors">Politique de confidentialité</a>
                <a href="<?php echo TERMS_OF_SERVICE_URL; ?>" class="text-sm text-gray-700 hover:text-gray-900 transition-colors">Conditions d'utilisation du service</a>
            </div>

            <!-- Copyright -->
            <p class="text-center text-xs text-gray-400 mb-6">Tous droits réservés ©2025</p>

            <!-- Social Media Icons -->
            <div class="flex justify-center gap-5 pb-4">
                <a href="<?php echo SOCIAL_YOUTUBE; ?>" target="_blank" rel="noopener noreferrer" class="text-gray-800 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                </a>
                <a href="<?php echo SOCIAL_TWITTER; ?>" target="_blank" rel="noopener noreferrer" class="text-gray-800 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                </a>
                <a href="<?php echo SOCIAL_FACEBOOK; ?>" target="_blank" rel="noopener noreferrer" class="text-gray-800 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
                <a href="<?php echo SOCIAL_LINKEDIN; ?>" target="_blank" rel="noopener noreferrer" class="text-gray-800 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                </a>
                <a href="<?php echo SOCIAL_INSTAGRAM; ?>" target="_blank" rel="noopener noreferrer" class="text-gray-800 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                </a>
                <a href="<?php echo SOCIAL_TIKTOK; ?>" target="_blank" rel="noopener noreferrer" class="text-gray-800 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                </a>
            </div>
        </div>
    </footer>

    <!-- QR Code Bottom Sheet -->
    <div id="qrModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50" onclick="closeQRModal(event)">
        <div id="qrBottomSheet" class="fixed bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-2xl transform translate-y-full transition-transform duration-300 ease-out" onclick="event.stopPropagation()">
            <div class="p-6 pb-8">
                <!-- Title -->
                <h2 class="text-xl font-semibold text-gray-900 text-center mb-8">Qr code</h2>

                <!-- QR Code Container -->
                <div class="flex justify-center mb-8">
                    <div id="qrcode" class="bg-white"></div>
                </div>

                <!-- Close Button -->
                <button onclick="closeQRModal()" class="w-full py-4 bg-gray-100 text-gray-900 font-semibold rounded-full hover:bg-gray-200 transition-colors">
                    Fermer
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="hidden fixed bottom-8 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white px-6 py-3 rounded-full shadow-lg z-50 transition-opacity">
        <span id="toastMessage">Message</span>
    </div>

    <!-- QR Code Library (loaded on demand) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- Profile Data -->
    <script>
        const profileData = {
            username: '<?php echo $username; ?>',
            fullName: '<?php echo addslashes($displayName); ?>',
            profileUrl: '<?php echo $profileUrl; ?>',
            qrCodeData: '<?php echo $qrCodeData; ?>',
            iosAppStoreUrl: '<?php echo IOS_APP_STORE_URL; ?>',
            androidPlayStoreUrl: '<?php echo ANDROID_PLAY_STORE_URL; ?>'
        };
    </script>

    <!-- Main Application Script -->
    <script src="/assets/js/app.js"></script>
</body>
</html>
