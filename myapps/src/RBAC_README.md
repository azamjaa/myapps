# Integrasi RBAC MyApps (Centralized)

**Langkah Integrasi untuk Semua Aplikasi:**

1. Pastikan semua aplikasi dalam direktori MyApps ada field `kod_aplikasi` unik dalam table `aplikasi`.
2. Dalam setiap aplikasi, sebelum load page utama, panggil function RBAC berikut:

```php
require_once '/path/to/myapps/src/rbac_access_helper.php';

session_start();
$user_id = $_SESSION['user_id'] ?? null;
$app_code = 'KOD_APLIKASI_SAYA'; // Gantikan dengan kod aplikasi sebenar

if (!$user_id || !check_app_access($user_id, $app_code)) {
    http_response_code(403);
    echo 'Akses tidak dibenarkan.';
    exit;
}
```

3. Table `application_access` di MyApps akan kawal role mana boleh akses aplikasi mana.
4. Untuk aplikasi luar, boleh buat API endpoint di MyApps untuk semak akses jika perlu.

**Nota:**
- Semua kawalan role, permission, dan audit kekal centralized di MyApps.
- Anda hanya perlu maintain satu RBAC sahaja untuk semua aplikasi.
- Jika mahu SSO, pastikan session user konsisten antara aplikasi.

**Soalan lanjut?** Boleh tanya untuk contoh API atau integrasi lain.