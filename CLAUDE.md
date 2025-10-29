# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**OptimaSphere ERP** is a Laravel 12 application with Filament admin panel integration. The project uses Tailwind CSS 4 for styling and Vite for asset bundling. Development is managed through Reward Docker environment.

**Creator**: [Webtech-Solutions](https://webtech-solutions.hu)

## Development Commands

### Initial Setup
```bash
composer setup
```
This runs the complete setup: installs dependencies, creates .env file, generates app key, runs migrations, installs npm packages, and builds assets.

**Important**: After setup, update your `.env` file with the application name:
```
APP_NAME="OptimaSphere ERP"
```

### Version Management

Version information is managed in `config/app-version.php`:

```php
'version' => '1.0.0',  // Update when releasing new versions
'stage' => 'stable',   // Options: 'alpha', 'beta', 'stable'
```

**Version Stages**:
- `alpha` - Displays version number with red ALPHA badge in footer (e.g., v1.0.0 ALPHA)
- `beta` - Displays version number with yellow BETA badge in footer (e.g., v1.0.0 BETA)
- `stable` - Displays version number only, without stage badge (e.g., v1.0.0)

Update `config/app-version.php` directly when:
- Releasing a new version (change `version` value)
- Moving between release stages (change `stage` value)

### Development Server
```bash
composer dev
```
Starts a concurrent development environment with:
- PHP development server (port 8000)
- Queue worker (single try)
- Laravel Pail for logs
- Vite dev server for hot module replacement

Individual services can be started separately if needed:
```bash
php artisan serve          # Development server
npm run dev                # Vite dev server only
php artisan queue:listen   # Queue worker
php artisan pail           # Log viewer
```

### Asset Building
```bash
npm run build    # Production build
npm run dev      # Development with hot reload
```

### Testing
```bash
composer test              # Run all tests
php artisan test           # Run all tests
php artisan test --filter TestName  # Run specific test
vendor/bin/phpunit tests/Unit       # Run unit tests only
vendor/bin/phpunit tests/Feature    # Run feature tests only
```

Test configuration uses SQLite in-memory database with test environment variables defined in phpunit.xml.

### Code Quality
```bash
./vendor/bin/pint          # Code formatting (Laravel Pint)
php artisan config:clear   # Clear configuration cache
php artisan cache:clear    # Clear application cache
```

### Database Operations
```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh        # Drop all tables and re-run migrations
php artisan migrate:fresh --seed # Fresh migration with seeding
php artisan db:seed              # Run database seeders
```

## Architecture

### Filament Admin Panel
- Admin panel is configured at `/admin` route (app/Providers/Filament/AdminPanelProvider.php:28)
- Filament Resources auto-discovered from `app/Filament/Resources`
- Filament Pages auto-discovered from `app/Filament/Pages`
- Filament Widgets auto-discovered from `app/Filament/Widgets`
- Default authentication enabled with login page
- Primary color set to Amber

### Application Structure
- **Models**: `app/Models/` - Eloquent models
- **Controllers**: `app/Http/Controllers/` - HTTP controllers
- **Providers**: `app/Providers/` - Service providers
  - `AdminPanelProvider` for Filament configuration
  - `AppServiceProvider` for application services
- **Routes**:
  - `routes/web.php` - Web routes
  - `routes/console.php` - Console commands
- **Views**: `resources/views/` - Blade templates
- **Migrations**: `database/migrations/` - Database schema
- **Factories**: `database/factories/` - Model factories
- **Seeders**: `database/seeders/` - Database seeders

### Frontend Assets
- Vite configuration in `vite.config.js`
- Entry points:
  - `resources/css/app.css` - Main stylesheet
  - `resources/js/app.js` - Main JavaScript
- Tailwind CSS 4 via Vite plugin
- Auto-refresh enabled in development

### Environment Configuration
- Default database: SQLite
- Queue connection: database
- Cache store: database
- Session driver: database
- Mail driver: log (development)
- File system: local

### Docker Environment
This project uses Reward for Docker-based local development. The `.reward/` directory contains environment-specific configurations.

## Key Technologies
- PHP 8.2+
- Laravel 12
- Filament 3
- Tailwind CSS 4
- Vite 7
- PHPUnit 11

## Development Workflow
1. When creating new Filament resources, they will be auto-discovered from `app/Filament/Resources`
2. Database changes require migrations in `database/migrations/`
3. Frontend changes in `resources/` are hot-reloaded when using `npm run dev`
4. Queue jobs are processed by the queue worker when using `composer dev`
5. Logs are viewable in real-time via Pail when using `composer dev`
- Counter must have on all sub-menus.
- Run all artisan commands with docker exec php-fpm-1 don't create anything what available with command line
- Run all artisan commands with docker exec werp-php-fpm-1 don't create anything what available with command line