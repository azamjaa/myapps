# MyApps No-Code Builder - Quick Start Guide

## 🚀 Ringkasan
Sistem No-Code Builder yang lengkap untuk membina aplikasi web tanpa menulis kod, melalui 5 fasa pembangunan.

---

## 📋 5 Fasa Pembangunan

### **Fasa 1: Identiti & Data Source**
- ✅ Nama aplikasi & kategori (Dalaman/Luaran/Gunasama)
- ✅ Auto-generate URL slug dari nama
- ✅ 3 mod input: Blank Form / Excel Upload / Borang Fizikal

### **Fasa 2: Definisi Data**
- ✅ Upload Excel: PhpSpreadsheet parse header + data
- ✅ Bina Manual: Tambah field (Text, Date, Number, Select)
- ✅ Auto-infer field types dari header Excel

### **Fasa 3: Rupa Paras & Navigasi**
- ✅ Page Manager: Tambah unlimited pages
- ✅ View Layouts: Table View / Card View / Calendar View
- ✅ Dashboard Builder: Pick & Drop statistik (Count/Sum/Average)

### **Fasa 4: Workflow & Automation**
- ✅ If-This-Then-That interface
- ✅ Triggers: Apabila Ditambah / Dikemaskini
- ✅ Conditions: Field comparison (==, !=, >, <)
- ✅ Actions: Hantar emel notifikasi (PHPMailer)

### **Fasa 5: Deployment**
- ✅ Simpan metadata (JSON) ke database
- ✅ Import data Excel (jika ada)
- ✅ Virtual routing: `/myapps/apps/[slug]`
- ✅ Status: Live & accessible

---

## 🔧 Installation

### 1. Dependencies
```bash
# Install PHP dependencies
composer require phpoffice/phpspreadsheet
composer require phpmailer/phpmailer  # optional untuk email
```

### 2. Apache Configuration
```bash
# Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 3. Database Setup
```sql
-- Tables: custom_apps, custom_app_data, kategori
-- Auto-created: workflow_logs
```

---

## 🎯 Quick Start

### Cara Guna (3 Steps):

**1. Buka Wizard**
```
http://localhost/myapps/wizard.php
```

**2. Ikut 5 Langkah**
- Langkah 1: Nama + Kategori + Pilih sumber data
- Langkah 2: Bina halaman (Table/Card/Calendar)
- Langkah 3: Tambah dashboard widgets
- Langkah 4: Setup workflow automation
- Langkah 5: Deploy!

**3. Akses Aplikasi**
```
http://localhost/myapps/apps/nama-aplikasi-anda
```

---

## 📁 File Structure

```
myapps/
├── wizard.php                          # ⭐ 5-step wizard builder
├── engine.php                          # ⭐ Master renderer
├── workflow_processor.php              # ⭐ NEW: Workflow engine
├── .htaccess                           # Virtual routing
├── NOCODE_BUILDER_DOCUMENTATION.md    # Full documentation
└── README_NOCODE_BUILDER.md           # This file
```

---

## 🔄 Workflow Execution

```
User Submit Form
    ↓
engine.php: INSERT/UPDATE
    ↓
workflow_processor.php: Execute workflows
    ↓
Check trigger (created/updated)
    ↓
Evaluate condition (field value)
    ↓
Send email notification
    ↓
Log to workflow_logs
```

---

## 📊 Contoh Metadata JSON

```json
{
  "fields": [
    {"name": "nama", "label": "Nama", "type": "text"},
    {"name": "tarikh", "label": "Tarikh", "type": "date"}
  ],
  "pages": [
    {"id": "senarai", "type": "list", "label": "Senarai", "icon": "fas fa-list"}
  ],
  "dashboard_cards": [
    {"title": "Jumlah Rekod", "field": "status", "aggregation": "count"}
  ],
  "workflows": [
    {
      "trigger": "created",
      "condition_field": "status",
      "condition_operator": "==",
      "condition_value": "Baru",
      "action_email": "admin@example.com"
    }
  ]
}
```

---

## ⚙️ Konfigurasi

### Virtual Routing (.htaccess)
```apache
RewriteRule ^apps/([a-zA-Z0-9_-]+)/?$ engine.php?app_slug=$1 [L,QSA]
```

### Workflow Email Settings
Edit `workflow_processor.php` untuk SMTP configuration (jika guna PHPMailer).

---

## 🛠️ Troubleshooting

| Issue | Solution |
|-------|----------|
| .htaccess tidak berfungsi | Enable `mod_rewrite` dan set `AllowOverride All` |
| Excel import gagal | Install PhpSpreadsheet + enable php_zip extension |
| Email tidak dihantar | Install PHPMailer atau configure sendmail |
| Workflow tidak execute | Check workflow_logs table untuk debugging |

---

## 📌 Features Checklist

### Fasa 1 ✅
- [x] Input nama aplikasi
- [x] Pilih kategori (Dalaman/Luaran/Gunasama)
- [x] Auto-generate slug dari nama
- [x] Excel Upload (PhpSpreadsheet)
- [x] Borang Fizikal (manual fields)

### Fasa 2 ✅
- [x] Parse Excel header → fields
- [x] Import Excel data → session storage
- [x] Manual field builder (Text/Date/Number/Select)
- [x] Field validation

### Fasa 3 ✅
- [x] Multi-page support (unlimited)
- [x] Table View (DataTables)
- [x] Card View (Bootstrap cards)
- [x] Calendar View (FullCalendar)
- [x] Dashboard Builder (widgets)

### Fasa 4 ✅
- [x] If-This-Then-That interface
- [x] Trigger: created/updated
- [x] Condition: field comparisons
- [x] Action: Send email notification
- [x] Workflow logging

### Fasa 5 ✅
- [x] Save metadata to database
- [x] Import Excel data to custom_app_data
- [x] Virtual routing (/apps/slug)
- [x] Application status: Live

---

## 🎨 UI Components

- AdminLTE-style modals
- Bootstrap 5 cards & forms
- Wizard stepper dengan progress indicator
- Drag & drop field builder (future enhancement)
- Responsive mobile design

---

## 📖 Full Documentation

Rujuk **NOCODE_BUILDER_DOCUMENTATION.md** untuk:
- Detailed technical specs
- Database schema
- API reference
- Advanced configurations
- Security best practices

---

## 🔐 Security

- ✅ CSRF protection (db.php)
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS prevention (htmlspecialchars)
- ✅ Session validation
- ✅ Email validation dalam workflows

---

## 📞 Support

Untuk bantuan teknikal:
- **Email**: support@keda.gov.my
- **Wiki**: http://wiki.keda.gov.my/myapps
- **Documentation**: NOCODE_BUILDER_DOCUMENTATION.md

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-09  
**Status**: Production Ready ✅
