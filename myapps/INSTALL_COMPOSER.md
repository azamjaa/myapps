# Arahan Install Composer & PhpSpreadsheet

## Langkah 1: Install Composer (Jika Belum Ada)

### Kaedah A: Menggunakan Laragon (Paling Mudah)
Laragon biasanya sudah ada Composer built-in. Sila:
1. Buka **Laragon Terminal** (klik kanan icon Laragon > Terminal)
2. Atau buka Command Prompt dan navigate ke: `d:\laragon\www\myapps`

### Kaedah B: Download Composer Manual
1. Download Composer dari: https://getcomposer.org/download/
2. Pilih **Composer-Setup.exe** untuk Windows
3. Install dan pastikan "Add to PATH" dipilih
4. Restart terminal/command prompt

### Kaedah C: Download composer.phar
1. Download `composer.phar` dari: https://getcomposer.org/download/
2. Letakkan di folder `d:\laragon\www\myapps\`
3. Gunakan: `php composer.phar install` (bukan `composer install`)

## Langkah 2: Install PhpSpreadsheet

### Menggunakan Composer (Disyorkan)

1. **Buka Terminal/Command Prompt** di folder:
   ```
   d:\laragon\www\myapps
   ```

2. **Jalankan command:**
   ```bash
   composer install
   ```
   
   Atau jika menggunakan `composer.phar`:
   ```bash
   php composer.phar install
   ```

3. **Tunggu sehingga selesai** - Composer akan:
   - Download PhpSpreadsheet
   - Download semua dependencies
   - Buat folder `vendor/`
   - Buat file `vendor/autoload.php`

4. **Verify Installation:**
   - Check folder `vendor/` wujud
   - Check file `vendor/autoload.php` wujud
   - Check folder `vendor/phpoffice/phpspreadsheet/` wujud

## Langkah 3: Refresh Halaman

Selepas `composer install` selesai:
1. Refresh halaman `pengurusan_rekod_dashboard.php`
2. Mesej amaran sepatutnya hilang
3. Upload Excel akan berfungsi

## Troubleshooting

### Error: "composer command not found"
**Penyelesaian:**
- Pastikan Composer sudah dipasang
- Restart terminal/command prompt
- Atau gunakan `php composer.phar` instead

### Error: "SSL certificate problem"
**Penyelesaian:**
```bash
composer config -g secure-http false
composer install
```

### Error: "Memory limit exhausted"
**Penyelesaian:**
```bash
php -d memory_limit=512M composer.phar install
```

### Error: "Could not find package"
**Penyelesaian:**
- Pastikan `composer.json` wujud di folder project
- Check internet connection
- Try: `composer clear-cache` kemudian `composer install`

### Masih Error Selepas Install?
1. Check file `vendor/autoload.php` wujud
2. Check folder `vendor/phpoffice/phpspreadsheet/` wujud
3. Check PHP version (perlu >= 7.4)
4. Check error log PHP untuk details

## Quick Start (Laragon Users)

Jika menggunakan Laragon, paling mudah:

1. Buka **Laragon Terminal**
2. Type:
   ```bash
   cd d:\laragon\www\myapps
   composer install
   ```
3. Tunggu selesai
4. Refresh browser

## Manual Check

Selepas install, verify dengan command:
```bash
cd d:\laragon\www\myapps
dir vendor
dir vendor\phpoffice
```

Jika folder wujud, installation berjaya!
