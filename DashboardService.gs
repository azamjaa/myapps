/**
 * Dashboard Pencapaian KEDA
 */

function apiGetDashboardSummary(sessionToken, filters) {
  try {
    requireAuth_(sessionToken);
    filters = filters || {};
    var kategori = String(filters.kategori || '').trim();
    var daerah = String(filters.daerah || '').trim();
    var status = String(filters.status || '').trim();
    var cacheKey = 'dash_sum_' + [kategori, daerah, status].join('|');
    var cached = CacheService.getScriptCache().get(cacheKey);
    if (cached) {
      try { return ok_(JSON.parse(cached)); } catch (ignore) {}
    }

    var rows = sheetToObjects_(SHEETS.PENCAPAIAN);
    var filtered = rows.filter(function (r) {
      if (kategori && String(r.kategori || '').indexOf(kategori) < 0) return false;
      if (daerah && String(r.daerah || '') !== daerah) return false;
      if (status && String(r.status || '') !== status) return false;
      return true;
    });

    var byKategori = {};
    var byDaerah = {};
    var byStatus = {};
    filtered.forEach(function (r) {
      var kat = String(r.kategori || 'Lain-lain');
      var dae = String(r.daerah || 'Tidak Dinyatakan');
      var st = String(r.status || 'Tidak Dinyatakan');
      byKategori[kat] = (byKategori[kat] || 0) + 1;
      byDaerah[dae] = (byDaerah[dae] || 0) + 1;
      byStatus[st] = (byStatus[st] || 0) + 1;
    });

    var topKategori = Object.keys(byKategori)
      .map(function (k) { return { name: k, total: byKategori[k] }; })
      .sort(function (a, b) { return b.total - a.total; })
      .slice(0, 10);

    var topDaerah = Object.keys(byDaerah)
      .map(function (k) { return { name: k, total: byDaerah[k] }; })
      .sort(function (a, b) { return b.total - a.total; })
      .slice(0, 15);

    var recent = filtered.slice(-20).reverse().map(function (r) {
      return {
        id: toInt_(r.id),
        kategori: String(r.kategori || ''),
        nama: String(r.nama || ''),
        daerah: String(r.daerah || ''),
        status: String(r.status || ''),
        latitude: r.latitude,
        longitude: r.longitude
      };
    });

    var payload = {
      total: filtered.length,
      byStatus: byStatus,
      topKategori: topKategori,
      topDaerah: topDaerah,
      recent: recent,
      filters: {
        kategoriList: Object.keys(byKategori).sort(),
        daerahList: Object.keys(byDaerah).sort(),
        statusList: Object.keys(byStatus).sort()
      }
    };
    try {
      CacheService.getScriptCache().put(cacheKey, JSON.stringify(payload), 300);
    } catch (cacheErr) {}
    return ok_(payload);
  } catch (e) {
    return fail_(e.message);
  }
}

function apiSearchPencapaian(sessionToken, query, limit) {
  try {
    requireAuth_(sessionToken);
    query = String(query || '').toLowerCase().trim();
    limit = toInt_(limit, 50) || 50;
    if (!query) return ok_({ results: [] });

    var rows = sheetToObjects_(SHEETS.PENCAPAIAN);
    var results = [];
    for (var i = 0; i < rows.length && results.length < limit; i++) {
      var r = rows[i];
      var hay = [
        r.kategori, r.nama, r.daerah, r.parlimen, r.dun, r.status
      ].join(' ').toLowerCase();
      if (hay.indexOf(query) >= 0) {
        results.push({
          id: toInt_(r.id),
          kategori: String(r.kategori || ''),
          nama: String(r.nama || ''),
          daerah: String(r.daerah || ''),
          status: String(r.status || ''),
          latitude: r.latitude,
          longitude: r.longitude,
          catatan: String(r.catatan || '')
        });
      }
    }
    return ok_({ results: results });
  } catch (e) {
    return fail_(e.message);
  }
}
