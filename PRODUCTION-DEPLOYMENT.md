# OptimaSphere ERP - Production Deployment Guide

## Overview

This guide helps you deploy OptimaSphere ERP to a cPanel production environment where npm/node is not available.

## Prerequisites

- Local development environment with npm installed
- SSH or FTP access to production server
- Production database configured

---

## Step 1: Build Assets Locally

Run the deployment script from your local machine:

```bash
./deploy-production.sh
```

Or manually:

```bash
# Install dependencies
npm install

# Build for production
npm run build
```

This will create the `public/build` directory with compiled assets including:
- `manifest.json` - Asset manifest file
- CSS files (minified)
- JS files (minified)

---

## Step 2: Upload Built Assets to Production

### Option A: Using FTP/SFTP

1. Connect to your production server via FTP
2. Navigate to: `/home/txoyxssz/optimasphere.webtech-solutions.hu/public/`
3. Upload the entire `build` directory from local `public/build/` to production `public/build/`

### Option B: Using SCP (if SSH available)

```bash
scp -r public/build/* user@server:/home/txoyxssz/optimasphere.webtech-solutions.hu/public/build/
```

### Option C: Using rsync (if SSH available)

```bash
rsync -avz public/build/ user@server:/home/txoyxssz/optimasphere.webtech-solutions.hu/public/build/
```

---

## Step 3: Upload Code Changes

Upload the following updated files to production:

### Required Files:
- `database/migrations/2025_01_24_000002_create_suppliers_table.php` (fixed code field)
- All new migration files from `database/migrations/2025_01_25_*`
- All new model files
- All new Filament resources
- All new widgets

### Important:
- Make sure `.env` file is configured correctly on production
- Do NOT upload `.env` from local - use production values

---

## Step 4: Run Database Migrations on Production

Using cPanel Terminal or SSH:

```bash
cd /home/txoyxssz/optimasphere.webtech-solutions.hu

# Run migrations
php artisan migrate --force

# If you need to seed data (ONLY first time):
php artisan db:seed --force
```

**Important:** The `--force` flag is required in production to bypass the confirmation prompt.

---

## Step 5: Clear All Caches

```bash
cd /home/txoyxssz/optimasphere.webtech-solutions.hu

# Clear configuration cache
php artisan config:clear

# Clear application cache
php artisan cache:clear

# Clear compiled views
php artisan view:clear

# Clear route cache
php artisan route:clear

# Optimize for production (optional but recommended)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Step 6: Set Correct Permissions

```bash
chmod -R 755 /home/txoyxssz/optimasphere.webtech-solutions.hu/storage
chmod -R 755 /home/txoyxssz/optimasphere.webtech-solutions.hu/bootstrap/cache
```

---

## Step 7: Verify Deployment

1. Visit your production URL: `https://optimasphere.webtech-solutions.hu`
2. Check that assets are loading (no 404 errors in browser console)
3. Test login functionality
4. Verify dashboard widgets are displaying correctly

---

## Troubleshooting

### Issue: "Vite manifest not found"

**Solution:**
- Verify `public/build/manifest.json` exists on production server
- Check file permissions: `chmod 644 public/build/manifest.json`
- Ensure full path is correct: `/home/txoyxssz/optimasphere.webtech-solutions.hu/public/build/manifest.json`

### Issue: "Field 'code' doesn't have a default value"

**Solution:**
- Make sure you uploaded the updated `database/migrations/2025_01_24_000002_create_suppliers_table.php`
- Drop the suppliers table and re-run migrations:
  ```bash
  php artisan tinker
  Schema::dropIfExists('suppliers');
  exit
  php artisan migrate --force
  ```

### Issue: CSS/JS not loading

**Solution:**
- Clear browser cache
- Check `.env` has `APP_ENV=production`
- Verify `APP_URL` in `.env` matches your domain
- Check `public/build/` directory permissions

### Issue: 500 Internal Server Error

**Solution:**
- Check Laravel logs: `storage/logs/laravel.log`
- Ensure storage directory is writable: `chmod -R 755 storage`
- Run: `php artisan config:clear && php artisan cache:clear`

---

## Production Environment Variables

Ensure your production `.env` has these settings:

```env
APP_NAME="OptimaSphere ERP"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://optimasphere.webtech-solutions.hu

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_production_db
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Cache (use database for simplicity on cPanel)
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# Mail (configure for production)
MAIL_MAILER=smtp
MAIL_HOST=your_mail_host
MAIL_PORT=587
MAIL_USERNAME=your_mail_username
MAIL_PASSWORD=your_mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@webtech-solutions.hu
MAIL_FROM_NAME="OptimaSphere ERP"
```

---

## Future Deployments

For future updates:

1. **Code changes only:** Upload changed files via FTP
2. **Database changes:** Run `php artisan migrate --force`
3. **Asset changes:** Run `./deploy-production.sh` locally, then upload `public/build/`
4. **Always clear caches** after deployment

---

## Quick Deployment Checklist

- [ ] Build assets locally: `npm run build`
- [ ] Upload `public/build/` directory
- [ ] Upload changed PHP files
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Clear caches: `php artisan config:clear && php artisan cache:clear && php artisan view:clear`
- [ ] Test application

---

## Support

For issues, check:
- Laravel logs: `storage/logs/laravel.log`
- Web server error logs (cPanel → Error Logs)
- Browser console for JavaScript errors

Creator: [Webtech-Solutions](https://webtech-solutions.hu)
