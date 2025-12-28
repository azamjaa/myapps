<?php
session_start();

// Kalau sudah login, terus redirect ke dashboard
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    header("Location: dashboard.php");
    exit;
}

// Kalau ada access_denied error
$error = '';
if (isset($_GET['akses_ditolak'])) {
    $error = '⚠️ Akses ditolak. Sila hubungi pentadbir sistem.';
}

// Redirect ke MyApps untuk SSO login
$mybooking_redirect = "http://localhost/mybooking/callback.php";
$myapps_login = "http://localhost/myapps/index.php?redirect=" . urlencode($mybooking_redirect);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyBooking KEDA - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
            border-radius: 15px 15px 0 0;
        }
        .login-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .login-header p {
            font-size: 14px;
            margin: 0;
            opacity: 0.9;
        }
        .login-body {
            padding: 40px;
        }
        .btn-sso {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            width: 100%;
        }
        .btn-sso:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="logo">
            <i class="fas fa-calendar-check text-primary"></i>
        </div>
        <h1>MyBooking KEDA</h1>
        <p>Sistem Tempahan Bilik Mesyuarat</p>
    </div>
    
    <div class="login-body">
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <p class="text-muted mb-4 text-center">
            Sila log masuk menggunakan akaun organisasi anda melalui MyApps
        </p>
        
        <a href="<?php echo $myapps_login; ?>" class="btn btn-sso">
            <i class="fas fa-sign-in-alt me-2"></i> Log Masuk via SSO
        </a>
        
        <hr class="my-4">
        
        <small class="text-muted d-block text-center">
            <i class="fas fa-info-circle me-1"></i>
            Anda akan diarahkan ke portal MyApps untuk pengesahan
        </small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
