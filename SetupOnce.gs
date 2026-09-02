/**
 * Jalankan sekali selepas salin kod ke Apps Script.
 * 1. Tampal SPREADSHEET_ID dan EMEL_ADMIN di bawah
 * 2. Run: MULA_SETUP()
 * 3. Deploy Web App (lihat MULA-DARI-SIFAR.txt)
 */

var SETUP_SPREADSHEET_ID = '1ZwQ3lDESC0sR4YLziHy2RuEvxxBf9zyzt3b_G5Xb6D0';
var SETUP_ADMIN_EMAIL = 'azam@keda.gov.my';
var SETUP_WEBAPP_URL = 'https://script.google.com/macros/s/AKfycbxpDPSF8z4q6ZJjtniCPlOVR_5HhOnR1gBz_LtQsNhT2slAwAYVND8TZI42AJcHV6y0Og/exec';

function MULA_SETUP() {
  if (SETUP_SPREADSHEET_ID.indexOf('TAMPAL') >= 0) {
    throw new Error('Sila edit SETUP_SPREADSHEET_ID dalam SetupOnce.gs');
  }
  if (SETUP_ADMIN_EMAIL.indexOf('emel-google') >= 0) {
    throw new Error('Sila edit SETUP_ADMIN_EMAIL dalam SetupOnce.gs');
  }

  var report = SETUP_MYAPPS(SETUP_SPREADSHEET_ID);
  var admin = PROMOTE_ADMIN_BY_EMAIL(SETUP_ADMIN_EMAIL);

  var result = {
    setup: report,
    admin: admin,
    verify: VERIFY_SETUP()
  };

  if (SETUP_WEBAPP_URL.indexOf('TAMPAL') < 0) {
    result.appUrl = SET_WEBAPP_URL(SETUP_WEBAPP_URL);
  } else {
    result.appUrl = 'Lewatkan SETUP_WEBAPP_URL selepas deploy, kemudian run SET_URL_SELEPAS_DEPLOY()';
  }

  if (typeof GOOGLE_OAUTH_CLIENT_ID !== 'undefined' && GOOGLE_OAUTH_CLIENT_ID) {
    result.googleClientId = setGoogleClientId(GOOGLE_OAUTH_CLIENT_ID);
  } else if (!getGoogleClientId_()) {
    result.googleClientId = 'PENTING: Jalankan setGoogleClientId("CLIENT_ID_WEB.apps.googleusercontent.com") — salin dari GCP Credentials';
  }

  Logger.log(JSON.stringify(result, null, 2));
  return result;
}

function SET_URL_SELEPAS_DEPLOY() {
  if (SETUP_WEBAPP_URL.indexOf('TAMPAL') >= 0) {
    throw new Error('Sila edit SETUP_WEBAPP_URL dalam SetupOnce.gs');
  }
  return SET_WEBAPP_URL(SETUP_WEBAPP_URL);
}

function shareSheetWithRegisteredUsers_() {
  var ssId = PropertiesService.getScriptProperties().getProperty(PROP_SPREADSHEET_ID) || DEFAULT_SPREADSHEET_ID;
  var file = DriveApp.getFileById(ssId);
  var users = sheetToObjects_(SHEETS.USERS).filter(function (u) {
    return toInt_(u.aktif, 1) === 1 && String(u.emel || '').indexOf('@') > 0;
  });
  var shared = 0;
  var errors = [];
  users.forEach(function (u) {
    var emel = lower_(String(u.emel || '').trim());
    if (!emel) return;
    try {
      file.addViewer(emel);
      shared++;
    } catch (e) {
      errors.push({ emel: emel, error: String(e.message || e) });
    }
  });
  return { shared: shared, total: users.length, errors: errors.slice(0, 10) };
}

function SETUP_MYAPPS(spreadsheetId) {
  if (!spreadsheetId) {
    throw new Error('Sila beri Spreadsheet ID. Contoh: SETUP_MYAPPS("abc123...")');
  }
  PropertiesService.getScriptProperties().setProperty(PROP_SPREADSHEET_ID, spreadsheetId);

  var ss = SpreadsheetApp.openById(spreadsheetId);
  var needed = [
    SHEETS.USERS, SHEETS.LOOKUPS, SHEETS.APLIKASI, SHEETS.ROLES,
    SHEETS.FAQ, SHEETS.PENCAPAIAN, SHEETS.AUDIT
  ];
  var report = { spreadsheetId: spreadsheetId, sheets: [] };
  needed.forEach(function (name) {
    var sh = ss.getSheetByName(name);
    report.sheets.push({ name: name, exists: !!sh, rows: sh ? Math.max(0, sh.getLastRow() - 1) : 0 });
  });

  Logger.log(JSON.stringify(report, null, 2));
  return report;
}

function SET_WEBAPP_URL(url) {
  return setAppUrl(url || '');
}

function VERIFY_SETUP() {
  var id = PropertiesService.getScriptProperties().getProperty(PROP_SPREADSHEET_ID);
  if (!id) return { ok: false, error: 'SPREADSHEET_ID belum diset' };
  try {
    var ss = SpreadsheetApp.openById(id);
    return {
      ok: true,
      name: ss.getName(),
      url: ss.getUrl(),
      appUrl: PropertiesService.getScriptProperties().getProperty(PROP_APP_URL) || '',
      allowedDomain: ALLOWED_EMAIL_DOMAIN || '(semua emel berdaftar)',
      googleClientIdSet: !!getGoogleClientId_()
    };
  } catch (e) {
    return { ok: false, error: e.message };
  }
}

function PROMOTE_ADMIN_BY_EMAIL(emel) {
  emel = lower_(emel);
  if (!emel) throw new Error('Emel diperlukan.');

  var user = findUserByEmail_(emel);
  if (!user) throw new Error('Pengguna dengan emel ' + emel + ' tidak dijumpai dalam sheet Users.');

  updateRowById_(SHEETS.USERS, 'id_user', user.id_user, {
    role: ROLES.SUPER_ADMIN,
    aktif: 1,
    id_status_staf: 1
  });
  return 'Super admin: ' + user.nama + ' (' + emel + ')';
}
