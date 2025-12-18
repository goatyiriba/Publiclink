<?php
/**
 * Wespee 404 Error Page
 * Displayed when a user is not found or an error occurs
 */

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

    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
    <meta name="robots" content="noindex, nofollow">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">

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
