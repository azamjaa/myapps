import fs from 'fs';
import path from 'path';
import { google } from 'googleapis';

const CLASP_RC = path.join(process.env.USERPROFILE || '', '.clasprc.json');
const SPREADSHEET_ID = '1ZwQ3lDESC0sR4YLziHy2RuEvxxBf9zyzt3b_G5Xb6D0';

const rc = JSON.parse(fs.readFileSync(CLASP_RC, 'utf8'));
const tok = rc.tokens?.default || rc.token;
const oauth2 = new google.auth.OAuth2(tok.client_id, tok.client_secret);
oauth2.setCredentials({
  access_token: tok.access_token,
  refresh_token: tok.refresh_token,
  expiry_date: tok.expiry_date
});

const drive = google.drive({ version: 'v3', auth: oauth2 });

await drive.permissions.create({
  fileId: SPREADSHEET_ID,
  requestBody: {
    type: 'domain',
    role: 'reader',
    domain: 'keda.gov.my'
  },
  supportsAllDrives: true
});

console.log('Shared MyApps Database with keda.gov.my (reader)');
