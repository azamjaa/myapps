import fs from 'fs';
import path from 'path';
import { google } from 'googleapis';

const SCRIPT_ID = '1YCsO6LzCbsEqvQ-olB-V8IWAvGWHop229mSH8RAzmLJ7JsDrqyoGQr78';
const CLASP_RC = path.join(process.env.USERPROFILE || '', '.clasprc.json');

const rc = JSON.parse(fs.readFileSync(CLASP_RC, 'utf8'));
const tok = rc.tokens?.default || rc.token;
const oauth2 = new google.auth.OAuth2(tok.client_id, tok.client_secret);
oauth2.setCredentials({
  access_token: tok.access_token,
  refresh_token: tok.refresh_token,
  expiry_date: tok.expiry_date
});

const script = google.script({ version: 'v1', auth: oauth2 });

const { data } = await script.scripts.run({
  scriptId: SCRIPT_ID,
  requestBody: { function: 'MULA_SETUP', devMode: true }
});

if (data.error) {
  console.error('ERROR:', JSON.stringify(data.error, null, 2));
  process.exit(1);
}
console.log('SUCCESS:', JSON.stringify(data.response?.result, null, 2));
