<?php
/**
 * Wespee 404 Error Page
 * Displayed when a user is not found or an error occurs
 */

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'");

require_once 'config.php';

// Set 404 header
http_response_code(404);

$errorMessage = ERROR_USER_NOT_FOUND;
$username = isset($_GET['username']) ? htmlspecialchars($_GET['username']) : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateur non trouvé - <?php echo SITE_NAME; ?></title>

<?php
// Get username from URL for OG image
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$urlUsername = trim(parse_url($requestUri, PHP_URL_PATH), '/');
$urlUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $urlUsername);
if (empty($urlUsername)) {
    $urlUsername = 'unknown';
}
$ogImage = SITE_URL . '/og-image/' . $urlUsername;
$pageTitle = "Utilisateur n'existe pas - " . SITE_NAME;
$pageDescription = "Cet utilisateur n'existe pas sur " . SITE_NAME;
$pageUrl = SITE_URL . '/' . $urlUsername;
?>
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo $pageDescription; ?>">
    <meta name="robots" content="noindex, nofollow">

    <!-- Open Graph / Facebook / WhatsApp / LinkedIn -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    <meta property="og:url" content="<?php echo $pageUrl; ?>">
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo $pageDescription; ?>">
    <meta property="og:image" content="<?php echo $ogImage; ?>">
    <meta property="og:image:secure_url" content="<?php echo $ogImage; ?>">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="2400">
    <meta property="og:image:height" content="1260">
    <meta property="og:image:alt" content="Utilisateur non trouvé sur <?php echo SITE_NAME; ?>">
    <meta property="og:locale" content="fr_FR">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@wespee">
    <meta name="twitter:url" content="<?php echo $pageUrl; ?>">
    <meta name="twitter:title" content="<?php echo $pageTitle; ?>">
    <meta name="twitter:description" content="<?php echo $pageDescription; ?>">
    <meta name="twitter:image" content="<?php echo $ogImage; ?>">
    <meta name="twitter:image:alt" content="Utilisateur non trouvé sur <?php echo SITE_NAME; ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <link rel="apple-touch-icon" href="/assets/images/favicon.png">

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
<body class="bg-white min-h-screen flex flex-col items-center justify-center px-4 py-8">
    <!-- Error Content -->
    <div class="w-full max-w-md text-center">
        <!-- Error Icon -->
        <div class="mb-12 flex justify-center">
            <img src="/assets/images/Group.svg" alt="Ghost icon" class="w-24 h-24 opacity-40">
        </div>

        <!-- Error Message -->
        <h1 class="text-3xl font-bold text-gray-900 mb-6 leading-tight">Oups ! Cet utilisateur<br>n'existe pas</h1>

        <p class="text-base text-gray-600 mb-24 px-8 leading-relaxed">
            Vérifie-le et assure-toi que l'identifiant est valide avant de réessayer.
        </p>

        <!-- Action Button -->
        <div class="px-8">
            <a href="<?php echo SITE_URL; ?>" class="block w-full py-4 bg-gray-100 text-gray-900 font-semibold rounded-full hover:bg-gray-200 transition-colors text-base">
                Retour à l'accueil
            </a>
        </div>
    </div>

</body>
</html>
