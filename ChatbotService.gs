/**
 * Chatbot FAQ
 */

function apiChatbot(sessionToken, message) {
  try {
    requireAuth_(sessionToken);
    message = String(message || '').toLowerCase().trim();
    if (!message) return ok_({ reply: 'Sila taip soalan anda.' });

    var faqs = sheetToObjects_(SHEETS.FAQ);
    var best = null;
    var bestScore = 0;

    faqs.forEach(function (f) {
      var kw = String(f.keyword || '').toLowerCase();
      if (!kw) return;
      if (message.indexOf(kw) >= 0 || kw.indexOf(message) >= 0) {
        var score = kw.length;
        if (score > bestScore) {
          bestScore = score;
          best = f;
        }
      }
    });

    if (best) {
      return ok_({ reply: String(best.jawapan || ''), matched: String(best.keyword || '') });
    }

    var fallback = faqs.filter(function (f) {
      return String(f.keyword || '').toLowerCase() === 'bantu';
    })[0];

    return ok_({
      reply: fallback
        ? String(fallback.jawapan || '')
        : 'Maaf, saya tidak jumpa jawapan. Sila hubungi pentadbir sistem.',
      matched: ''
    });
  } catch (e) {
    return fail_(e.message);
  }
}

function apiGetFaqList(sessionToken) {
  try {
    var s = requireAuth_(sessionToken);
    if (!isAdminRole_(s.role)) return fail_('Tidak dibenarkan.');
    var faqs = sheetToObjects_(SHEETS.FAQ).map(function (f) {
      return { id: toInt_(f.id), keyword: String(f.keyword || ''), jawapan: String(f.jawapan || '') };
    });
    return ok_({ faqs: faqs });
  } catch (e) {
    return fail_(e.message);
  }
}
