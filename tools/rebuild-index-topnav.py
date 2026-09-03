from pathlib import Path

uri = Path(r'd:\myapps\server\myapps\assets\logo-data-uri.txt').read_text(encoding='utf-8').strip()

html = f'''<!DOCTYPE html>
<html lang="ms">
<head>
  <base target="_top">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0B1F3A">
  <title><?= appName ?></title>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
  <style><?!= include('Styles'); ?></style>
</head>
<body>

  <!-- AUTH GATE -->
  <div id="viewAuthGate" class="login-wrap">
    <div class="login-card">
      <img class="login-logo" src="{uri}" alt="Logo rasmi KEDA" width="120" height="120">
      <div class="login-brand">
        <h1><?= appName ?></h1>
        <p><?= appTagline ?></p>
      </div>
      <div id="authAlert" class="alert alert-info" style="text-align:center">
        Memuatkan akaun Google anda...
      </div>
      <div id="googleSignInWrap" class="google-signin-wrap hidden">
        <div id="googleSignInBtn"></div>
      </div>
      <p class="login-hint">
        Log masuk dengan akaun Google berdaftar<br>
        (@keda.gov.my atau Gmail peribadi dalam rekod MyApps)
      </p>
      <button class="btn-keda hidden" style="margin-top:14px" id="btnRetryAuth" onclick="bootstrap()">CUBA SEMULA</button>
      <div class="login-foot">&copy; Lembaga Kemajuan Wilayah Kedah</div>
    </div>
  </div>

  <!-- APP — menu atas seperti Koop KEDA -->
  <div id="viewApp" class="hidden app-shell">
    <header class="app-header">
      <div class="header-inner">
        <div class="brand-block">
          <img class="header-logo" src="{uri}" alt="Logo rasmi KEDA" width="56" height="56">
          <div class="brand-text">
            <h1><?= appName ?></h1>
            <p><?= appTagline ?></p>
          </div>
        </div>
        <div class="header-user">
          <div class="user-chip" id="userChip"></div>
          <button class="btn-logout" type="button" onclick="doLogout()">Log Keluar</button>
        </div>
      </div>
    </header>

    <nav class="app-nav" aria-label="Menu utama">
      <div class="nav-inner" id="navList">
        <button type="button" class="nav-pill active" data-page="apps" onclick="navigate('apps')">Semua Aplikasi</button>
        <button type="button" class="nav-pill" data-page="apps-dalaman" onclick="navigate('apps-dalaman')">Aplikasi Dalaman</button>
        <button type="button" class="nav-pill" data-page="apps-luaran" onclick="navigate('apps-luaran')">Aplikasi Luaran</button>
        <button type="button" class="nav-pill" data-page="apps-gunasama" onclick="navigate('apps-gunasama')">Aplikasi Gunasama</button>
        <button type="button" class="nav-pill hidden" id="navAdmin" data-page="admin-apps" onclick="navigate('admin-apps')">Urus Aplikasi</button>
        <button type="button" class="nav-pill hidden" id="navUsers" data-page="admin-users" onclick="navigate('admin-users')">Urus Pengguna</button>
      </div>
    </nav>

    <div class="app-body">
      <div class="page-head">
        <h2 id="pageTitle">Direktori Aplikasi</h2>
        <div id="topbarActions"></div>
      </div>
      <div class="content" id="pageContent"></div>
    </div>
  </div>

  <button class="chat-fab hidden" id="chatFab" onclick="toggleChat()" aria-label="Pembantu Maya">💬</button>
  <div class="chat-panel hidden" id="chatPanel">
    <div class="chat-header">Pembantu Maya KEDA</div>
    <div class="chat-messages" id="chatMessages">
      <div class="chat-msg bot">Assalamualaikum! Saya Pembantu Maya KEDA. Apa yang boleh saya bantu?</div>
    </div>
    <div class="chat-input-row">
      <input id="chatInput" placeholder="Taip soalan..." onkeydown="if(event.key==='Enter')sendChat()">
      <button type="button" onclick="sendChat()">Hantar</button>
    </div>
  </div>

  <div class="modal-overlay hidden" id="modalOverlay" onclick="if(event.target===this)closeModal()">
    <div class="modal" id="modalBody"></div>
  </div>

  <script>
    window.INITIAL_PAGE = '<?= initialPage ?>';
  </script>
  <?!= include('JavaScript'); ?>
</body>
</html>
'''

Path(r'd:\myapps\Index.html').write_text(html, encoding='utf-8')
print('Index.html written', Path(r'd:\myapps\Index.html').stat().st_size)
