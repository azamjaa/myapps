/**
 * Utiliti umum
 */

function ok_(data) {
  return { success: true, data: data || {} };
}

function fail_(message) {
  return { success: false, message: String(message || 'Ralat tidak diketahui.') };
}

function toInt_(v, def) {
  var n = parseInt(v, 10);
  return isNaN(n) ? (def != null ? def : 0) : n;
}

function lower_(s) {
  return String(s || '').trim().toLowerCase();
}

function upper_(s) {
  return String(s || '').trim().toUpperCase();
}

function nowIso_() {
  return new Date().toISOString();
}

function todayYmd_() {
  var d = new Date();
  return Utilities.formatDate(d, Session.getScriptTimeZone(), 'yyyyMMdd');
}

function normalizeNoKp_(noKp) {
  var s = String(noKp || '').replace(/[-\s]/g, '').trim();
  if (s.indexOf('.') >= 0) s = s.split('.')[0];
  return s;
}

function lookupName_(type, id) {
  id = toInt_(id);
  if (!id) return '';
  var rows = sheetToObjects_(SHEETS.LOOKUPS);
  for (var i = 0; i < rows.length; i++) {
    if (String(rows[i].type) === type && toInt_(rows[i].id) === id) {
      return String(rows[i].name || '');
    }
  }
  return '';
}

function getUmur_(noKp) {
  var t = getTarikhLahir_(noKp);
  if (!t) return '';
  var parts = t.split('/');
  if (parts.length !== 3) return '';
  var y = parseInt(parts[2], 10);
  if (isNaN(y)) return '';
  return String(new Date().getFullYear() - y);
}

function getTarikhLahir_(noKp) {
  noKp = String(noKp || '').replace(/[-\s]/g, '');
  if (noKp.length < 6) return '';
  var yy = parseInt(noKp.substr(0, 2), 10);
  var mm = noKp.substr(2, 2);
  var dd = noKp.substr(4, 2);
  var year = yy >= 30 ? 1900 + yy : 2000 + yy;
  return dd + '/' + mm + '/' + year;
}

function rowsToCsv_(headers, rows) {
  var lines = [headers.join(',')];
  rows.forEach(function (row) {
    lines.push(row.map(function (cell) {
      var s = cell == null ? '' : String(cell);
      if (s.indexOf(',') >= 0 || s.indexOf('"') >= 0 || s.indexOf('\n') >= 0) {
        return '"' + s.replace(/"/g, '""') + '"';
      }
      return s;
    }).join(','));
  });
  return lines.join('\r\n');
}
