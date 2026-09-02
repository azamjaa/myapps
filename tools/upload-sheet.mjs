import fs from 'fs';
import path from 'path';
import { google } from 'googleapis';

const CLASP_RC = path.join(process.env.USERPROFILE || '', '.clasprc.json');
const XLSX = path.resolve('d:/myapps/myapps.xlsx');

const rc = JSON.parse(fs.readFileSync(CLASP_RC, 'utf8'));
const tok = rc.tokens?.default || rc.token;
const oauth2 = new google.auth.OAuth2(tok.client_id, tok.client_secret);
oauth2.setCredentials({
  access_token: tok.access_token,
  refresh_token: tok.refresh_token,
  expiry_date: tok.expiry_date
});

const drive = google.drive({ version: 'v3', auth: oauth2 });

const { data: file } = await drive.files.create({
  requestBody: {
    name: 'MyApps Database',
    mimeType: 'application/vnd.google-apps.spreadsheet'
  },
  media: {
    mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    body: fs.createReadStream(XLSX)
  },
  fields: 'id,name,webViewLink',
  supportsAllDrives: true
});

console.log(JSON.stringify({
  spreadsheetId: file.id,
  name: file.name,
  url: file.webViewLink
}, null, 2));
