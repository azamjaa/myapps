# Arahan Pemasangan PhpSpreadsheet

## Kaedah 1: Menggunakan Composer (Disyorkan)

### Langkah 1: Install Composer
Jika Composer belum dipasang, sila install terlebih dahulu:
- Windows: Download dari https://getcomposer.org/Composer-Setup.exe
- Atau download `composer.phar` dan letakkan di folder project

### Langkah 2: Install PhpSpreadsheet
Buka terminal/command prompt di folder `d:\laragon\www\myapps` dan jalankan:

```bash
composer install
```

Atau jika menggunakan `composer.phar`:

```bash
php composer.phar install
```

### Langkah 3: Verify Installation
Selepas install, folder `vendor/` akan wujud. Pastikan file berikut wujud:
- `vendor/autoload.php`
- `vendor/phpoffice/phpspreadsheet/`

## Kaedah 2: Manual Download (Alternatif)

Jika Composer tidak boleh digunakan, anda boleh download PhpSpreadsheet secara manual:

1. Download dari: https://github.com/PHPOffice/PhpSpreadsheet/releases
2. Extract ke folder `vendor/phpoffice/phpspreadsheet/`
3. Download dependencies yang diperlukan:
   - `psr/simple-cache` (https://github.com/php-fig/simple-cache)
   - `markbaker/matrix` (https://github.com/MarkBaker/PHPComplex)
   - `markbaker/complex` (https://github.com/MarkBaker/PHPComplex)
4. Buat file `vendor/autoload.php` atau gunakan autoloader manual

## Kaedah 3: Menggunakan Laragon (Jika menggunakan Laragon)

Laragon biasanya sudah ada Composer. Sila buka terminal Laragon dan jalankan:

```bash
cd d:\laragon\www\myapps
composer install
```

## Verify Installation

Selepas install, refresh halaman `pengurusan_rekod_dashboard.php`. Mesej amaran "PhpSpreadsheet library tidak ditemui" sepatutnya hilang.

## Troubleshooting

### Error: "composer command not found"
- Pastikan Composer sudah dipasang dan ditambah ke PATH
- Atau gunakan `php composer.phar` instead of `composer`

### Error: "Memory limit exhausted"
- Tambah `memory_limit = 512M` dalam `php.ini`
- Atau jalankan: `php -d memory_limit=512M composer.phar install`

### Error: "SSL certificate problem"
- Jalankan: `composer config -g secure-http false`
- Atau download secara manual
