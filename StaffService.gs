/**
 * Maklumat Staf (MyStaf)
 */

function enrichStaff_(u) {
  return publicUser_(u);
}

function apiGetStaffList(sessionToken, filters) {
  try {
    requireAuth_(sessionToken);
    filters = filters || {};
    var search = String(filters.search || '').toLowerCase();
    var bahagian = filters.bahagian ? toInt_(filters.bahagian) : 0;

    var list = sheetToObjects_(SHEETS.USERS)
      .filter(function (u) {
        return toInt_(u.aktif, 1) === 1 && toInt_(u.id_status_staf, 1) === 1;
      })
      .map(enrichStaff_)
      .filter(function (u) {
        if (bahagian && u.id_bahagian !== bahagian) return false;
        if (!search) return true;
        return (
          u.nama.toLowerCase().indexOf(search) >= 0 ||
          u.no_staf.toLowerCase().indexOf(search) >= 0 ||
          u.emel.toLowerCase().indexOf(search) >= 0 ||
          u.bahagian.toLowerCase().indexOf(search) >= 0 ||
          u.jawatan.toLowerCase().indexOf(search) >= 0
        );
      });

    list.sort(function (a, b) { return a.nama.localeCompare(b.nama); });

    var byBahagian = {};
    list.forEach(function (u) {
      var key = u.bahagian || 'Lain-lain';
      byBahagian[key] = (byBahagian[key] || 0) + 1;
    });

    return ok_({
      total: list.length,
      staff: list,
      byBahagian: byBahagian
    });
  } catch (e) {
    return fail_(e.message);
  }
}

function apiGetBirthdays(sessionToken) {
  try {
    requireAuth_(sessionToken);
    var now = new Date();
    var cm = now.getMonth() + 1;
    var list = sheetToObjects_(SHEETS.USERS)
      .filter(function (u) {
        return toInt_(u.aktif, 1) === 1 && toInt_(u.id_status_staf, 1) === 1;
      })
      .map(function (u) {
        var kp = normalizeNoKp_(u.no_kp);
        var mm = kp.length >= 4 ? parseInt(kp.substr(2, 2), 10) : 0;
        var dd = kp.length >= 6 ? parseInt(kp.substr(4, 2), 10) : 0;
        return {
          nama: String(u.nama || ''),
          bahagian: lookupName_('bahagian', u.id_bahagian),
          hari: dd,
          bulan: mm,
          gambar_url: String(u.gambar_url || '')
        };
      })
      .filter(function (u) { return u.bulan === cm; })
      .sort(function (a, b) { return a.hari - b.hari; });

    return ok_({ month: cm, birthdays: list });
  } catch (e) {
    return fail_(e.message);
  }
}

function apiGetStaffProfile(sessionToken, userId) {
  try {
    requireAuth_(sessionToken);
    userId = toInt_(userId);
    var user = findById_(SHEETS.USERS, 'id_user', userId);
    if (!user) return fail_('Staf tidak dijumpai.');
    return ok_({ staff: publicUser_(user) });
  } catch (e) {
    return fail_(e.message);
  }
}
