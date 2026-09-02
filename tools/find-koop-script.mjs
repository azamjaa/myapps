import fs from 'fs';
import path from 'path';
import { google } from 'googleapis';

const SPREADSHEET_ID = '1tTnETdRHExtfWeMSycUl3vyQAnZUxj1s_ese98n2FRs';
const CLASP_RC = path.join(process.env.USERPROFILE || '', '.clasprc.json');
const rc = JSON.parse(fs.readFileSync(CLASP_RC, 'utf8'));
const tok = rc.tokens?.default || rc.token;
const oauth2 = new google.auth.OAuth2(tok.client_id, tok.client_secret);
oauth2.setCredentials({ access_token: tok.access_token, refresh_token: tok.refresh_token, expiry_date: tok.expiry_date });
const drive = google.drive({ version: 'v3', auth: oauth2 });
const script = google.script({ version: 'v1', auth: oauth2 });

const { data: meta } = await drive.files.get({
  fileId: SPREADSHEET_ID,
  fields: 'id,name,mimeType,owners,permissions,capabilities,parents,webViewLink'
});
console.log('META:', JSON.stringify(meta, null, 2));

// search scripts named koop
const { data: search } = await drive.files.list({
  q: "(name contains 'Koop' or name contains 'koop') and mimeType='application/vnd.google-apps.script' and trashed=false",
  fields: 'files(id,name,parents)',
  supportsAllDrives: true,
  includeItemsFromAllDrives: true
});
console.log('\nSCRIPTS:', JSON.stringify(search.files, null, 2));

// try spreadsheet id as script id
for (const id of [SPREADSHEET_ID, ...(search.files||[]).map(f=>f.id)]) {
  try {
    const { data: p } = await script.projects.get({ scriptId: id });
    console.log('\nPROJECT', id, JSON.stringify(p, null, 2));
    const { data: content } = await script.projects.getContent({ scriptId: id });
    console.log('FILES:', content.files?.map(f => ({ name: f.name, type: f.type, len: (f.source||'').length })));
  } catch (e) {
    console.log('\nPROJECT', id, 'ERR:', e.message);
  }
}
