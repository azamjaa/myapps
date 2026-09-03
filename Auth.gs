/**
 * Autentikasi Google SSO
 * Deploy: Execute as = Me (USER_DEPLOYING), Who has access = Anyone / domain
 * Emel disahkan via Google ID token + session; sheet dibaca sebagai deployer.
 */

var PROP_GOOGLE_CLIENT_ID = 'GOOGLE_OAUTH_CLIENT_ID';
var SESSION_CACHE_PREFIX = 'myapps_sess:';
var SESSION_TTL_SEC = 21600; // 6 jam (max Script Cache)

function getGoogleClientId_() {
  return PropertiesService.getScriptProperties().getProperty(PROP_GOOGLE_CLIENT_ID)
    || (typeof GOOGLE_OAUTH_CLIENT_ID !== 'undefined' ? GOOGLE_OAUTH_CLIENT_ID : '')
    || '';
}

function setGoogleClientId(id) {
  PropertiesService.getScriptProperties().setProperty(PROP_GOOGLE_CLIENT_ID, String(id || '').trim());
  return 'GOOGLE_OAUTH_CLIENT_ID diset: ' + id;
}

function extractClientIdFromResource_(name) {
  if (!name) return '';
  var parts = String(name).split('/');
  var last = parts[parts.length - 1] || '';
  if (last.indexOf('.apps.googleusercontent.com') >= 0) return last;
  return '';
}

function getGcpProjectNumber_() {
  var token = ScriptApp.getOAuthToken();
  var scriptId = ScriptApp.getScriptId();
  var resp = UrlFetchApp.fetch('https://script.googleapis.com/v1/projects/' + scriptId, {
    headers: { Authorization: 'Bearer ' + token },
    muteHttpExceptions: true
  });
  if (resp.getResponseCode() !== 200) return '';
  var data = JSON.parse(resp.getContentText());
  return String(data.parentId || data.scriptProjectNumber || data.scriptId || '');
}

function autoDiscoverGoogleClientId_() {
  if (getGoogleClientId_()) {
    return { ok: true, clientId: getGoogleClientId_(), source: 'existing' };
  }

  var token = ScriptApp.getOAuthToken();
  var projectNumber = getGcpProjectNumber_();
  if (!projectNumber) {
    return { ok: false, error: 'GCP project number tidak dijumpai.' };
  }

  var listUrl = 'https://iam.googleapis.com/v1/projects/' + projectNumber + '/locations/global/oauthClients';
  var resp = UrlFetchApp.fetch(listUrl, {
    headers: { Authorization: 'Bearer ' + token },
    muteHttpExceptions: true
  });

  if (resp.getResponseCode() === 200) {
    var data = JSON.parse(resp.getContentText());
    var clients = data.oauthClients || [];
    var preferred = null;
    for (var i = 0; i < clients.length; i++) {
      var c = clients[i];
      var label = String(c.displayName || c.title || '').toLowerCase();
      var cid = extractClientIdFromResource_(c.name);
      if (!cid) continue;
      if (label.indexOf('apps script') >= 0 || label.indexOf('web client') >= 0) {
        preferred = cid;
        break;
      }
      if (!preferred && label.indexOf('web') >= 0) preferred = cid;
      if (!preferred) preferred = cid;
    }
    if (preferred) {
      setGoogleClientId(preferred);
      return { ok: true, clientId: preferred, source: 'iam-list', projectNumber: projectNumber };
    }
  }

  var createUrl = listUrl;
  var createBody = {
    displayName: 'MyApps KEDA Web Sign-In',
    allowedGrantTypes: ['AUTHORIZATION_CODE_GRANT', 'IMPLICIT_GRANT'],
    allowedRedirectUris: [
      'https://script.google.com/macros/' + ScriptApp.getScriptId() + '/usercallback',
      'https://script.google.com/macros/s/' + ScriptApp.getScriptId() + '/usercallback'
    ],
    allowedOrigins: ['https://script.google.com']
  };
  var createResp = UrlFetchApp.fetch(createUrl, {
    method: 'post',
    contentType: 'application/json',
    headers: { Authorization: 'Bearer ' + token },
    payload: JSON.stringify(createBody),
    muteHttpExceptions: true
  });
  if (createResp.getResponseCode() >= 200 && createResp.getResponseCode() < 300) {
    var created = JSON.parse(createResp.getContentText());
    var newId = extractClientIdFromResource_(created.name);
    if (newId) {
      setGoogleClientId(newId);
      return { ok: true, clientId: newId, source: 'iam-create', projectNumber: projectNumber };
    }
  }

  return {
    ok: false,
    error: 'Auto-discover Client ID gagal. Jalankan setGoogleClientId() manual dari GCP Credentials.',
    projectNumber: projectNumber,
    iamListStatus: resp.getResponseCode(),
    iamCreateStatus: createResp.getResponseCode(),
    iamCreateBody: createResp.getContentText().slice(0, 500)
  };
}

function SETUP_GOOGLE_CLIENT() {
  var result = autoDiscoverGoogleClientId_();
  Logger.log(JSON.stringify(result, null, 2));
  return result;
}

function apiGetAuthConfig() {
  return ok_({
    clientId: getGoogleClientId_(),
    appName: APP_NAME
  });
}

function verifyGoogleIdToken_(idToken) {
  if (!idToken) throw new Error('Token Google diperlukan.');
  var resp = UrlFetchApp.fetch(
    'https://oauth2.googleapis.com/tokeninfo?id_token=' + encodeURIComponent(idToken),
    { muteHttpExceptions: true }
  );
  if (resp.getResponseCode() !== 200) {
    throw new Error('Token Google tidak sah. Sila log masuk semula.');
  }
  var data = JSON.parse(resp.getContentText());
  if (data.error) throw new Error('Token Google tidak sah.');
  if (data.exp && Number(data.exp) * 1000 < Date.now()) {
    throw new Error('Sesi Google telah tamat. Sila log masuk semula.');
  }
  if (!data.email) throw new Error('Emel tidak dijumpai dalam token Google.');
  var clientId = getGoogleClientId_();
  if (clientId && data.aud && data.aud !== clientId) {
    throw new Error('Token Google tidak sah untuk aplikasi ini.');
  }
  if (String(data.email_verified) !== 'true') {
    throw new Error('Emel Google anda belum disahkan.');
  }
  return lower_(data.email);
}

function createSession_(email) {
  var token = 'sess_' + Utilities.getUuid().replace(/-/g, '');
  CacheService.getScriptCache().put(SESSION_CACHE_PREFIX + token, email, SESSION_TTL_SEC);
  return token;
}

function destroySession_(sessionToken) {
  if (sessionToken) {
    CacheService.getScriptCache().remove(SESSION_CACHE_PREFIX + sessionToken);
  }
}

function getEmailFromSession_(sessionToken) {
  if (!sessionToken) return '';
  return CacheService.getScriptCache().get(SESSION_CACHE_PREFIX + sessionToken) || '';
}

function getGoogleEmailLegacy_() {
  var email = '';
  try { email = Session.getActiveUser().getEmail(); } catch (e) {}
  // Jangan guna EffectiveUser — bila Execute as: Me ia sentiasa pemilik script
  return lower_(email);
}

function findUserByEmail_(email) {
  if (!email) return null;
  var users = sheetToObjects_(SHEETS.USERS);
  for (var i = 0; i < users.length; i++) {
    if (lower_(users[i].emel) === email) return users[i];
  }
  return null;
}

function isEmailDomainAllowed_(email) {
  if (!ALLOWED_EMAIL_DOMAIN) return true;
  return email.indexOf('@' + ALLOWED_EMAIL_DOMAIN) >= 0;
}

function buildAuthContext_(email) {
  if (!email) {
    throw new Error(
      'Sila log masuk dengan akaun Google anda. ' +
      'Pastikan Web App: Execute as = User accessing the app, Who has access = Anyone.'
    );
  }
  if (!isEmailDomainAllowed_(email)) {
    throw new Error('Akaun ' + email + ' tidak dibenarkan untuk domain ini.');
  }
  var user = findUserByEmail_(email);
  if (!user) {
    throw new Error(
      'Akaun Google ' + email + ' tidak berdaftar dalam MyApps. ' +
      'Pastikan emel anda sama seperti dalam rekod staf. Sila hubungi pentadbir.'
    );
  }
  if (toInt_(user.id_status_staf, 0) !== 1 || toInt_(user.aktif, 1) !== 1) {
    throw new Error('Akaun anda tidak aktif. Sila hubungi pentadbir sistem.');
  }
  return {
    user_id: toInt_(user.id_user),
    nama: String(user.nama || ''),
    role: String(user.role || ROLES.USER),
    emel: email,
    gambar_url: String(user.gambar_url || ''),
    no_kp: normalizeNoKp_(user.no_kp)
  };
}

/**
 * Sahkan sesi — token Google Sign-In atau sesi cache
 */
function requireAuth_(sessionToken) {
  var email = getGoogleEmailLegacy_() || getEmailFromSession_(sessionToken);
  return buildAuthContext_(email);
}

function isAdminRole_(role) {
  return role === ROLES.SUPER_ADMIN || role === ROLES.ADMIN;
}

function apiLoginWithGoogle(idToken) {
  try {
    var email = verifyGoogleIdToken_(idToken);
    var ctx = buildAuthContext_(email);
    var sessionToken = createSession_(email);
    var user = findById_(SHEETS.USERS, 'id_user', ctx.user_id);
    updateRowById_(SHEETS.USERS, 'id_user', ctx.user_id, { last_login: nowIso_() });
    logAudit_('LOGIN_GOOGLE', 'Users', ctx.user_id, email, ctx.user_id);
    return ok_({
      sessionToken: sessionToken,
      user: publicUser_(user),
      googleEmail: email
    });
  } catch (e) {
    return fail_(e.message);
  }
}

function apiGetMe(sessionToken) {
  try {
    var s = requireAuth_(sessionToken);
    var user = findById_(SHEETS.USERS, 'id_user', s.user_id);
    if (!user) return fail_('Pengguna tidak dijumpai.');
    var token = sessionToken || '';
    if (!getEmailFromSession_(token)) {
      token = createSession_(s.emel);
    }
    return ok_({ user: publicUser_(user), googleEmail: s.emel, sessionToken: token });
  } catch (e) {
    return fail_(e.message);
  }
}

function apiLogout(sessionToken) {
  destroySession_(sessionToken);
  return ok_({
    loggedOut: true,
    message: 'Anda telah log keluar. Sila log masuk semula dengan Google.',
    logoutUrl: 'https://accounts.google.com/Logout'
  });
}

function publicUser_(user) {
  return {
    id_user: toInt_(user.id_user),
    no_staf: String(user.no_staf || ''),
    no_kp: normalizeNoKp_(user.no_kp),
    nama: String(user.nama || ''),
    emel: String(user.emel || ''),
    telefon: String(user.telefon || ''),
    id_jawatan: toInt_(user.id_jawatan),
    id_gred: toInt_(user.id_gred),
    id_bahagian: toInt_(user.id_bahagian),
    jawatan: lookupName_('jawatan', user.id_jawatan),
    gred: lookupName_('gred', user.id_gred),
    bahagian: lookupName_('bahagian', user.id_bahagian),
    gambar_url: String(user.gambar_url || ''),
    id_status_staf: toInt_(user.id_status_staf, 1),
    role: String(user.role || ROLES.USER),
    isAdmin: isAdminRole_(String(user.role || '')),
    aktif: toInt_(user.aktif, 1) === 1,
    umur: getUmur_(user.no_kp),
    tarikh_lahir: getTarikhLahir_(user.no_kp)
  };
}
