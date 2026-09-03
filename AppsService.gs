/**
 * Direktori Aplikasi
 */

function enrichApp_(a) {
  var idKat = toInt_(a.id_kategori);
  return {
    id_aplikasi: toInt_(a.id_aplikasi),
    nama_aplikasi: String(a.nama_aplikasi || ''),
    id_kategori: idKat,
    nama_kategori: KATEGORI[idKat] || lookupName_('kategori', idKat) || '',
    keterangan: String(a.keterangan || ''),
    url: String(a.url || ''),
    warna_bg: KATEGORI_WARNA[idKat] || String(a.warna_bg || '#2F6FED'),
    sso_comply: toInt_(a.sso_comply, 0),
    status: toInt_(a.status, 1)
  };
}

function apiGetAppsDashboard(sessionToken, filters) {
  try {
    requireAuth_(sessionToken);
    filters = filters || {};
    var search = String(filters.search || '').toLowerCase();
    var kategori = filters.kategori ? toInt_(filters.kategori) : 0;
    var ssoOnly = !!filters.sso;

    var all = sheetToObjects_(SHEETS.APLIKASI)
      .filter(function (a) { return toInt_(a.status, 1) === 1; })
      .map(enrichApp_);

    var stats = {
      semua: all.length,
      dalaman: all.filter(function (a) { return a.id_kategori === 1; }).length,
      luaran: all.filter(function (a) { return a.id_kategori === 2; }).length,
      gunasama: all.filter(function (a) { return a.id_kategori === 3; }).length,
      sso: all.filter(function (a) { return a.sso_comply === 1; }).length
    };

    var chart = [1, 2, 3].map(function (id) {
      return {
        id_kategori: id,
        nama_kategori: KATEGORI[id],
        total: all.filter(function (a) { return a.id_kategori === id; }).length
      };
    });

    var list = all.filter(function (a) {
      if (kategori && a.id_kategori !== kategori) return false;
      if (ssoOnly && a.sso_comply !== 1) return false;
      if (!search) return true;
      return (
        a.nama_aplikasi.toLowerCase().indexOf(search) >= 0 ||
        a.keterangan.toLowerCase().indexOf(search) >= 0 ||
        a.nama_kategori.toLowerCase().indexOf(search) >= 0
      );
    });

    list.sort(function (a, b) {
      if (a.id_kategori !== b.id_kategori) return a.id_kategori - b.id_kategori;
      if (b.sso_comply !== a.sso_comply) return b.sso_comply - a.sso_comply;
      return a.nama_aplikasi.localeCompare(b.nama_aplikasi);
    });

    return ok_({ stats: stats, chart: chart, apps: list, kategori: KATEGORI });
  } catch (e) {
    return fail_(e.message);
  }
}

function apiSaveApp(sessionToken, payload) {
  try {
    var s = requireAuth_(sessionToken);
    if (!isAdminRole_(s.role)) return fail_('Anda tidak dibenarkan mengurus aplikasi.');

    payload = payload || {};
    var nama = upper_(payload.nama_aplikasi);
    var idKat = toInt_(payload.id_kategori);
    var keterangan = upper_(payload.keterangan);
    var url = String(payload.url || '').trim();
    var sso = payload.sso_comply ? 1 : 0;
    var status = payload.status != null ? toInt_(payload.status, 1) : 1;
    var id = payload.id_aplikasi ? toInt_(payload.id_aplikasi) : 0;

    if (!nama || !idKat) return fail_('Nama aplikasi dan kategori diperlukan.');

    var warna = KATEGORI_WARNA[idKat] || '#007bff';
    if (id) {
      if (!findById_(SHEETS.APLIKASI, 'id_aplikasi', id)) return fail_('Aplikasi tidak dijumpai.');
      updateRowById_(SHEETS.APLIKASI, 'id_aplikasi', id, {
        nama_aplikasi: nama,
        id_kategori: idKat,
        keterangan: keterangan,
        url: url,
        warna_bg: warna,
        sso_comply: sso,
        status: status
      });
      logAudit_('UPDATE', 'Aplikasi', id, nama, s.user_id);
      return ok_({ message: 'Aplikasi berjaya dikemas kini.', id_aplikasi: id });
    }

    id = nextId_(SHEETS.APLIKASI, 'id_aplikasi');
    appendObject_(SHEETS.APLIKASI, {
      id_aplikasi: id,
      nama_aplikasi: nama,
      id_kategori: idKat,
      keterangan: keterangan,
      url: url,
      warna_bg: warna,
      sso_comply: sso,
      status: 1,
      created_at: nowIso_()
    });
    logAudit_('CREATE', 'Aplikasi', id, nama, s.user_id);
    return ok_({ message: 'Aplikasi berjaya ditambah.', id_aplikasi: id });
  } catch (e) {
    return fail_(e.message);
  }
}

function apiDeleteApp(sessionToken, idAplikasi) {
  try {
    var s = requireAuth_(sessionToken);
    if (!isAdminRole_(s.role)) return fail_('Anda tidak dibenarkan memadam aplikasi.');
    idAplikasi = toInt_(idAplikasi);
    if (!findById_(SHEETS.APLIKASI, 'id_aplikasi', idAplikasi)) return fail_('Aplikasi tidak dijumpai.');
    updateRowById_(SHEETS.APLIKASI, 'id_aplikasi', idAplikasi, { status: 0 });
    logAudit_('SOFT_DELETE', 'Aplikasi', idAplikasi, '', s.user_id);
    return ok_({ message: 'Aplikasi dinyahaktifkan.' });
  } catch (e) {
    return fail_(e.message);
  }
}

function apiExportAppsCsv(sessionToken, kategori) {
  try {
    requireAuth_(sessionToken);
    var res = apiGetAppsDashboard(sessionToken, { kategori: kategori || '' });
    if (!res.success) return res;
    var headers = ['ID', 'NAMA APLIKASI', 'KATEGORI', 'KETERANGAN', 'URL', 'SSO'];
    var rows = res.data.apps.map(function (a) {
      return [a.id_aplikasi, a.nama_aplikasi, a.nama_kategori, a.keterangan, a.url, a.sso_comply ? 'SSO' : ''];
    });
    return ok_({ csv: rowsToCsv_(headers, rows), filename: 'Direktori_Aplikasi_' + todayYmd_() + '.csv' });
  } catch (e) {
    return fail_(e.message);
  }
}
