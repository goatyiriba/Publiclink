# Wespee Profile Web Interface

Public web interface for Wespee user profiles, accessible via `https://wespee.me/<username>`. This serves as a fallback when users open deep links in a browser instead of the mobile app.

## Features

- **User Profile Display**: Shows user's name, username, and profile picture
- **Copy Profile Link**: Copy profile URL to clipboard
- **Share Profile**: Native share dialog or clipboard fallback
- **QR Code Generation**: Generates QR code with format `w:<username>`
- **Deep Link Integration**: Attempts to open mobile app, falls back to app stores
- **Responsive Design**: Mobile-first design with Tailwind CSS
- **SEO Optimized**: Meta tags for social sharing (Open Graph, Twitter Cards)
- **Error Handling**: 404 page for non-existent users

## Tech Stack

- **Backend**: PHP 8.0+
- **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript
- **QR Code**: QRCode.js library
- **API**: Wespee REST API

## File Structure

```
wespee-web/
├── index.php              # Main entry point
├── 404.php                # Error page
├── config.php             # Configuration constants
├── .htaccess              # URL rewriting
├── assets/
│   ├── css/
│   │   └── styles.css     # Custom styles
│   └── js/
│       └── app.js         # JavaScript functionality
└── README.md              # This file
```

## Installation

### Requirements

- PHP 8.0 or higher
- Apache web server with mod_rewrite enabled
- cURL extension enabled
- SSL certificate (for HTTPS)

### Deployment Steps

1. **Upload Files**
   ```bash
   # Upload all files to your web server
   scp -r wespee-web/* user@wespee.me:/var/www/html/
   ```

2. **Configure Apache**

   Enable mod_rewrite:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

   Ensure `.htaccess` is allowed in your Apache configuration:
   ```apache
   <Directory /var/www/html>
       AllowOverride All
   </Directory>
   ```

3. **Set Permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/html
   sudo chmod -R 755 /var/www/html
   ```

4. **Configure SSL**
   ```bash
   # Using Let's Encrypt
   sudo certbot --apache -d wespee.me -d www.wespee.me
   ```

5. **Configure App Store URLs**

   Edit [config.php](config.php) and add your app store URLs:
   ```php
   define('IOS_APP_STORE_URL', 'https://apps.apple.com/app/wespee/YOUR_APP_ID');
   define('ANDROID_PLAY_STORE_URL', 'https://play.google.com/store/apps/details?id=com.wespee.app');
   ```

6. **Test Configuration**
   ```bash
   # Test a profile URL
   curl -I https://wespee.me/davvy
   ```

## Configuration

### API Configuration

The API configuration is set in [config.php](config.php):

```php
define('API_BASE_URL', 'https://recorder-wespee-api.bicentsafe.com/api/v1');
define('API_CLIENT_ID', 'a2f43234317d8ebb28a810660d4ba73ed6d42adb7f1c75820e693cd88595a03d');
define('API_CLIENT_SECRET', 'df809470293b2ca6d5d135560deb6b3e5fbb000e160cf6d8cffe35acf657af10b4f792d5a2bc8e62ed0919d0164b183c038da34969cf256ca20154b6936950c9');
```

### Branding Colors

Customize colors in [config.php](config.php):

```php
define('COLOR_PRIMARY', '#00FF66');          // Wespee green
define('COLOR_PRIMARY_LIGHT', '#E0FFE0');    // Light green
define('COLOR_BACKGROUND', '#FAFAFA');       // Background
define('COLOR_TEXT_DARK', '#1A1A1A');        // Dark text
define('COLOR_TEXT_GRAY', '#6B7280');        // Gray text
```

### Social Media Links

Update social media links in [config.php](config.php):

```php
define('SOCIAL_YOUTUBE', 'https://youtube.com/@wespee');
define('SOCIAL_TWITTER', 'https://twitter.com/wespee');
define('SOCIAL_FACEBOOK', 'https://facebook.com/wespee');
// ... etc
```

## API Integration

### Endpoint

**POST** `https://recorder-wespee-api.bicentsafe.com/api/v1/user/accounts/username/check`

### Request Headers

```
Content-Type: application/json
x-client-id: a2f43234317d8ebb28a810660d4ba73ed6d42adb7f1c75820e693cd88595a03d
x-client-secret: df809470293b2ca6d5d135560deb6b3e5fbb000e160cf6d8cffe35acf657af10b4f792d5a2bc8e62ed0919d0164b183c038da34969cf256ca20154b6936950c9
```

### Request Body

```json
{
  "username": "davvy"
}
```

### Response

```json
{
  "statusCode": 200,
  "data": {
    "available": false,
    "firstName": "David",
    "lastName": "Smith",
    "username": "davvy",
    "profilePictureUrl": "https://...",
    "birthDate": "1990-01-01"
  }
}
```

**Note**: `available: false` means the user EXISTS (inverted logic).

## Deep Linking

### URL Format

```
https://wespee.me/<username>
```

### Deep Link Behavior

1. **Mobile App Installed**: Opens the app at send screen for the user
2. **App Not Installed**: Redirects to appropriate app store (iOS/Android)
3. **Desktop**: Shows app download modal

### QR Code Format

QR codes contain: `w:<username>`

Example: For user "davvy", QR code contains `w:davvy`

## Testing

### Local Testing

1. **Start PHP Development Server**
   ```bash
   cd wespee-web
   php -S localhost:8000
   ```

2. **Test Profile URLs**
   ```
   http://localhost:8000/davvy
   ```

3. **Test API Connection**
   ```bash
   curl -X POST \
     -H "Content-Type: application/json" \
     -H "x-client-id: a2f43234317d8ebb28a810660d4ba73ed6d42adb7f1c75820e693cd88595a03d" \
     -H "x-client-secret: df809470293b2ca6d5d135560deb6b3e5fbb000e160cf6d8cffe35acf657af10b4f792d5a2bc8e62ed0919d0164b183c038da34969cf256ca20154b6936950c9" \
     -d '{"username":"davvy"}' \
     https://recorder-wespee-api.bicentsafe.com/api/v1/user/accounts/username/check
   ```

### Testing Checklist

- [ ] Profile loads for existing users
- [ ] 404 page shows for non-existent users
- [ ] Copy profile link works
- [ ] Share button functionality
- [ ] QR code generation works
- [ ] QR code modal opens/closes
- [ ] "Payer maintenant" button
- [ ] Mobile app deep linking
- [ ] App store redirects
- [ ] Responsive design (mobile/tablet/desktop)
- [ ] Social media icons work
- [ ] Footer links work
- [ ] SEO meta tags render correctly
- [ ] Profile pictures load with fallback
- [ ] HTTPS redirect (production)

## Troubleshooting

### Common Issues

**1. Clean URLs not working**
- Ensure mod_rewrite is enabled
- Check .htaccess file permissions
- Verify Apache AllowOverride is set to All

**2. API Connection Failed**
- Check API credentials in config.php
- Verify cURL is enabled: `php -m | grep curl`
- Check server firewall settings

**3. Profile Pictures Not Loading**
- Check CORS settings
- Verify image URLs are accessible
- Check Content-Security-Policy headers

**4. Deep Links Not Working**
- Verify app store URLs are configured
- Test on actual mobile devices (not emulators)
- Check browser console for errors

### Debug Mode

Enable error reporting for debugging:

```php
// Add to top of index.php temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

**Remember to disable in production!**

## Performance Optimization

### Caching

Enable caching in [config.php](config.php):

```php
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 300); // 5 minutes
```

### CDN Configuration

Consider using a CDN for:
- Tailwind CSS (already using CDN)
- QRCode.js library
- Static assets (images, CSS, JS)

### Image Optimization

- Use WebP format with JPEG/PNG fallback
- Implement lazy loading for profile pictures
- Set appropriate cache headers for images

## Security

### Implemented Security Measures

- HTTPS enforcement
- Input validation and sanitization
- XSS prevention (htmlspecialchars)
- CSRF protection headers
- Content Security Policy
- Rate limiting (to be implemented)
- Secure API credentials handling

### Security Headers

The `.htaccess` file includes:
- X-Frame-Options
- X-XSS-Protection
- X-Content-Type-Options
- Content-Security-Policy

## Maintenance

### Regular Tasks

- Monitor error logs: `/var/log/apache2/error.log`
- Update dependencies (PHP, Apache)
- Check SSL certificate expiration
- Review API usage and performance
- Update social media links as needed

### Monitoring

Set up monitoring for:
- Server uptime
- API response times
- Error rates
- SSL certificate expiration
- Disk space

## Support

For issues or questions:
- Check the troubleshooting section above
- Review server error logs
- Contact Wespee development team

## License

© 2025 Wespee. All rights reserved.

## Changelog

### v1.0.0 (2025-01-XX)
- Initial release
- User profile display
- QR code generation
- Deep link integration
- Responsive design
- SEO optimization
#   P u b l i c l i n k  
 