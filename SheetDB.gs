/**
 * Akses Google Spreadsheet (ganti MySQL)
 */

function getSpreadsheet_() {
  var props = PropertiesService.getScriptProperties();
  var stored = props.getProperty(PROP_SPREADSHEET_ID);
  var ids = [];
  if (stored) ids.push(stored);
  if (DEFAULT_SPREADSHEET_ID && ids.indexOf(DEFAULT_SPREADSHEET_ID) < 0) ids.push(DEFAULT_SPREADSHEET_ID);

  var lastErr = '';
  for (var i = 0; i < ids.length; i++) {
    try {
      var ss = SpreadsheetApp.openById(ids[i]);
      if (stored !== ids[i]) {
        props.setProperty(PROP_SPREADSHEET_ID, ids[i]);
      }
      return ss;
    } catch (e) {
      lastErr = String(e && e.message || e);
    }
  }
  throw new Error(
    'Spreadsheet MyApps tidak boleh dibuka. ' +
    (lastErr || 'Jalankan setup sekali: ' + DEFAULT_APP_URL + '?setup=1&key=' + SETUP_KEY)
  );
}

function getSheet_(name) {
  var ss = getSpreadsheet_();
  var sh = ss.getSheetByName(name);
  if (!sh) throw new Error('Sheet tidak dijumpai: ' + name);
  return sh;
}

function sheetToObjects_(sheetName) {
  var sh = getSheet_(sheetName);
  var values = sh.getDataRange().getValues();
  if (!values.length) return [];
  var headers = values[0].map(function (h) { return String(h || '').trim(); });
  var out = [];
  for (var r = 1; r < values.length; r++) {
    var row = values[r];
    if (!row.some(function (c) { return c !== '' && c != null; })) continue;
    var obj = {};
    for (var c = 0; c < headers.length; c++) {
      if (!headers[c]) continue;
      var v = row[c];
      if (v instanceof Date) v = v.toISOString();
      obj[headers[c]] = v;
    }
    out.push(obj);
  }
  return out;
}

function findById_(sheetName, idField, id) {
  id = toInt_(id);
  var rows = sheetToObjects_(sheetName);
  for (var i = 0; i < rows.length; i++) {
    if (toInt_(rows[i][idField]) === id) return rows[i];
  }
  return null;
}

function findRowIndex_(sheetName, idField, id) {
  var sh = getSheet_(sheetName);
  var values = sh.getDataRange().getValues();
  if (!values.length) return -1;
  var headers = values[0];
  var col = headers.indexOf(idField);
  if (col < 0) return -1;
  id = toInt_(id);
  for (var r = 1; r < values.length; r++) {
    if (toInt_(values[r][col]) === id) return r + 1;
  }
  return -1;
}

function updateRowById_(sheetName, idField, id, updates) {
  var sh = getSheet_(sheetName);
  var rowNum = findRowIndex_(sheetName, idField, id);
  if (rowNum < 0) throw new Error('Rekod tidak dijumpai.');
  var headers = sh.getRange(1, 1, 1, sh.getLastColumn()).getValues()[0];
  Object.keys(updates).forEach(function (key) {
    var col = headers.indexOf(key);
    if (col >= 0) sh.getRange(rowNum, col + 1).setValue(updates[key]);
  });
}

function appendObject_(sheetName, obj) {
  var sh = getSheet_(sheetName);
  var headers = sh.getRange(1, 1, 1, sh.getLastColumn()).getValues()[0];
  var row = headers.map(function (h) {
    return obj.hasOwnProperty(h) ? obj[h] : '';
  });
  sh.appendRow(row);
}

function nextId_(sheetName, idField) {
  var rows = sheetToObjects_(sheetName);
  var max = 0;
  rows.forEach(function (r) {
    var n = toInt_(r[idField]);
    if (n > max) max = n;
  });
  return max + 1;
}

function logAudit_(action, tableAffected, recordId, detail, userId) {
  try {
    appendObject_(SHEETS.AUDIT, {
      created_at: nowIso_(),
      user_id: userId || '',
      action: action,
      table_affected: tableAffected,
      record_id: recordId || '',
      detail: detail || ''
    });
  } catch (e) {
    Logger.log('Audit log gagal: ' + e.message);
  }
}

function getLookupsByType_(type) {
  return sheetToObjects_(SHEETS.LOOKUPS)
    .filter(function (r) { return String(r.type) === type; })
    .map(function (r) {
      return { id: toInt_(r.id), name: String(r.name || ''), extra: String(r.extra || '') };
    })
    .sort(function (a, b) { return a.name.localeCompare(b.name); });
}

function apiGetLookups(sessionToken) {
  try {
    requireAuth_(sessionToken);
    return ok_({
      jawatan: getLookupsByType_('jawatan'),
      gred: getLookupsByType_('gred'),
      bahagian: getLookupsByType_('bahagian'),
      kategori: getLookupsByType_('kategori'),
      status_staf: getLookupsByType_('status_staf')
    });
  } catch (e) {
    return fail_(e.message);
  }
}
