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

$response = @file_get_contents($apiUrl, false, $ctx);
if ($response !== false) {
    $data = json_decode($response, true);
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

$patternSize = 280;
$patternSpacing = 360;
$patternAlpha = imagecolorallocatealpha($canvas, 3, 180, 40, 100);
for ($row = 0; $row < 4; $row++) {
    $offsetX = ($row % 2) * ($patternSpacing / 2);
    for ($col = 0; $col < 8; $col++) {
        $x = $offsetX + ($col * $patternSpacing);
        $y = ($row * $patternSpacing);
        
        imagesetthickness($canvas, 2);
        
        $cx = $x + $patternSize / 2;
        $cy = $y + $patternSize / 2;
        $radius = $patternSize / 2 - 40;
        
        imageellipse($canvas, $cx, $cy, $radius * 2, $radius * 2, $patternAlpha);
        
        $innerRadius = $radius * 0.4;
        imageellipse($canvas, $cx, $cy, $innerRadius * 2, $innerRadius * 2, $patternAlpha);
    }
}

$avatarSize = 480;
$avatarX = ($ogWidth - $avatarSize) / 2;
$avatarY = 260;

$borderSize = 14;
imagefilledellipse($canvas, (int)($avatarX + $avatarSize / 2), (int)($avatarY + $avatarSize / 2), $avatarSize + $borderSize * 2, $avatarSize + $borderSize * 2, $white);

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
    $fontSize = 120;
    
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

// Display full name (Prénom Nom) below avatar
$nameFontSize = 90;
$nameY = 860;
$displayName = !empty($fullName) ? $fullName : $username;

$fontPath = __DIR__ . '/assets/fonts/Athletics-Bold.otf';
if (!file_exists($fontPath)) {
    $fontPath = __DIR__ . '/assets/fonts/Athletics-Medium.otf';
}

$blackText = imagecolorallocate($canvas, 0, 0, 0);
if (file_exists($fontPath)) {
    $bbox = imagettfbbox($nameFontSize, 0, $fontPath, $displayName);
    $nameWidth = $bbox[2] - $bbox[0];
    $nameX = (int)(($ogWidth - $nameWidth) / 2 - $bbox[0]);
    imagettftext($canvas, $nameFontSize, 0, $nameX, $nameY, $blackText, $fontPath, $displayName);
} else {
    $fontBuiltin = 5;
    $nameWidth = imagefontwidth($fontBuiltin) * strlen($displayName);
    $nameX = (int)(($ogWidth - $nameWidth) / 2);
    imagestring($canvas, $fontBuiltin, $nameX, $nameY - 20, $displayName, $blackText);
}

// Badge with @username
$userBadgeText = '@' . $username;
$userBadgeFontSize = 52;
$userBadgePaddingX = 40;
$userBadgePaddingY = 22;
$userBadgeY = 920;

if (file_exists($fontPath)) {
    $bbox = imagettfbbox($userBadgeFontSize, 0, $fontPath, $userBadgeText);
    $textWidth = $bbox[2] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[7];
} else {
    $textWidth = strlen($userBadgeText) * 20;
    $textHeight = 30;
}

$userBadgeWidth = $textWidth + $userBadgePaddingX * 2;
$userBadgeHeight = $textHeight + $userBadgePaddingY * 2;
$userBadgeX = ($ogWidth - $userBadgeWidth) / 2;

$userBadgeRadius = $userBadgeHeight / 2;

imagefilledellipse($canvas, (int)($userBadgeX + $userBadgeRadius), (int)($userBadgeY + $userBadgeRadius), (int)($userBadgeRadius * 2), (int)($userBadgeRadius * 2), $lightGreen);
imagefilledellipse($canvas, (int)($userBadgeX + $userBadgeWidth - $userBadgeRadius), (int)($userBadgeY + $userBadgeRadius), (int)($userBadgeRadius * 2), (int)($userBadgeRadius * 2), $lightGreen);
imagefilledrectangle($canvas, (int)($userBadgeX + $userBadgeRadius), (int)$userBadgeY, (int)($userBadgeX + $userBadgeWidth - $userBadgeRadius), (int)($userBadgeY + $userBadgeHeight), $lightGreen);

if (file_exists($fontPath)) {
    $textX = (int)($userBadgeX + $userBadgePaddingX);
    $textY = (int)($userBadgeY + $userBadgePaddingY + $textHeight - 5);
    imagettftext($canvas, $userBadgeFontSize, 0, $textX, $textY, $darkText, $fontPath, $userBadgeText);
} else {
    $fontBuiltin = 5;
    $textX = (int)(($ogWidth - $textWidth) / 2);
    $textY = (int)($userBadgeY + $userBadgePaddingY);
    imagestring($canvas, $fontBuiltin, $textX, $textY, $userBadgeText, $darkText);
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

imagepng($canvas, null, 6);
imagedestroy($canvas);
