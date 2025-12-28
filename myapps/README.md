# MyApps KEDA

Portal pusat untuk semua aplikasi dalam talian KEDA. Berfungsi sebagai Single Sign-On (SSO) Provider dan Single Source of Truth (SSOT) untuk maklumat staf.

## Requirements

- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js & NPM

## Installation

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan storage:link

# Build assets
npm run build
```

## Production Deployment

```bash
# Optimize for production
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

## Features

- 🔐 Single Sign-On (SSO) Provider
- 📊 Single Source of Truth (SSOT) API
- 👥 Staff Management (CRUD)
- 📱 Application Management (CRUD)
- 📈 Dashboard with Statistics
- 🎨 Navy Blue & Gold Corporate Theme
- 🌐 Dual Language (Bahasa Melayu / English)
- 📱 Progressive Web App (PWA)

## Tech Stack

- Laravel 11
- FilamentPHP v3
- Tailwind CSS
- MySQL

## License

Proprietary - KEDA

