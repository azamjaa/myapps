/**
 * MyApps KEDA — Konfigurasi
 * Selepas upload myapps.xlsx ke Google Drive, set SPREADSHEET_ID di Script Properties
 * atau jalankan setSpreadsheetId('ID_SPREADSHEET_ANDA')
 */

var APP_NAME = 'MyApps KEDA';
var APP_TAGLINE = 'Direktori Aplikasi KEDA';

/** Google Sheet ID (myapps.xlsx) */
var DEFAULT_SPREADSHEET_ID = '1ZwQ3lDESC0sR4YLziHy2RuEvxxBf9zyzt3b_G5Xb6D0';
/** URL portal rasmi (tanpa www — perlukan IT samakan DNS) */
var DEFAULT_APP_URL = 'https://keda.gov.my/myapps/';

/** OAuth Web Client ID — salin dari GCP Credentials (Web client auto created by Apps Script) */
var GOOGLE_OAUTH_CLIENT_ID = '';
var SETUP_KEY = 'myapps-setup-2026';

/**
 * Sekatan domain emel (opsyenal).
 * Kosongkan '' = benarkan mana-mana emel Google selagi wujud dalam sheet Users
 * (termasuk Gmail/Yahoo peribadi staf).
 * Set nilai contoh 'keda.gov.my' jika mahu hadkan domain sahaja.
 */
var ALLOWED_EMAIL_DOMAIN = '';

var PROP_SPREADSHEET_ID = 'SPREADSHEET_ID';
var PROP_APP_URL = 'APP_URL';

var SHEETS = {
  USERS: 'Users',
  LOOKUPS: 'Lookups',
  APLIKASI: 'Aplikasi',
  ROLES: 'UserRoles',
  AUDIT: 'AuditLog',
  FAQ: 'ChatbotFAQ',
  PENCAPAIAN: 'Pencapaian'
};

var KATEGORI = {
  1: 'Dalaman',
  2: 'Luaran',
  3: 'Gunasama'
};

var KATEGORI_WARNA = {
  1: '#F39C12',
  2: '#E74C3C',
  3: '#6C3483'
};

var STATUS_STAF = {
  1: 'Masih Bekerja',
  2: 'Bersara',
  3: 'Berhenti'
};

var ROLES = {
  SUPER_ADMIN: 'super_admin',
  ADMIN: 'admin',
  USER: 'user_biasa'
};
