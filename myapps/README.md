# MyApps KEDA

**Enterprise Web Application** - Single Sign-On (SSO) Provider & Single Source of Truth (SSOT)

## 🚀 Technology Stack

- **Framework**: Laravel 11
- **Admin Panel**: FilamentPHP v3 (TALL Stack)
- **Frontend**: Tailwind CSS
- **Database**: MySQL
- **PWA**: Vite PWA Plugin

## 📋 Features

### Core Modules
- ✅ Staff Management (CRUD)
- ✅ Application Management (CRUD)
- ✅ Role-Based Access Control (Admin/User)
- ✅ Single Sign-On (SSO) Provider
- ✅ Single Source of Truth (SSOT) API
- ✅ Activity Audit Trail
- ✅ Dashboard Analytics & Widgets

### Dashboard Widgets
- 📊 Staff Statistics Overview
- 📈 Staff Status Distribution Chart
- 📊 Application Category Chart
- 🎂 Birthday Widget
- 🎨 Interactive Application Grid

### API Endpoints
- `GET /api/v1/staf/{no_kp}` - SSOT Staff Information
- `POST /api/v1/sso/token` - Issue SSO Token
- `POST /api/v1/sso/verify` - Verify SSO Token

## 🔐 Authentication

**Login Credentials:**
- Username: No. K/P (IC Number)
- Password: Standard password

**Admin Access:**
- URL: `http://localhost:8000/admin`
- Admin role required

## ⚙️ Installation

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Update `.env` with database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapps
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Database Setup

1. Create database `myapps`
2. Import `myapps.sql` via phpMyAdmin
3. Run optimization scripts if needed

### 4. Build Assets

```bash
npm run build
```

### 5. Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

### 6. Start Server

```bash
php artisan serve
```

Access: `http://localhost:8000`

## 🛠️ Maintenance

### Clear Cache

```bash
php artisan optimize:clear
```

### Rebuild Cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

## 📊 Performance

Optimized for enterprise-grade performance:
- Widget caching (5-60 minutes)
- Database query optimization
- Reduced polling intervals
- Laravel config/route/view caching

## 🎨 Design

**Color Scheme:**
- Primary: Navy Blue (#1e3a8a)
- Secondary: Gold (#fbbf24)
- Background: White

**Features:**
- Responsive design
- Progressive Web App (PWA)
- Modern enterprise UI/UX

## 📞 Support

**Version:** 1.0
**Last Updated:** 28 Disember 2025

---

**Developed for KEDA** 🇲🇾
