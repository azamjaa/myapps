# No-Code Builder System - Dokumentasi Lengkap

## Ringkasan Sistem

Sistem No-Code Builder MyApps membolehkan pengguna membina aplikasi web tanpa perlu menulis kod, melalui 5 fasa pembangunan yang sistematik.

---

## 5 Fasa Pembangunan Aplikasi

### **Fasa 1: Asas & Identiti (The Identity)**

#### Komponen:
1. **Nama Aplikasi** - Nama aplikasi yang akan dipaparkan
2. **Kategori** - Dalaman / Luaran / Gunasama
3. **URL Slug** - Auto-generated dari nama aplikasi (contoh: `sistem-aduan-awam`)

#### Mod Input Data:
- **Blank Form** - Borang kosong untuk bina manual
- **Upload Excel** - Import data dari fail .xlsx (header = field names)
- **Borang Fizikal** - Digitalkan borang fizikal dengan field manual

#### Teknologi:
- JavaScript auto-generate slug menggunakan `toLowerCase()`, `replace(/\s+/g, '-')`
- PhpSpreadsheet library untuk parse Excel
- Field extraction dari header Excel baris pertama

---

### **Fasa 2: Definisi Data (Hybrid Skeleton)**

#### Upload Excel Mode:
```php
// Kod dalam wizard.php (action: parse_excel)
- PhpSpreadsheet\IOFactory::load() untuk baca fail
- Header row (baris 1) -> field names
- Data rows (baris 2+) -> simpan ke $_SESSION['wizard_excel_rows']
- Infer field types (tarikh, emel, number) berdasarkan pattern header
```

#### Bina Manual Mode:
- Tambah field secara dinamik dengan atribut:
  - `field_name` (unique identifier)
  - `field_type` (text, date, number, select)
  - `label` (display label)
  
#### Struktur JSON:
```json
{
  "fields": [
    {
      "name": "nama_pemohon",
      "label": "Nama Pemohon",
      "type": "text",
      "required": false
    }
  ]
}
```

---

### **Fasa 3: Rupa Paras & Navigasi (Visual Layout)**

#### Page Manager:
Setiap halaman mempunyai konfigurasi:
- **Nama Halaman** (contoh: "Senarai", "Borang", "Dashboard")
- **View Layout:**
  - **Table View** - DataTables standard dengan sort/search/pagination
  - **Card View** - Grid kad moden (Bootstrap cards)
  - **Calendar View** - FullCalendar integration untuk event-based data

#### Dashboard Builder:
- Pick & Drop statistik widgets
- Aggregation functions:
  - **Count** - Kira bilangan rekod
  - **Sum** - Jumlah nilai numerik
  - **Average** - Purata nilai
  
#### Contoh Metadata:
```json
{
  "pages": [
    {
      "id": "senarai",
      "type": "list",
      "label": "Senarai",
      "icon": "fas fa-list",
      "config": {
        "layout_type": "card_view"
      }
    }
  ],
  "dashboard_cards": [
    {
      "title": "Jumlah Aduan",
      "field": "status",
      "aggregation": "count"
    }
  ]
}
```

---

### **Fasa 4: Logik & Automasi (Workflow Engine)**

#### If-This-Then-That Interface:

**Struktur Workflow:**
1. **Trigger** - Bila workflow dijalankan
   - `created` - Apabila rekod ditambah
   - `updated` - Apabila rekod dikemaskini

2. **Condition** - Syarat yang perlu dipenuhi
   - Field name (cth: `status`)
   - Operator (`==`, `!=`, `>`, `<`, `>=`, `<=`, `contains`)
   - Value (cth: `Rosak`)

3. **Action** - Tindakan yang dijalankan
   - Hantar emel notifikasi (PHPMailer atau mail())
   - Cipta notifikasi sistem (future enhancement)

#### Contoh Workflow JSON:
```json
{
  "workflows": [
    {
      "trigger": "created",
      "condition_field": "status",
      "condition_operator": "==",
      "condition_value": "Rosak",
      "action_email": "admin@example.com"
    }
  ]
}
```

#### Implementasi:
- File: `workflow_processor.php`
- Dipanggil dalam `engine.php` selepas INSERT/UPDATE
- Logging ke table `workflow_logs` (auto-created)

---

### **Fasa 5: Deployment & Virtual Routing**

#### Deployment Process:

**1. Simpan Metadata**
```php
// wizard.php (action: publish)
INSERT INTO custom_apps (app_slug, app_name, metadata, id_user_owner, id_kategori)
VALUES (?, ?, ?, ?, ?)
```

**2. Import Data Excel** (jika ada)
```php
// Loop through $_SESSION['wizard_excel_rows']
INSERT INTO custom_app_data (id_custom, created_by, payload, created_at)
VALUES (?, ?, ?, NOW())
```

**3. Set Status 'Live'**
- Aplikasi automatik muncul di dashboard selepas deploy
- Accessible via virtual URL

#### Virtual Subfolder Logic (.htaccess):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /myapps/
    
    # Virtual routing: /myapps/apps/slug -> engine.php?app_slug=slug
    RewriteRule ^apps/([a-zA-Z0-9_-]+)/?$ engine.php?app_slug=$1 [L,QSA]
</IfModule>
```

**Contoh URL:**
- Input: `http://localhost/myapps/apps/sistem-aduan`
- Actual: `engine.php?app_slug=sistem-aduan`
- URL bar: TIDAK BERUBAH (tetap `/apps/sistem-aduan`)

---

## Struktur Database

### Table: `custom_apps`
```sql
CREATE TABLE custom_apps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    app_slug VARCHAR(255) UNIQUE NOT NULL,
    app_name VARCHAR(255) NOT NULL,
    metadata TEXT,  -- JSON dengan fields, pages, workflows, dashboard_cards
    id_user_owner INT,
    id_kategori INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Table: `custom_app_data`
```sql
CREATE TABLE custom_app_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_custom INT NOT NULL,
    payload TEXT,  -- JSON data rekod
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_custom) REFERENCES custom_apps(id)
);
```

### Table: `workflow_logs` (auto-created)
```sql
CREATE TABLE workflow_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_custom INT NOT NULL,
    record_id INT NOT NULL,
    workflow_index INT,
    trigger_type VARCHAR(50),
    condition_met BOOLEAN,
    action_success BOOLEAN,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## Keperluan Teknikal

### Dependencies:
1. **PHP 7.4+** dengan PDO MySQL
2. **PhpSpreadsheet** - `composer require phpoffice/phpspreadsheet`
3. **PHPMailer** (optional) - `composer require phpmailer/phpmailer`

### Frontend Libraries:
1. **Bootstrap 5.3** - UI framework
2. **DataTables.js** - Table view dengan sort/search
3. **FullCalendar.js** - Calendar view
4. **Chart.js** - Dashboard widgets
5. **SweetAlert2** - Notifikasi moden

### Server Requirements:
- Apache 2.4+ dengan `mod_rewrite` enabled
- MySQL 5.7+ atau MariaDB 10.2+
- Session support enabled

---

## File Structure

```
myapps/
├── wizard.php              # 5-step wizard builder
├── engine.php              # Master renderer untuk aplikasi
├── workflow_processor.php  # Workflow automation engine
├── builder_save.php        # Legacy builder save endpoint
├── .htaccess              # Virtual routing configuration
├── db.php                 # Database connection
├── header.php             # Layout header
└── footer.php             # Layout footer
```

---

## Cara Guna

### 1. Akses Wizard
```
http://localhost/myapps/wizard.php
```

### 2. Ikut 5 Langkah:
- **Step 1:** Isi nama, kategori, pilih sumber data
- **Step 2:** Bina halaman (Table/Card/Calendar view)
- **Step 3:** Tambah dashboard widgets (optional)
- **Step 4:** Setup workflow automation (optional)
- **Step 5:** Deploy aplikasi

### 3. Akses Aplikasi
```
http://localhost/myapps/apps/[slug-anda]
```

---

## Workflow Execution Flow

```
1. User submit form dalam aplikasi
   ↓
2. engine.php: INSERT/UPDATE ke custom_app_data
   ↓
3. engine.php: require_once workflow_processor.php
   ↓
4. process_workflows() dipanggil dengan:
   - $id_custom (app ID)
   - $record_id (rekod yang baru ditambah/dikemaskini)
   - $trigger ('created' atau 'updated')
   - $payload (data rekod)
   ↓
5. Loop through workflows dalam metadata
   ↓
6. Check trigger type
   ↓
7. Evaluate condition (field value vs expected value)
   ↓
8. If condition met → send_workflow_email()
   ↓
9. Log execution ke workflow_logs table
   ↓
10. Return results array
```

---

## Contoh Penggunaan End-to-End

### Scenario: Sistem Aduan Awam

**Fasa 1:**
- Nama: "Sistem Aduan Awam"
- Kategori: Luaran
- Slug: `sistem-aduan-awam` (auto-generated)
- Sumber: Borang Fizikal

**Fasa 2:**
- Field manual:
  - `nama_pengadu` (Text)
  - `no_telefon` (Text)
  - `jenis_aduan` (Select)
  - `tarikh_kejadian` (Date)
  - `keterangan` (Text)
  - `status` (Select: Baru/Dalam Tindakan/Selesai)

**Fasa 3:**
- Page 1: "Senarai Aduan" (Card View)
- Page 2: "Borang Aduan" (Form)
- Page 3: "Dashboard" (Report View)
- Dashboard: Kad "Jumlah Aduan" (Count, field: status)

**Fasa 4:**
- Workflow 1:
  - Trigger: created
  - Condition: status == "Baru"
  - Action: Email ke admin@keda.gov.my

**Fasa 5:**
- Klik "Deploy Application"
- Aplikasi live di: `http://localhost/myapps/apps/sistem-aduan-awam`

---

## Troubleshooting

### Issue: .htaccess tidak berfungsi
**Solution:**
```bash
# Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# Pastikan AllowOverride All dalam Apache config
```

### Issue: Excel import gagal
**Solution:**
```bash
# Install PhpSpreadsheet
composer require phpoffice/phpspreadsheet

# Pastikan PHP extensions enabled:
- php_zip
- php_xml
- php_gd
```

### Issue: Email tidak dihantar
**Solution:**
1. Install PHPMailer: `composer require phpmailer/phpmailer`
2. Configure SMTP settings dalam `workflow_processor.php`
3. Atau guna `mail()` function (pastikan sendmail configured)

---

## Best Practices

1. **Field Naming**: Gunakan snake_case (cth: `nama_pemohon`)
2. **Workflow Testing**: Test dengan dummy email dahulu
3. **Performance**: Limit workflows kepada 5-10 per app
4. **Security**: Validate email addresses dalam workflow config
5. **Backup**: Export metadata sebagai JSON backup secara berkala

---

## Future Enhancements

- [ ] Webhook integration untuk workflow actions
- [ ] SMS notification support
- [ ] Conditional field visibility dalam forms
- [ ] Duplicate record detection
- [ ] Bulk import/export Excel
- [ ] App versioning dan rollback
- [ ] Multi-language support
- [ ] Role-based access control per app

---

## Support & Documentation

Untuk sokongan teknikal atau pertanyaan:
- Email: support@keda.gov.my
- Internal Wiki: http://wiki.keda.gov.my/myapps

**Last Updated:** 2026-02-09
**Version:** 1.0.0
