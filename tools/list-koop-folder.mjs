import fs from 'fs';
import path from 'path';
import { google } from 'googleapis';
const rc = JSON.parse(fs.readFileSync(path.join(process.env.USERPROFILE, '.clasprc.json'), 'utf8'));
const tok = rc.tokens?.default || rc.token;
const oauth2 = new google.auth.OAuth2(tok.client_id, tok.client_secret);
oauth2.setCredentials({ access_token: tok.access_token, refresh_token: tok.refresh_token, expiry_date: tok.expiry_date });
const drive = google.drive({ version: 'v3', auth: oauth2 });
const folder = '19qPy81lqrevRnr143u6O9jiYVbhz82IV';
const { data } = await drive.files.list({
  q: `'${folder}' in parents and trashed=false`,
  fields: 'files(id,name,mimeType,shortcutDetails)',
  supportsAllDrives: true,
  includeItemsFromAllDrives: true
});
for (const f of data.files || []) {
  console.log(f.mimeType.split('.').pop(), '|', f.name, '|', f.id);
}
