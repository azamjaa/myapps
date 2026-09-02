/**
 * Entry point Web App
 */

function doGet(e) {
  e = e || {};
  var params = e.parameter || {};

  if (params.setup === '1' && params.key === SETUP_KEY) {
    return runRemoteSetup_(e);
  }

  var tpl = HtmlService.createTemplateFromFile('Index');
  tpl.appName = APP_NAME;
  tpl.appTagline = APP_TAGLINE;
  tpl.initialPage = params.page || '';

  return tpl.evaluate()
    .setTitle(APP_NAME)
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
    .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}

function runRemoteSetup_(e) {
  try {
    var params = e.parameter || {};
    var ssId = params.spreadsheetId || DEFAULT_SPREADSHEET_ID;
    PropertiesService.getScriptProperties().setProperty(PROP_SPREADSHEET_ID, ssId);
    PropertiesService.getScriptProperties().setProperty(PROP_APP_URL, DEFAULT_APP_URL);
    if (params.clientId) setGoogleClientId(params.clientId);
    var report = SETUP_MYAPPS(ssId);
    var admin = PROMOTE_ADMIN_BY_EMAIL('azam@keda.gov.my');
    var share = shareSheetWithRegisteredUsers_();
    return ContentService.createTextOutput(JSON.stringify({
      ok: true,
      report: report,
      admin: admin,
      share: share,
      verify: VERIFY_SETUP()
    }, null, 2)).setMimeType(ContentService.MimeType.JSON);
  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({
      ok: false,
      error: String(err && err.message || err)
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

function include(filename) {
  return HtmlService.createHtmlOutputFromFile(filename).getContent();
}

function setAppUrl(url) {
  PropertiesService.getScriptProperties().setProperty(PROP_APP_URL, String(url || ''));
  return 'APP_URL diset: ' + url;
}

function setSpreadsheetId(id) {
  PropertiesService.getScriptProperties().setProperty(PROP_SPREADSHEET_ID, String(id || ''));
  return 'SPREADSHEET_ID diset: ' + id;
}

function getAppInfo() {
  return ok_({
    name: APP_NAME,
    tagline: APP_TAGLINE,
    version: '1.0.0'
  });
}

function apiPing() {
  return ok_({ pong: true, time: new Date().toISOString() });
}
