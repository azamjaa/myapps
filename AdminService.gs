/**
 * Pentadbiran pengguna
 */

function apiGetUsers(sessionToken, filters) {
  try {
    var s = requireAuth_(sessionToken);
    if (!isAdminRole_(s.role)) return fail_('Anda tidak dibenarkan.');

    filters = filters || {};
    var search = String(filters.search || '').toLowerCase();

    var users = sheetToObjects_(SHEETS.USERS)
      .map(publicUser_)
      .filter(function (u) {
        if (!search) return true;
        return (
          u.nama.toLowerCase().indexOf(search) >= 0 ||
          u.no_kp.indexOf(search) >= 0 ||
          u.emel.toLowerCase().indexOf(search) >= 0
        );
      })
      .sort(function (a, b) { return a.nama.localeCompare(b.nama); });

    return ok_({ users: users, total: users.length });
  } catch (e) {
    return fail_(e.message);
  }
}

function apiSaveUser(sessionToken, payload) {
  try {
    var s = requireAuth_(sessionToken);
    if (!isAdminRole_(s.role)) return fail_('Anda tidak dibenarkan.');

    payload = payload || {};
    var id = payload.id_user ? toInt_(payload.id_user) : 0;
    var noKp = normalizeNoKp_(payload.no_kp);
    var nama = upper_(payload.nama);
    var emel = lower_(payload.emel);
    var role = String(payload.role || ROLES.USER);
    var aktif = payload.aktif ? 1 : 0;

    if (!noKp || !nama) return fail_('No. KP dan nama diperlukan.');
    if (!emel) return fail_('Emel Google diperlukan untuk log masuk SSO.');

    if (id) {
      if (!findById_(SHEETS.USERS, 'id_user', id)) return fail_('Pengguna tidak dijumpai.');
      updateRowById_(SHEETS.USERS, 'id_user', id, {
        no_kp: noKp,
        nama: nama,
        emel: emel,
        telefon: String(payload.telefon || ''),
        id_jawatan: toInt_(payload.id_jawatan),
        id_gred: toInt_(payload.id_gred),
        id_bahagian: toInt_(payload.id_bahagian),
        id_status_staf: toInt_(payload.id_status_staf, 1),
        role: role,
        aktif: aktif
      });
      logAudit_('UPDATE', 'Users', id, nama, s.user_id);
      return ok_({ message: 'Pengguna dikemas kini.', id_user: id });
    }

    var users = sheetToObjects_(SHEETS.USERS);
    for (var i = 0; i < users.length; i++) {
      if (normalizeNoKp_(users[i].no_kp) === noKp) {
        return fail_('No. KP sudah wujud.');
      }
      if (lower_(users[i].emel) === emel) {
        return fail_('Emel sudah wujud.');
      }
    }

    id = nextId_(SHEETS.USERS, 'id_user');
    appendObject_(SHEETS.USERS, {
      id_user: id,
      no_staf: String(payload.no_staf || ''),
      no_kp: noKp,
      nama: nama,
      emel: emel,
      telefon: String(payload.telefon || ''),
      id_jawatan: toInt_(payload.id_jawatan),
      id_gred: toInt_(payload.id_gred),
      id_bahagian: toInt_(payload.id_bahagian),
      gambar_url: '',
      id_status_staf: toInt_(payload.id_status_staf, 1),
      password_salt: '',
      password_hash: '',
      tarikh_tukar_katalaluan: '',
      reset_token: '',
      reset_token_expiry: '',
      last_login: '',
      role: role,
      aktif: aktif
    });
    logAudit_('CREATE', 'Users', id, nama, s.user_id);
    return ok_({ message: 'Pengguna ditambah. Log masuk melalui Google SSO.', id_user: id });
  } catch (e) {
    return fail_(e.message);
  }
}
