import fs from 'fs';
import path from 'path';
import { google } from 'googleapis';

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
const iam = google.iam({ version: 'v1', auth: oauth2 });

const SCRIPT_ID = '1YCsO6LzCbsEqvQ-olB-V8IWAvGWHop229mSH8RAzmLJ7JsDrqyoGQr78';

const { data: proj } = await script.projects.get({ scriptId: SCRIPT_ID });
console.log('GCP Project:', proj.parentId || proj.scriptId);

const projectId = proj.parentId;
if (!projectId) {
  console.log('No GCP project linked');
  process.exit(1);
}

const oauth = google.oauth2('v2');
// list oauth clients via api credentials
const cloudresourcemanager = google.cloudresourcemanager({ version: 'v1', auth: oauth2 });
try {
  const { data: enabled } = await google.discoveryAPI('discovery', { version: 'v1', auth: oauth2 });
} catch (e) {}

// Use Service Usage / Credentials API
const { google: g } = await import('googleapis');
const credApi = g.google.auth.fromJSON ? null : null;

// Try IAM credentials list - actually use oauth2 credentials from cloud console via script projects
const { data: creds } = await fetch(
  `https://content.googleapis.com/appsmarket/v2/customerLicense/${projectId}`,
  { headers: { Authorization: `Bearer ${tok.access_token}` } }
).then(r => r.text()).catch(e => e.message);
console.log('creds attempt:', creds);

// Standard approach: list via oauth2 API isn't available. Use script project's default client from clasp credentials
console.log('\nClasp OAuth client_id (for reference):', tok.client_id);

// Apps Script uses auto-created Web client - often same project number
// Try Cloud Console API credentials endpoint
const resp = await fetch(
  `https://apikeys.googleapis.com/v2/projects/${projectId}/locations/global/keys`,
  { headers: { Authorization: `Bearer ${(await oauth2.getAccessToken()).token}` } }
);
const text = await resp.text();
console.log('apikeys:', resp.status, text.slice(0, 500));
