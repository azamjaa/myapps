# MyApps KEDA

Portal direktori aplikasi KEDA — Google Apps Script + Google Sheet + iframe server.

## URL

- **Portal:** https://www.keda.gov.my/myapps/
- **Apps Script:** [Editor](https://script.google.com/d/1YCsO6LzCbsEqvQ-olB-V8IWAvGWHop229mSH8RAzmLJ7JsDrqyoGQr78/edit)

## Struktur

```
├── *.gs, *.html     # Kod Google Apps Script (clasp)
├── server/myapps/   # Halaman iframe (upload ke server KEDA)
└── tools/           # Skrip utiliti deploy
```

## Deploy Apps Script

```bash
clasp push
clasp deploy
```

## Deploy server (WinSCP)

Upload ke `/var/www/html/keda2026/myapps/`:
- `server/myapps/index.html`
- `server/myapps/.htaccess`
