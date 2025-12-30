# 🚀 MyApps KEDA - Setup & Configuration Guide

## ⚙️ Initial Setup (Sebelum Run Aplikasi)

### 1. **Create `.env` File**

Buat file `.env` di root folder project dengan isi (gunakan credentials Anda):

```env
# APP CONFIGURATION
APP_NAME=MyApps KEDA
APP_ENV=development
APP_DEBUG=false
APP_URL=http://127.0.0.1/myapps
APP_TIMEZONE=Asia/Kuala_Lumpur

# DATABASE CONFIGURATION
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapps
DB_USERNAME=root
DB_PASSWORD=your_database_password_here

# MAIL CONFIGURATION
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_gmail_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_NAME=Sistem MyApps

# JWT SECURITY
JWT_SECRET_KEY=your_super_secret_key_here
JWT_ALGORITHM=HS256

# CORS CONFIGURATION
CORS_ALLOWED_ORIGINS=http://127.0.0.1,http://localhost

# RATE LIMITING
RATE_LIMIT_ENABLED=true
RATE_LIMIT_ATTEMPTS=5
RATE_LIMIT_WINDOW=900
```

**⚠️ PENTING:** 
- Jangan share file `.env` kepada sesiapa
- Jangan commit `.env` ke Git/GitHub
- Ganti semua placeholder dengan nilai actual

---

### 2. **Generate JWT Secret Key**

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Copy hasilnya dan paste di `JWT_SECRET_KEY` dalam file `.env`

---

### 3. **Setup Gmail App Password**

Untuk menggunakan email feature:

1. Enable 2-Factor Authentication di Gmail account
2. Create App Password di: https://myaccount.google.com/apppasswords
3. Copy App Password dan paste di `MAIL_PASSWORD` dalam `.env`

---

### 4. **Create Logs Directory**

```bash
mkdir logs
chmod 755 logs
```

---

## 🧪 Testing

### Test Login Rate Limiting:
```php
// Try login 6 times dalam 15 minutes dengan wrong password
// Attempt ke-6 seharusnya error: "Terlalu banyak percobaan login gagal"
```

### Test CORS:
```bash
# From browser console, try fetch dari domain lain
fetch('http://your-domain/myapps/api/api.php', {
  headers: {'Authorization': 'Bearer token'}
})
// Should fail with CORS error
```

### Test URL Validation:
```php
// Form input dengan URL tidak sah
// Example: "not a url"
// Seharusnya error: "URL tidak sah"
```

---

## 📊 Project Structure

```
myapps/
├── .env                      # ⚠️ Environment variables (NOT in Git)
├── .env.example              # Template for .env
├── .gitignore               # Git ignore rules
├── SECURITY_FIXES.md        # Security improvements documentation
├── security_helper.php      # Security utility functions
│
├── api/
│   ├── api.php             # Base API class with rate limiting
│   ├── aplikasi.php        # Aplikasi API endpoints
│   └── staf.php            # Staff API endpoints
│
├── src/
│   ├── PHPMailer.php
│   ├── SMTP.php
│   └── Exception.php
│
├── logs/
│   └── security.log         # Security events log
│
└── [other application files]
```

---

## 🔒 Security Features Implemented

### ✅ Active Protections:
- **CSRF Token Protection** - Every form has CSRF token
- **SQL Injection Prevention** - Prepared statements everywhere
- **XSS Prevention** - Output escaping with htmlspecialchars
- **Rate Limiting** - Prevent brute force attacks
- **CORS Restriction** - Only allow configured origins
- **Security Headers** - X-Frame-Options, HSTS, etc
- **Session Security** - httpOnly cookies, strict mode
- **Password Security** - password_hash() & password_verify()
- **Environment Variables** - Credentials never in code
- **Error Logging** - Errors logged, not displayed to users

---

## 🚀 Production Deployment

Sebelum go-live ke production:

### 1. Update `.env` untuk production:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-production-domain.com
DB_PASSWORD=production_password
MAIL_PASSWORD=production_gmail_app_password
JWT_SECRET_KEY=production_secret_key
CORS_ALLOWED_ORIGINS=https://your-production-domain.com
```

### 2. Enable HTTPS:
```env
# Automatic dengan production env
```

### 3. Verify Security:
```bash
# Check .env is in .gitignore
cat .gitignore | grep ".env"

# Verify no credentials in code
grep -r "password" api/ --include="*.php"
```

### 4. Monitor Logs:
```bash
tail -f logs/security.log
```

---

## 🛠️ Troubleshooting

### Error: "Cannot find .env file"
```php
// Ensure .env exists in root folder:
ls .env

// If missing, copy from template:
cp .env.example .env
// Then edit .env with actual credentials
```

### Error: "Database connection failed"
```php
// Check .env credentials:
cat .env | grep DB_

// Test connection:
php -r "new PDO('mysql:host=127.0.0.1;dbname=myapps', 'root', 'password');"
```

### Error: "Rate limit exceeded"
```php
// Wait 15 minutes or clear session:
session_destroy();
```

### Email not sending
```php
// Check Gmail app password is correct
// Enable "Less secure app access" if needed
// Verify SMTP settings in .env
```

---

## 📞 Support

Untuk pertanyaan lebih lanjut:
1. Check SECURITY_FIXES.md
2. Review error logs di logs/security.log
3. Contact: admin@keda.gov.my

---

**Last Updated:** 2025-12-30
**Security Level:** Production-Ready ✅
