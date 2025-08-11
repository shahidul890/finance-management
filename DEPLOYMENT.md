# FTP Deployment Setup

This document explains how to set up automatic FTP deployment for your Laravel application using GitHub Actions.

## Prerequisites

- A web server with FTP access
- GitHub repository with Actions enabled
- Laravel application with proper environment configuration

## Setup Instructions

### 1. Configure GitHub Secrets

Go to your GitHub repository settings → Secrets and variables → Actions → New repository secret

Add the following secrets:

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `FTP_SERVER` | Your FTP server hostname or IP | `ftp.yourhost.com` |
| `FTP_USERNAME` | Your FTP username | `your_ftp_user` |
| `FTP_PASSWORD` | Your FTP password | `your_secure_password` |
| `FTP_SERVER_DIR` | Target directory on FTP server | `/public_html/` or `/htdocs/` |

### 2. Server Requirements

Ensure your server has:
- PHP 8.4 or higher
- Web server (Apache/Nginx) configured
- Database server (MySQL/PostgreSQL)
- Proper file permissions for Laravel

### 3. Environment Configuration

Create a production `.env` file on your server with appropriate settings:

```env
APP_NAME="Your Finance App"
APP_ENV=production
APP_KEY=base64:your_app_key_here
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your_mail_host
MAIL_PORT=587
MAIL_USERNAME=your_mail_user
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=tls

# Add other environment variables as needed
```

### 4. Server Directory Structure

Your FTP server directory should look like this after deployment:

```
/public_html/ (or your web root)
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── index.php
│   ├── build/ (compiled assets)
│   └── ...
├── resources/
├── routes/
├── storage/
├── vendor/
├── artisan
└── .env (your production environment file)
```

### 5. Post-Deployment Steps

After the first deployment, SSH into your server and run:

```bash
# Set proper permissions
chmod -R 755 storage bootstrap/cache
chmod 644 .env

# Run migrations (if needed)
php artisan migrate --force

# Clear and cache configurations
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set up storage symlink if using file storage
php artisan storage:link
```

## Workflow Triggers

The deployment workflow runs automatically when:

- Code is pushed to `main` branch
- Code is pushed to `production` branch
- Manually triggered via GitHub Actions interface

## What Gets Deployed

The workflow will:

1. ✅ Install PHP dependencies (production only)
2. ✅ Install and build Node.js assets
3. ✅ Generate Laravel application key
4. ✅ Compile frontend assets (CSS, JS)
5. ✅ Generate Ziggy routes configuration
6. ✅ Optimize Laravel caches
7. ✅ Upload files via FTP

## What Gets Excluded

The following files/directories are NOT uploaded:

- ❌ `node_modules/`
- ❌ `tests/`
- ❌ `.git/` and `.github/`
- ❌ Development configuration files
- ❌ Package manager files (`composer.json`, `package.json`)
- ❌ Build configuration files
- ❌ Log files and cache files

## Troubleshooting

### Common Issues

1. **Permission Denied Errors**
   ```bash
   chmod -R 755 storage bootstrap/cache
   ```

2. **Environment File Missing**
   - Ensure `.env` file exists on server
   - Check FTP_SERVER_DIR path is correct

3. **Database Connection Issues**
   - Verify database credentials in server `.env`
   - Ensure database exists and user has proper permissions

4. **Asset Loading Issues**
   - Check if `npm run build` completed successfully
   - Verify `public/build/` directory exists on server

### Deployment Logs

Check the GitHub Actions logs for detailed deployment information:
1. Go to your repository on GitHub
2. Click "Actions" tab
3. Click on the latest "FTP Deploy" workflow run
4. Review logs for any errors

## Security Considerations

- ✅ Use strong FTP passwords
- ✅ Enable FTPS/SFTP if available
- ✅ Limit FTP user permissions to necessary directories only
- ✅ Keep GitHub secrets secure and rotate regularly
- ✅ Use production environment settings (`APP_DEBUG=false`)
- ✅ Configure proper file permissions on server

## Manual Deployment

If you need to deploy manually:

```bash
# Build locally
composer install --no-dev --optimize-autoloader
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Upload via FTP client or rsync
# Exclude: node_modules, tests, .git, package.json, composer.json
```

## Support

For deployment issues:
1. Check GitHub Actions logs
2. Verify server configuration
3. Review this documentation
4. Check Laravel logs on server (`storage/logs/`)
