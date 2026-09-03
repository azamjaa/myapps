from pathlib import Path

path = Path(r'd:\myapps\JavaScript.html')
text = path.read_text(encoding='utf-8')
start = text.index('/* ── Birthdays ── */')
end = text.index('/* ── Admin Apps ── */')

new_fn = r'''/* ── Birthdays (calendar) ── */
async function loadBirthdays() {
  $('pageContent').innerHTML = '<div class="empty-state">Memuatkan kalendar...</div>';
  try {
    var res = await run('apiGetBirthdays', []);
    if (!res.success) throw new Error(res.message);
    var months = ['', 'Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'];
    var monthsShort = ['', 'Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogos', 'Sep', 'Okt', 'Nov', 'Dis'];
    var weekDays = ['Isn', 'Sel', 'Rab', 'Kha', 'Jum', 'Sab', 'Ahd'];
    var month = toIntSafe(res.data.month, new Date().getMonth() + 1);
    var year = new Date().getFullYear();
    var today = new Date();
    var todayDay = (today.getMonth() + 1 === month && today.getFullYear() === year) ? today.getDate() : 0;
    var birthdays = res.data.birthdays || [];

    var byDay = {};
    birthdays.forEach(function (b) {
      var d = toIntSafe(b.hari, 0);
      if (d < 1 || d > 31) return;
      if (!byDay[d]) byDay[d] = [];
      byDay[d].push(b);
    });

    var first = new Date(year, month - 1, 1);
    var daysInMonth = new Date(year, month, 0).getDate();
    var startPad = (first.getDay() + 6) % 7;

    var html = '';
    html += '<div class="bday-layout">';
    html += '<div class="bday-cal panel">';
    html += '<div class="bday-cal-head">';
    html += '<div class="bday-cal-title"><span class="bday-cal-label">Kalendar Hari Lahir</span>';
    html += '<h3>' + months[month] + ' ' + year + '</h3>';
    html += '<p>' + birthdays.length + ' staf menyambut hari lahir bulan ini</p></div>';
    html += '<div class="bday-cal-stat"><strong>' + birthdays.length + '</strong><span>Hari Lahir</span></div>';
    html += '</div>';

    html += '<div class="bday-weekhead">';
    weekDays.forEach(function (w) { html += '<div>' + w + '</div>'; });
    html += '</div>';

    html += '<div class="bday-grid">';
    var i;
    for (i = 0; i < startPad; i++) html += '<div class="bday-cell empty"></div>';
    for (var day = 1; day <= daysInMonth; day++) {
      var list = byDay[day] || [];
      var isToday = day === todayDay;
      var hasBday = list.length > 0;
      var cls = 'bday-cell' + (isToday ? ' is-today' : '') + (hasBday ? ' has-bday' : '');
      html += '<button type="button" class="' + cls + '" data-day="' + day + '" onclick="selectBdayDay(' + day + ')">';
      html += '<span class="bday-daynum">' + day + '</span>';
      if (hasBday) {
        html += '<span class="bday-count">' + list.length + '</span>';
        html += '<div class="bday-dots">';
        list.slice(0, 3).forEach(function () { html += '<i></i>'; });
        html += '</div>';
        html += '<div class="bday-preview">' + esc(String(list[0].nama || '').split(' ')[0]) + (list.length > 1 ? ' +' + (list.length - 1) : '') + '</div>';
      }
      html += '</button>';
    }
    html += '</div></div>';

    html += '<div class="bday-side">';
    html += '<div class="panel bday-side-panel" id="bdayDetail"></div>';
    html += '<div class="panel bday-upcoming">';
    html += '<h4>Senarai Bulan Ini</h4>';
    if (!birthdays.length) {
      html += '<div class="empty-state" style="padding:20px">Tiada hari lahir bulan ini.</div>';
    } else {
      html += '<div class="bday-list">';
      birthdays.forEach(function (b) {
        var nama = String(b.nama || '-');
        html += '<button type="button" class="bday-list-item" onclick="selectBdayDay(' + toIntSafe(b.hari, 1) + ')">';
        html += '<div class="bday-list-date"><strong>' + toIntSafe(b.hari, '-') + '</strong><span>' + monthsShort[month] + '</span></div>';
        html += '<div class="bday-list-info"><strong>' + esc(nama) + '</strong><span>' + esc(b.bahagian || '') + '</span></div>';
        html += '</button>';
      });
      html += '</div>';
    }
    html += '</div></div></div>';

    state.bdayByDay = byDay;
    state.bdayMonthShort = monthsShort[month];
    $('pageContent').innerHTML = html;
    var firstBday = Object.keys(byDay).map(Number).sort(function (a, b) { return a - b; })[0] || 1;
    selectBdayDay(todayDay || firstBday);
  } catch (e) {
    $('pageContent').innerHTML = '<div class="alert alert-danger">' + esc(e.message) + '</div>';
  }
}

function toIntSafe(v, def) {
  var n = parseInt(v, 10);
  return isNaN(n) ? def : n;
}

function renderBdayDetail_(byDay, day, monthShort) {
  var list = (byDay && byDay[day]) || [];
  var html = '<div class="bday-detail-head"><span>Tarikh dipilih</span><h3>' + day + ' ' + esc(monthShort || '') + '</h3></div>';
  if (!list.length) {
    html += '<div class="empty-state" style="padding:28px 12px">Tiada hari lahir pada hari ini.</div>';
    return html;
  }
  html += '<div class="bday-detail-list">';
  list.forEach(function (b) {
    var nama = String(b.nama || '-');
    html += '<div class="bday-detail-card">';
    html += '<div class="staff-avatar">' + esc(nama.charAt(0) || '?') + '</div>';
    html += '<div><strong>' + esc(nama) + '</strong><p>' + esc(b.bahagian || '') + '</p></div>';
    html += '</div>';
  });
  html += '</div>';
  return html;
}

function selectBdayDay(day) {
  day = toIntSafe(day, 1);
  document.querySelectorAll('.bday-cell').forEach(function (el) {
    el.classList.toggle('is-selected', toIntSafe(el.getAttribute('data-day'), 0) === day);
  });
  var box = $('bdayDetail');
  if (box) box.innerHTML = renderBdayDetail_(state.bdayByDay || {}, day, state.bdayMonthShort || '');
}

'''

path.write_text(text[:start] + new_fn + text[end:], encoding='utf-8')
print('updated birthday calendar js')
