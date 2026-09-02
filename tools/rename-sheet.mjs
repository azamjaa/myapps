import fs from 'fs';
import path from 'path';
import { google } from 'googleapis';

const CLASP_RC = path.join(process.env.USERPROFILE || '', '.clasprc.json');
const DATABASE_ID = '1ZwQ3lDESC0sR4YLziHy2RuEvxxBf9zyzt3b_G5Xb6D0';
const FOLDER_QUERY = "name = 'Sistem Aplikasi' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";

const rc = JSON.parse(fs.readFileSync(CLASP_RC, 'utf8'));
const tok = rc.tokens?.default || rc.token;
const oauth2 = new google.auth.OAuth2(tok.client_id, tok.client_secret);
oauth2.setCredentials({
  access_token: tok.access_token,
  refresh_token: tok.refresh_token,
  expiry_date: tok.expiry_date
});

const drive = google.drive({ version: 'v3', auth: oauth2 });

async function findInFolder(folderId, name) {
  const q = `'${folderId}' in parents and name = '${name.replace(/'/g, "\\'")}' and trashed = false`;
  const { data } = await drive.files.list({
    q,
    fields: 'files(id,name,mimeType)',
    supportsAllDrives: true,
    includeItemsFromAllDrives: true
  });
  return data.files || [];
}

// Cari folder myapps di bawah Sistem Aplikasi atau My Drive
let myappsFolderId = null;
const { data: folders } = await drive.files.list({
  q: "name = 'myapps' and mimeType = 'application/vnd.google-apps.folder' and trashed = false",
  fields: 'files(id,name,parents)',
  supportsAllDrives: true,
  includeItemsFromAllDrives: true
});

if (folders.files?.length) {
  myappsFolderId = folders.files[0].id;
}

const oldMyapps = myappsFolderId ? await findInFolder(myappsFolderId, 'myapps') : [];
const oldSheets = oldMyapps.filter(f => f.mimeType === 'application/vnd.google-apps.spreadsheet' && f.id !== DATABASE_ID);

for (const f of oldSheets) {
  const backupName = 'myapps-lama-' + new Date().toISOString().slice(0, 10);
  await drive.files.update({
    fileId: f.id,
    requestBody: { name: backupName },
    supportsAllDrives: true
  });
  console.log('Renamed old sheet:', f.id, '->', backupName);
}

const { data: renamed } = await drive.files.update({
  fileId: DATABASE_ID,
  requestBody: { name: 'myapps' },
  fields: 'id,name,webViewLink',
  supportsAllDrives: true
});

console.log(JSON.stringify({
  ok: true,
  id: renamed.id,
  name: renamed.name,
  url: renamed.webViewLink
}, null, 2));
