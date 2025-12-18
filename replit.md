# Wespee Profile Web Interface

## Overview
Public web interface for Wespee user profiles, accessible via `/<username>`. This serves as a fallback when users open deep links in a browser instead of the mobile app.

## Tech Stack
- **Backend**: PHP 8.2 with GD library for image generation
- **Frontend**: HTML5, Tailwind CSS (CDN), Vanilla JavaScript
- **QR Code**: QRCode.js library (CDN)
- **API**: Wespee REST API (external)

## Project Structure
```
/
├── index.php          # Main entry point - profile display
├── og-image.php       # Dynamic OG image generator for social sharing
├── 404.php            # Error page for non-existent users
├── config.php         # Configuration constants (API, branding)
├── router.php         # URL rewriting router for PHP built-in server
├── assets/
│   ├── css/styles.css # Custom styles
│   ├── fonts/         # Athletics font family
│   ├── images/        # Logo, icons, etc.
│   └── js/app.js      # Frontend JavaScript
└── README.md          # Original documentation
```

## Running the Project
The project runs on PHP's built-in development server with `router.php` handling URL rewrites:
```bash
php -S 0.0.0.0:5000 router.php
```

## URL Patterns
- `/<username>` - Shows user profile page
- `/og-image/<username>` - Generates dynamic Open Graph image for social sharing
- `/` - Redirects to main Wespee site
- Non-existent users show 404 page

## Dynamic OG Image
The `/og-image/<username>` endpoint generates a 1200x630 PNG image for social media previews featuring:
- Green Wespee background with pattern
- User avatar (initials if no photo)
- Username badge
- Wespee branding

Security: Avatar URLs are validated against an allowlist of Wespee-owned domains only.

## API Integration
The app connects to an external Wespee API to fetch user profiles.

## Environment Variables
API credentials are loaded from environment variables. Two methods supported:

### On Replit
Use Replit Secrets (recommended):
- `API_CLIENT_ID` - Wespee API client ID
- `API_CLIENT_SECRET` - Wespee API client secret

### External Hosting
Copy `.env.example` to `.env` and fill in values:
```bash
cp .env.example .env
```

## Configuration
Key settings in `config.php`:
- API credentials (from environment variables)
- Site branding colors
- Social media links
- App store URLs (iOS/Android)

## Security
- robots.txt blocks search engine indexing
- noindex meta tags on all pages
- Direct PHP file access blocked (403)
- SSRF protection on OG image avatar fetching
