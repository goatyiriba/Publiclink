<?php
/**
 * Dynamic OG Image Generator for Wespee Profiles
 * Generates social sharing preview images for user profiles
 */

// Production: disable error display
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Security headers for image endpoint
header('X-Content-Type-Options: nosniff');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

require_once 'config.php';

$ogWidth = 2400;
$ogHeight = 1260;

$username = isset($_GET['username']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['username']) : '';

if (empty($username)) {
    http_response_code(400);
    exit('Username required');
}

$avatarUrl = isset($_GET['avatar']) ? $_GET['avatar'] : '';
$initials = strtoupper(substr($username, 0, 2));
$fullName = '';

// Always call API to get firstName/lastName for the OG image
$apiUrl = API_BASE_URL . '/public/users/' . urlencode($username);
$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Accept: application/json',
            'x-public-client-id: ' . API_CLIENT_ID,
            'x-public-client-secret: ' . API_CLIENT_SECRET,
        ],
        'timeout' => 5,
    ],
    'ssl' => [
        'verify_peer' => true,
    ],
]);

$userExists = false;
$response = @file_get_contents($apiUrl, false, $ctx);
if ($response !== false) {
    $data = json_decode($response, true);
    // Check if user exists (API returns valid user data)
    if (isset($data['username']) || isset($data['firstName']) || isset($data['id'])) {
        $userExists = true;
    }
    // Only use API avatar if none provided via URL parameter
    if (empty($avatarUrl) && isset($data['profilePictureUrl']) && !empty($data['profilePictureUrl'])) {
        $avatarUrl = $data['profilePictureUrl'];
    }
    // Always get firstName/lastName for display
    if (isset($data['firstName']) || isset($data['lastName'])) {
        $firstName = $data['firstName'] ?? '';
        $lastName = $data['lastName'] ?? '';
        $fullName = trim($firstName . ' ' . $lastName);
        if (!empty($firstName) && !empty($lastName)) {
            $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
        } elseif (!empty($firstName)) {
            $initials = strtoupper(substr($firstName, 0, 2));
        }
    }
}

function isValidAvatarUrl($url) {
    if (empty($url)) {
        return false;
    }
    
    $parsed = parse_url($url);
    if ($parsed === false || !isset($parsed['scheme']) || !isset($parsed['host'])) {
        return false;
    }
    
    if ($parsed['scheme'] !== 'https') {
        return false;
    }
    
    $allowedDomains = [
        'recorder-wespee-api.bicentsafe.com',
        'wespee.me',
        'asset.wespee.me',
        'bicentsafe.com',
        'wespeestoragev0.blob.core.windows.net',
    ];
    
    $host = strtolower($parsed['host']);
    foreach ($allowedDomains as $domain) {
        if ($host === $domain || str_ends_with($host, '.' . $domain)) {
            return true;
        }
    }
    
    return false;
}

$validatedAvatarUrl = isValidAvatarUrl($avatarUrl) ? $avatarUrl : '';

$canvas = imagecreatetruecolor($ogWidth, $ogHeight);
imagesavealpha($canvas, true);

$bgGreen = imagecolorallocate($canvas, 6, 212, 50);
$patternGreen = imagecolorallocate($canvas, 3, 180, 40);
$white = imagecolorallocate($canvas, 255, 255, 255);
$lightGreen = imagecolorallocate($canvas, 212, 247, 224);
$darkText = imagecolorallocate($canvas, 26, 26, 26);
$avatarBg = imagecolorallocate($canvas, 229, 231, 235);

imagefill($canvas, 0, 0, $bgGreen);

// Draw Wespee watermark pattern in background
$watermarkText = 'Wespee';
$watermarkFontSize = 60;
$watermarkFontPath = __DIR__ . '/assets/fonts/Athletics-Bold.otf';
if (!file_exists($watermarkFontPath)) {
    $watermarkFontPath = __DIR__ . '/assets/fonts/Athletics-Medium.otf';
}
$watermarkColor = imagecolorallocatealpha($canvas, 255, 255, 255, 110);

if (file_exists($watermarkFontPath)) {
    $spacingX = 400;
    $spacingY = 200;
    for ($row = 0; $row < 8; $row++) {
        $offsetX = ($row % 2) * ($spacingX / 2);
        for ($col = 0; $col < 8; $col++) {
            $x = $offsetX + ($col * $spacingX) - 100;
            $y = ($row * $spacingY) + 80;
            imagettftext($canvas, $watermarkFontSize, -15, (int)$x, (int)$y, $watermarkColor, $watermarkFontPath, $watermarkText);
        }
    }
}

// If user doesn't exist, show "Utilisateur n'existe pas" message
if (!$userExists) {
    $notFoundText = "Utilisateur n'existe pas";
    $notFoundFontSize = 80;
    $fontPath = __DIR__ . '/assets/fonts/Athletics-Bold.otf';
    if (!file_exists($fontPath)) {
        $fontPath = __DIR__ . '/assets/fonts/Athletics-Medium.otf';
    }
    
    // Draw a ghost/question mark icon (circle with ?)
    $iconSize = 300;
    $iconX = $ogWidth / 2;
    $iconY = 400;
    $iconBg = imagecolorallocate($canvas, 229, 231, 235);
    imagefilledellipse($canvas, (int)$iconX, (int)$iconY, $iconSize, $iconSize, $white);
    imagefilledellipse($canvas, (int)$iconX, (int)$iconY, $iconSize - 20, $iconSize - 20, $iconBg);
    
    // Draw ? in the circle
    $questionColor = imagecolorallocate($canvas, 107, 114, 128);
    if (file_exists($fontPath)) {
        $bbox = imagettfbbox(120, 0, $fontPath, '?');
        $textWidth = $bbox[2] - $bbox[0];
        $textX = (int)($iconX - $textWidth / 2 - $bbox[0]);
        $textY = (int)($iconY + 45);
        imagettftext($canvas, 120, 0, $textX, $textY, $questionColor, $fontPath, '?');
    }
    
    // Draw the message below
    if (file_exists($fontPath)) {
        $bbox = imagettfbbox($notFoundFontSize, 0, $fontPath, $notFoundText);
        $textWidth = $bbox[2] - $bbox[0];
        $textX = (int)(($ogWidth - $textWidth) / 2 - $bbox[0]);
        $textY = 750;
        imagettftext($canvas, $notFoundFontSize, 0, $textX, $textY, $white, $fontPath, $notFoundText);
    }
    
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=3600');
    imagepng($canvas, null, 6);
    imagedestroy($canvas);
    exit;
}

// ============================================
// NEW DESIGN: Avatar with green border + badge on avatar + name + @username pill
// ============================================

$avatarSize = 420;
$avatarX = ($ogWidth - $avatarSize) / 2;
$avatarY = 180;

// Draw GREEN border around avatar (thicker border)
$borderSize = 16;
$borderGreen = imagecolorallocate($canvas, 255, 255, 255);
imagefilledellipse($canvas, (int)($avatarX + $avatarSize / 2), (int)($avatarY + $avatarSize / 2), $avatarSize + $borderSize * 2, $avatarSize + $borderSize * 2, $borderGreen);

$avatarCanvas = imagecreatetruecolor($avatarSize, $avatarSize);
imagesavealpha($avatarCanvas, true);
$transparent = imagecolorallocatealpha($avatarCanvas, 0, 0, 0, 127);
imagefill($avatarCanvas, 0, 0, $transparent);

$avatarLoaded = false;
if (!empty($validatedAvatarUrl)) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'max_redirects' => 0,
            'follow_location' => 0,
        ],
        'ssl' => [
            'verify_peer' => true,
        ],
    ]);
    
    $imageData = @file_get_contents($validatedAvatarUrl, false, $ctx, 0, 2 * 1024 * 1024);
    if ($imageData !== false && strlen($imageData) > 0) {
        $avatarImg = @imagecreatefromstring($imageData);
        if ($avatarImg !== false) {
            $srcW = imagesx($avatarImg);
            $srcH = imagesy($avatarImg);
            
            $tempAvatar = imagecreatetruecolor($avatarSize, $avatarSize);
            imagecopyresampled($tempAvatar, $avatarImg, 0, 0, 0, 0, $avatarSize, $avatarSize, $srcW, $srcH);
            imagedestroy($avatarImg);
            
            for ($x = 0; $x < $avatarSize; $x++) {
                for ($y = 0; $y < $avatarSize; $y++) {
                    $dist = sqrt(pow($x - $avatarSize / 2, 2) + pow($y - $avatarSize / 2, 2));
                    if ($dist <= $avatarSize / 2) {
                        $color = imagecolorat($tempAvatar, $x, $y);
                        imagesetpixel($avatarCanvas, $x, $y, $color);
                    }
                }
            }
            imagedestroy($tempAvatar);
            $avatarLoaded = true;
        }
    }
}

if (!$avatarLoaded) {
    $avatarBgColor = imagecolorallocate($avatarCanvas, 229, 231, 235);
    imagefilledellipse($avatarCanvas, (int)($avatarSize / 2), (int)($avatarSize / 2), $avatarSize, $avatarSize, $avatarBgColor);
    
    $fontPath = __DIR__ . '/assets/fonts/Athletics-Bold.otf';
    if (!file_exists($fontPath)) {
        $fontPath = __DIR__ . '/assets/fonts/Athletics-Medium.otf';
    }
    if (!file_exists($fontPath)) {
        $fontPath = 'arial';
    }
    
    $initialsColor = imagecolorallocate($avatarCanvas, 75, 85, 99);
    $fontSize = 100;
    
    if (file_exists($fontPath)) {
        $bbox = imagettfbbox($fontSize, 0, $fontPath, $initials);
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];
        $textX = (int)(($avatarSize - $textWidth) / 2 - $bbox[0]);
        $textY = (int)(($avatarSize + $textHeight) / 2 - $bbox[1] - 5);
        imagettftext($avatarCanvas, $fontSize, 0, $textX, $textY, $initialsColor, $fontPath, $initials);
    } else {
        $fontBuiltin = 5;
        $textWidth = imagefontwidth($fontBuiltin) * strlen($initials);
        $textHeight = imagefontheight($fontBuiltin);
        $textX = (int)(($avatarSize - $textWidth) / 2);
        $textY = (int)(($avatarSize - $textHeight) / 2);
        imagestring($avatarCanvas, $fontBuiltin, $textX, $textY, $initials, $initialsColor);
    }
}

imagecopy($canvas, $avatarCanvas, (int)$avatarX, (int)$avatarY, 0, 0, $avatarSize, $avatarSize);
imagedestroy($avatarCanvas);

// Draw verified badge at bottom-right of avatar
$badgeSize = 80;
$badgePath = __DIR__ . '/assets/images/verified-badge.png';
if (file_exists($badgePath)) {
    $badgeImg = imagecreatefrompng($badgePath);
    if ($badgeImg !== false) {
        imagesavealpha($badgeImg, true);
        $srcW = imagesx($badgeImg);
        $srcH = imagesy($badgeImg);
        
        // Position badge at bottom-right of avatar circle
        $badgeX = (int)($avatarX + $avatarSize - $badgeSize / 2 - 30);
        $badgeY = (int)($avatarY + $avatarSize - $badgeSize / 2 - 30);
        
        imagecopyresampled($canvas, $badgeImg, $badgeX, $badgeY, 0, 0, $badgeSize, $badgeSize, $srcW, $srcH);
        imagedestroy($badgeImg);
    }
}

// Display full name in white text below avatar
$fontPath = __DIR__ . '/assets/fonts/Athletics-Bold.otf';
if (!file_exists($fontPath)) {
    $fontPath = __DIR__ . '/assets/fonts/Athletics-Medium.otf';
}

$nameY = 720;
$displayFullName = !empty($fullName) ? $fullName : $username;
$nameFontSize = 100;

if (file_exists($fontPath)) {
    $bbox = imagettfbbox($nameFontSize, 0, $fontPath, $displayFullName);
    $nameWidth = $bbox[2] - $bbox[0];
    $nameX = (int)(($ogWidth - $nameWidth) / 2 - $bbox[0]);
    imagettftext($canvas, $nameFontSize, 0, $nameX, $nameY, $white, $fontPath, $displayFullName);
} else {
    $fontBuiltin = 5;
    $nameWidth = imagefontwidth($fontBuiltin) * strlen($displayFullName);
    $nameX = (int)(($ogWidth - $nameWidth) / 2);
    imagestring($canvas, $fontBuiltin, $nameX, $nameY - 20, $displayFullName, $white);
}

// Draw @username in a pill/badge (light green background with green text)
$usernamePillText = '@' . $username;
$pillFontSize = 50;
$pillPaddingX = 50;
$pillPaddingY = 25;
$pillY = 820;

// Pill colors
$pillBgColor = imagecolorallocate($canvas, 220, 252, 231);
$pillTextColor = imagecolorallocate($canvas, 22, 163, 74);

if (file_exists($fontPath)) {
    $bbox = imagettfbbox($pillFontSize, 0, $fontPath, $usernamePillText);
    $textWidth = $bbox[2] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[7];
    
    $pillWidth = $textWidth + $pillPaddingX * 2;
    $pillHeight = $textHeight + $pillPaddingY * 2;
    $pillX = (int)(($ogWidth - $pillWidth) / 2);
    
    // Draw rounded rectangle pill background
    $radius = (int)($pillHeight / 2);
    imagefilledellipse($canvas, $pillX + $radius, $pillY + $radius, $radius * 2, $radius * 2, $pillBgColor);
    imagefilledellipse($canvas, $pillX + $pillWidth - $radius, $pillY + $radius, $radius * 2, $radius * 2, $pillBgColor);
    imagefilledrectangle($canvas, $pillX + $radius, $pillY, $pillX + $pillWidth - $radius, $pillY + $pillHeight, $pillBgColor);
    
    // Draw text inside pill
    $textX = (int)(($ogWidth - $textWidth) / 2 - $bbox[0]);
    $textY = (int)($pillY + $pillPaddingY + $textHeight - $bbox[1] - 5);
    imagettftext($canvas, $pillFontSize, 0, $textX, $textY, $pillTextColor, $fontPath, $usernamePillText);
} else {
    $fontBuiltin = 5;
    $textWidth = imagefontwidth($fontBuiltin) * strlen($usernamePillText);
    $textHeight = imagefontheight($fontBuiltin);
    $pillWidth = $textWidth + 40;
    $pillHeight = $textHeight + 20;
    $pillX = (int)(($ogWidth - $pillWidth) / 2);
    
    imagefilledrectangle($canvas, $pillX, $pillY, $pillX + $pillWidth, $pillY + $pillHeight, $pillBgColor);
    imagestring($canvas, $fontBuiltin, $pillX + 20, $pillY + 10, $usernamePillText, $pillTextColor);
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

imagepng($canvas, null, 6);
imagedestroy($canvas);
