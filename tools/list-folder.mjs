import fs from 'fs';
import path from 'path';
import { google } from 'googleapis';

const CLASP_RC = path.join(process.env.USERPROFILE || '', '.clasprc.json');
const rc = JSON.parse(fs.readFileSync(CLASP_RC, 'utf8'));
const tok = rc.tokens?.default || rc.token;
const oauth2 = new google.auth.OAuth2(tok.client_id, tok.client_secret);
oauth2.setCredentials({ access_token: tok.access_token, refresh_token: tok.refresh_token, expiry_date: tok.expiry_date });
const drive = google.drive({ version: 'v3', auth: oauth2 });

const { data } = await drive.files.list({
  q: "name = 'myapps' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
  fields: 'files(id)',
  supportsAllDrives: true
});
if (!data.files?.length) { console.log('folder not found'); process.exit(0); }
const fid = data.files[0].id;
const { data: list } = await drive.files.list({
  q: `'${fid}' in parents and trashed = false`,
  fields: 'files(id,name,mimeType)',
  supportsAllDrives: true
});
console.log(JSON.stringify(list.files, null, 2));
