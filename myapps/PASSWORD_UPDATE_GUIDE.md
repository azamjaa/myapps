# 🔐 **STANDARD PASSWORD UPDATE - DOKUMENTASI**

## 📋 **OVERVIEW**

Password standard untuk SEMUA staf telah ditetapkan kepada: **`Noor@z@m1982`**

---

## 🔑 **PASSWORD DETAILS**

### **Password Standard**:
```
Noor@z@m1982
```

### **Password Hash** (Bcrypt):
```
$2y$12$6GVcyRuDLCyscSEweVLEg.eJgDDhK/VgjhUxaFDw1AR7b5yvusXQe
```

### **Hash Algorithm**:
- Bcrypt (PASSWORD_BCRYPT)
- Cost: 12 rounds
- Generated: {{ date('Y-m-d H:i:s') }}

---

## 📝 **CARA UPDATE**

### **Option 1: Via phpMyAdmin** (RECOMMENDED)

1. **Buka phpMyAdmin**:
   ```
   http://localhost/phpmyadmin
   ```

2. **Select database `myapps`**

3. **Click tab "SQL"**

4. **Copy & Paste SQL dari file**: `update-all-staf-password.sql`

5. **Click "Go"**

6. **Verify result**: 
   - Should see: "X rows affected"
   - Check verification query results

---

### **Option 2: Via MySQL Terminal**

```bash
mysql -u root -pNoor@z@m1982 myapps < update-all-staf-password.sql
```

---

## ✅ **SELEPAS UPDATE**

### **Login Credentials (Semua Staf)**:

```
Username: No. K/P (12 digit tanpa sengkang)
Password: Noor@z@m1982
```

### **Contoh Login**:

```
No. K/P: 900101011234
Password: Noor@z@m1982
```

---

## 🧪 **TESTING**

### **Step 1: Clear Laravel Cache**
```bash
cd D:\laragon\www\myapps
php artisan optimize:clear
```

### **Step 2: Test Login**
```
URL: http://127.0.0.1:8000/admin/login

Credentials:
- No. K/P: 900101011234
- Password: Noor@z@m1982
```

### **Step 3: Verify**
- ✅ Login successful
- ✅ Redirect to dashboard
- ✅ No errors

---

## 📊 **SQL DETAILS**

### **What the SQL Does**:

1. **UPDATE existing login records**:
   - Sets password_hash to new hash
   - Updates timestamp (`updated_at`)
   - Records password change date (`tarikh_tukar_katalaluan`)

2. **INSERT login records** for staff without login:
   - Creates login record with standard password
   - For all staff in `staf` table that don't have `login` record

3. **VERIFY** the update:
   - Counts total login records
   - Shows sample of 10 staff with updated credentials

---

## 🔍 **VERIFY QUERIES**

### **Check Total Login Records**:
```sql
SELECT COUNT(*) as total FROM login;
```

### **Check Password Hash**:
```sql
SELECT 
    s.no_kp,
    s.nama,
    l.password_hash,
    l.tarikh_tukar_katalaluan
FROM staf s
INNER JOIN login l ON s.id_staf = l.id_staf
LIMIT 5;
```

### **Test Password Verification** (PHP):
```php
php -r "echo password_verify('Noor@z@m1982', '\$2y\$12\$6GVcyRuDLCyscSEweVLEg.eJgDDhK/VgjhUxaFDw1AR7b5yvusXQe') ? 'MATCH' : 'NO MATCH';"
```

Should output: **MATCH**

---

## 🔐 **SECURITY NOTES**

### **Password Strength**:
- ✅ Length: 12 characters
- ✅ Uppercase: N
- ✅ Lowercase: oorzm
- ✅ Numbers: 1982
- ✅ Special chars: @, @
- ✅ **Strong password** ✓

### **Storage**:
- ✅ Bcrypt hashed (one-way encryption)
- ✅ Cost factor: 12 (recommended)
- ✅ Salt automatically included
- ✅ Cannot be reversed to plain text

### **Recommendations**:
1. ⚠️ Users should change password on first login
2. ✅ Implement password change form in profile
3. ✅ Enforce password expiry (e.g., 90 days)
4. ✅ Log password changes in audit table

---

## 📝 **AFFECTED TABLES**

### **`login` Table**:
```
Columns updated:
- password_hash (new hash)
- updated_at (current timestamp)
- tarikh_tukar_katalaluan (current timestamp)
```

### **Records Affected**:
- All existing login records: **UPDATED**
- New login records: **INSERTED** (for staff without login)

---

## 🚨 **TROUBLESHOOTING**

### **Issue: Cannot login after update**

**Solution**:
1. Clear browser cache (Ctrl + Shift + Delete)
2. Clear Laravel cache:
   ```bash
   php artisan optimize:clear
   ```
3. Verify password hash in database
4. Test with correct password: `Noor@z@m1982`

### **Issue: Some staff still cannot login**

**Solution**:
1. Check if staff has login record:
   ```sql
   SELECT s.no_kp, s.nama, l.id_login
   FROM staf s
   LEFT JOIN login l ON s.id_staf = l.id_staf
   WHERE s.no_kp = 'XXXXXX';
   ```

2. If `id_login` is NULL, run INSERT part of SQL again

### **Issue: Wrong password error**

**Solution**:
1. Verify you're typing: `Noor@z@m1982` (case-sensitive)
2. Check for extra spaces
3. Use password reveal (eye icon) to verify input

---

## 📅 **CHANGE LOG**

| Date | Action | Password | By |
|------|--------|----------|-----|
| {{ date('Y-m-d') }} | Initial standard password set | Noor@z@m1982 | System Admin |

---

## 📞 **SUPPORT**

Jika ada masalah:
- Email: support@keda.gov.my
- Tel: XXX-XXXXXXX

---

**Status**: ✅ Ready to Execute  
**Security**: ✅ Password Hashed with Bcrypt  
**Affected Records**: All staff in database  

---

**⚠️ PENTING**: Sila backup database sebelum run SQL update!

```bash
# Backup command
mysqldump -u root -pNoor@z@m1982 myapps > myapps_backup_$(date +%Y%m%d_%H%M%S).sql
```

