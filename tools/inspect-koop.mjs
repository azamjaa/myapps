import fs from 'fs';
import path from 'path';
import { google } from 'googleapis';

const SPREADSHEET_ID = '1tTnETdRHExtfWeMSycUl3vyQAnZUxj1s_ese98n2FRs';
const CLASP_RC = path.join(process.env.USERPROFILE || '', '.clasprc.json');

const rc = JSON.parse(fs.readFileSync(CLASP_RC, 'utf8'));
const tok = rc.tokens?.default || rc.token;
const oauth2 = new google.auth.OAuth2(tok.client_id, tok.client_secret);
oauth2.setCredentials({ access_token: tok.access_token, refresh_token: tok.refresh_token, expiry_date: tok.expiry_date });

const sheets = google.sheets({ version: 'v4', auth: oauth2 });
const drive = google.drive({ version: 'v3', auth: oauth2 });
const script = google.script({ version: 'v1', auth: oauth2 });

const meta = await sheets.spreadsheets.get({ spreadsheetId: SPREADSHEET_ID });
console.log('TITLE:', meta.data.properties.title);
console.log('SHEETS:', meta.data.sheets.map(s => ({ name: s.properties.title, rows: s.properties.gridProperties?.rowCount, cols: s.properties.gridProperties?.columnCount })));

// headers sample
for (const sh of meta.data.sheets.slice(0, 15)) {
  const name = sh.properties.title;
  const { data } = await sheets.spreadsheets.values.get({ spreadsheetId: SPREADSHEET_ID, range: `'${name}'!1:1` });
  console.log('\n===', name, '===');
  console.log((data.values && data.values[0]) || []);
}

// bound script?
const { data: file } = await drive.files.get({ fileId: SPREADSHEET_ID, fields: 'id,name,parents,shortcutDetails' });
console.log('\nFILE:', JSON.stringify(file, null, 2));

try {
  const { data: proj } = await script.projects.get({ scriptId: SPREADSHEET_ID });
  console.log('\nBOUND SCRIPT PROJECT:', JSON.stringify(proj, null, 2));
} catch (e) {
  console.log('\nNo bound script via spreadsheet id:', e.message);
}

// search container-bound script
const { data: scripts } = await drive.files.list({
  q: `mimeType='application/vnd.google-apps.script' and '${SPREADSHEET_ID}' in parents`,
  fields: 'files(id,name)',
  supportsAllDrives: true
});
console.log('\nChild scripts:', scripts.files);
