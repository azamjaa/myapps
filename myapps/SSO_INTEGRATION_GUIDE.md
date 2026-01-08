# Panduan Integrasi SSO MyApps KEDA

Dokumen ini menjelaskan cara integrate SSO MyApps KEDA ke aplikasi pihak ketiga (eTanah, ePelawat, dsb).

---

## 📋 Daftar Aplikasi di MyApps

Sebelum integrate, aplikasi anda mesti terdaftar di table `aplikasi` dengan:
- `app_key` (Client ID) - ID unik aplikasi
- `app_secret` (Client Secret) - Secret key untuk authentication
- `redirect_uri` - URL untuk redirect selepas login

**Contoh data:**
```sql
INSERT INTO aplikasi (app_key, app_secret, redirect_uri, nama_aplikasi, status)
VALUES ('ETANAH-KEDA', 'secret_key_123', 'http://etanah.example.com/callback.php', 'eTanah', 1);
```

---

## 🔧 Setup di Aplikasi Pihak Ketiga

### Step 1: Copy MyAppsSSO Library

Copy file `src/MyAppsSSO.php` ke aplikasi anda:
```
aplikasi-pihak-ketiga/
├── src/
│   └── MyAppsSSO.php
├── callback.php
└── index.php
```

### Step 2: Buat Config File (config/sso.php)

```php
<?php
// config/sso.php
return [
    'client_id' => 'ETANAH-KEDA',          // Client ID dari MyApps
    'client_secret' => 'secret_key_123',   // Client Secret dari MyApps
    'redirect_uri' => 'http://etanah.example.com/callback.php',
    'myapps_url' => 'http://localhost/myapps'  // URL MyApps anda
];
```

### Step 3: Buat Login Button (index.php)

```php
<?php
require_once 'src/MyAppsSSO.php';
$config = require 'config/sso.php';

use MyApps\MyAppsSSO;

$sso = new MyAppsSSO(
    $config['client_id'],
    $config['client_secret'],
    $config['redirect_uri'],
    $config['myapps_url']
);

$login_url = $sso->getLoginUrl();
?>

<!DOCTYPE html>
<html>
<head>
    <title>eTanah Login</title>
</head>
<body>
    <h1>eTanah</h1>
    <a href="<?php echo htmlspecialchars($login_url); ?>" style="padding: 10px 20px; background: #d32f2f; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">
        Log Masuk guna MyApps KEDA
    </a>
</body>
</html>
```

### Step 4: Buat Callback Handler (callback.php)

```php
<?php
session_start();

require_once 'src/MyAppsSSO.php';
$config = require 'config/sso.php';

use MyApps\MyAppsSSO;

$sso = new MyAppsSSO(
    $config['client_id'],
    $config['client_secret'],
    $config['redirect_uri'],
    $config['myapps_url']
);

// Ambil code dari URL parameter
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if (!$code) {
    die('Tiada authorization code');
}

// Step 1: Exchange code untuk access token
$token_response = $sso->getAccessToken($code);

if (isset($token_response['error'])) {
    die('Gagal dapatkan token: ' . $token_response['message']);
}

$access_token = $token_response['access_token'];

// Step 2: Dapatkan profil user
$user_profile = $sso->getUserProfile($access_token);

if (isset($user_profile['error'])) {
    die('Gagal dapatkan profil: ' . $user_profile['message']);
}

// Step 3: Login user di aplikasi anda
$_SESSION['user_id'] = $user_profile['sub'];
$_SESSION['user_name'] = $user_profile['name'];
$_SESSION['user_email'] = $user_profile['email'];
$_SESSION['user_position'] = $user_profile['position'];
$_SESSION['user_department'] = $user_profile['department'];
$_SESSION['access_token'] = $access_token;

// Redirect ke dashboard
header('Location: dashboard.php');
exit;
```

### Step 5: Paparkan Data User (dashboard.php)

```php
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>eTanah Dashboard</title>
</head>
<body>
    <h1>Selamat datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
    
    <h3>Profil Anda:</h3>
    <ul>
        <li>Nama: <?php echo htmlspecialchars($_SESSION['user_name']); ?></li>
        <li>Email: <?php echo htmlspecialchars($_SESSION['user_email']); ?></li>
        <li>Jawatan: <?php echo htmlspecialchars($_SESSION['user_position']); ?></li>
        <li>Bahagian: <?php echo htmlspecialchars($_SESSION['user_department']); ?></li>
    </ul>

    <a href="logout.php">Log Keluar</a>
</body>
</html>
```

### Step 6: Logout (logout.php)

```php
<?php
session_start();
session_destroy();
header('Location: index.php');
exit;
```

---

## 🔐 API Endpoints

### 1. Authorization Endpoint
**URL:** `http://localhost/myapps/sso/authorize.php`

**Parameters (GET):**
- `client_id` - Client ID anda
- `redirect_uri` - URL untuk redirect
- `response_type` - Mesti `code`
- `state` - Optional, untuk CSRF protection

**Response:** Redirect ke login form MyApps, kemudian kembali ke redirect_uri dengan parameter `code`

---

### 2. Token Endpoint
**URL:** `http://localhost/myapps/sso/token.php`

**Method:** POST

**Parameters:**
- `grant_type` - Mesti `authorization_code`
- `code` - Authorization code dari authorize endpoint
- `client_id` - Client ID anda
- `client_secret` - Client Secret anda

**Response (JSON):**
```json
{
    "access_token": "eyJ0...",
    "token_type": "Bearer",
    "expires_in": 3600
}
```

---

### 3. UserInfo Endpoint (SSOT)
**URL:** `http://localhost/myapps/api/userinfo.php`

**Method:** GET

**Headers:**
```
Authorization: Bearer <access_token>
```

**Response (JSON):**
```json
{
    "sub": 123,
    "name": "MOHANAD NOORAZAM BIN JAAFAR",
    "ic_no": "820426025349",
    "staff_no": "006181",
    "email": "azanm@keda.gov.my",
    "phone": "0195793994",
    "picture": "url_gambar",
    "department": "UNIT TEKNOLOGI MAKLUMAT",
    "position": "PEGAWAI TEKNOLOGI MAKLUMAT",
    "grade": "jbt"
}
```

---

## 📝 Contoh Lengkap Aplikasi

Sila rujuk folder `example-app/` untuk aplikasi contoh yang lengkap dengan semua file yang diperlukan.

---

## 🐛 Troubleshooting

### Error: "Gagal dapatkan token: invalid_grant"
- Pastikan `code` tidak sudah expired (5 minit)
- Pastikan `client_id` dan `client_secret` betul
- Jangan refresh page selepas dapat code (kerana code hanya boleh digunakan sekali)

### Error: "Gagal dapatkan profil: Tiada Token"
- Pastikan `access_token` betul dan tidak expired
- Pastikan header `Authorization: Bearer <token>` dihantar

### Error: CORS / Header Issue
- Pastikan `.htaccess` atau server config pass header Authorization ke PHP
- MyApps sudah set CORS headers, jadi cross-origin request sepatutnya OK

---

## 📞 Support

Untuk soalan, hubungi: IT Department KEDA

