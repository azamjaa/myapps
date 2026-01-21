# 🚀 Quick Install PhpSpreadsheet

## ⚡ Cara Paling Mudah (Laragon)

1. **Buka Laragon Terminal:**
   - Klik kanan icon Laragon di system tray
   - Pilih "Terminal"
   - Atau tekan `Win + R`, type `cmd`, Enter

2. **Navigate ke folder:**
   ```bash
   cd d:\laragon\www\myapps
   ```

3. **Jalankan Composer:**
   ```bash
   composer install
   ```

4. **Tunggu selesai** (akan download ~5-10MB)

5. **Refresh browser** - Error akan hilang! ✅

---

## 📋 Apa Yang Akan Berlaku?

Selepas `composer install`:
- ✅ Folder `vendor/` akan dicipta
- ✅ File `vendor/autoload.php` akan dicipta  
- ✅ PhpSpreadsheet akan dipasang
- ✅ Upload Excel akan berfungsi

---

## ❓ Kenapa Perlu Composer?

- PhpSpreadsheet adalah **external library** (bukan built-in PHP)
- Composer adalah **package manager** untuk PHP (seperti npm untuk Node.js)
- Composer akan **download & install** library secara automatik
- Tanpa Composer, kita perlu download & install manual (susah)

---

## 🔍 Verify Installation

Selepas `composer install`, check:
```bash
dir vendor
dir vendor\phpoffice
```

Jika folder wujud = **BERJAYA!** 🎉

---

## ⚠️ Jika Composer Tidak Ditemui

**Option 1: Install Composer**
- Download: https://getcomposer.org/Composer-Setup.exe
- Install dan pilih "Add to PATH"
- Restart terminal

**Option 2: Download composer.phar**
- Download: https://getcomposer.org/download/
- Letakkan di `d:\laragon\www\myapps\`
- Gunakan: `php composer.phar install`

---

## 💡 Tips

- **Laragon** biasanya sudah ada Composer built-in
- Jika error, pastikan **internet connection** aktif
- Jika masih error, check **PHP version** (perlu >= 7.4)
