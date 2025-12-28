# 🔐 MyApps KEDA - Admin & User Roles Setup Guide

## 📋 **OVERVIEW**

Sistem ini membolehkan:
- ✅ **Semua staf** boleh login menggunakan **No. K/P** mereka
- ✅ **Password yang sama** untuk semua: `Noor@z@m1982`
- ✅ **Admin** (No. K/P: 820426025349) boleh akses Admin Panel
- ✅ **User biasa** hanya boleh akses public portal

---

## 🚀 **CARA SETUP**

### **Step 1: Run SQL Script**

1. Buka **phpMyAdmin** (http://localhost/phpmyadmin)
2. Pilih database `myapps`
3. Klik tab **SQL**
4. Copy & paste kandungan file: `setup-admin-roles.sql`
5. Klik **Go** untuk execute

### **Step 2: Clear Laravel Cache**

Run command ini dalam terminal:

```bash
cd D:\laragon\www\myapps
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

### **Step 3: Test Login**

#### **TEST 1: Login sebagai Admin**
- URL: http://localhost:8000/admin/login
- Username: `820426025349`
- Password: `Noor@z@m1982`
- Expected: ✅ Boleh masuk Admin Panel

#### **TEST 2: Login sebagai User Biasa**
- URL: http://localhost:8000/admin/login
- Username: `660403026023` (atau No. K/P lain)
- Password: `Noor@z@m1982`
- Expected: ❌ Error 403 - Access Denied (user biasa tidak boleh masuk admin panel)

---

## 🗂️ **DATABASE STRUCTURE**

### **Table: `login`**

| Column | Type | Description |
|--------|------|-------------|
| `id_login` | INT | Primary key |
| `id_staf` | INT | Foreign key to `staf` table |
| `password_hash` | VARCHAR(255) | Bcrypt hash |
| **`role`** | ENUM('admin', 'user') | **NEW COLUMN** |
| `created_at` | TIMESTAMP | Created timestamp |
| `updated_at` | TIMESTAMP | Updated timestamp |

---

## 🔑 **ROLE LOGIC**

### **Admin Role**
- Full access to Filament Admin Panel
- Can manage:
  - Staf (CRUD)
  - Aplikasi (CRUD)
  - Dashboard Widgets
  - System Settings

### **User Role**
- Access to public portal only
- Cannot access `/admin/*` routes
- View personal profile
- View applications assigned to them

---

## 🛡️ **SECURITY**

### **Middleware: CheckAdminRole**

File: `app/Http/Middleware/CheckAdminRole.php`

```php
public function handle(Request $request, Closure $next): Response
{
    if (!auth()->check()) {
        return redirect()->route('filament.admin.auth.login');
    }

    $user = auth()->user();
    $user->load('loginRecord');

    if ($request->is('admin*')) {
        if (!$user->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }
    }

    return $next($request);
}
```

### **Model Methods**

File: `app/Models/Staf.php`

```php
// Check if user is admin
public function isAdmin()
{
    return $this->loginRecord && $this->loginRecord->role === 'admin';
}

// Get user role
public function getRole()
{
    return $this->loginRecord ? $this->loginRecord->role : 'user';
}
```

---

## 📊 **VERIFY SETUP**

Run this SQL to verify:

```sql
-- Check semua login records dengan role
SELECT 
    s.no_kp,
    s.nama,
    l.role,
    CASE 
        WHEN l.role = 'admin' THEN '👑 ADMIN' 
        ELSE '👤 USER' 
    END as access_level
FROM login l
INNER JOIN staf s ON l.id_staf = s.id_staf
ORDER BY 
    FIELD(l.role, 'admin', 'user'),
    s.nama
LIMIT 20;
```

---

## ❓ **TROUBLESHOOTING**

### **Problem: User biasa boleh masuk admin panel**

**Solution**:
1. Check column `role` ada dalam table `login`
2. Run SQL: `SELECT * FROM login WHERE id_staf = ?`
3. Pastikan `role = 'user'` bukan `'admin'`

### **Problem: Admin tak boleh masuk**

**Solution**:
1. Check No. K/P betul: `820426025349`
2. Run SQL: 
   ```sql
   UPDATE login l
   INNER JOIN staf s ON l.id_staf = s.id_staf
   SET l.role = 'admin'
   WHERE s.no_kp = '820426025349';
   ```

### **Problem: Password salah**

**Solution**:
Password hash untuk `Noor@z@m1982`:
```
$2y$12$6GVcyRuDLCyscSEweVLEg.eJgDDhK/VgjhUxaFDw1AR7b5yvusXQe
```

---

## 🎯 **NEXT STEPS**

1. ✅ Setup admin role
2. ⏳ Create public user portal (dashboard for user biasa)
3. ⏳ Implement SSO for external apps
4. ⏳ Add audit logging for role changes

---

## 📞 **SUPPORT**

Jika ada masalah:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check database structure: `DESCRIBE login;`
3. Clear cache: `php artisan optimize:clear`

---

**Last Updated**: 28 Disember 2025
**Version**: 1.0

