# 🎯 Flexible No-Code Builder - Panduan Lengkap

## 🌟 Overview

MyApps No-Code Builder kini support **3 pendekatan berbeza** untuk bina aplikasi:

1. **📊 Data-Driven** (AppSheet style) - Mula dengan data
2. **🎨 Visual-Driven** (Glide style) - Design UI dulu
3. **⚙️ Logic-Driven** (Bubble.io style) - Workflow & automation

---

## 🚀 Quick Start

### Akses Builder Hub

1. Login ke MyApps
2. Click **"No-Code Builder"** di sidebar (menu baru dengan gradient purple)
3. Pilih builder mode yang sesuai

**URL Direct:** `http://127.0.0.1/myapps/nocode_hub.php`

---

## 📊 Mode 1: DATA-DRIVEN Builder

### 🎯 Best For:
- Digitize borang sedia ada
- Import data dari Excel/CSV
- Setup cepat (< 5 minit)
- User yang ada data siap

### 📝 Cara Guna:

1. **Buka Wizard Builder**
   - Click "Data-Driven" card di Hub
   - Atau direct: `wizard.php`

2. **Step 1: App Info**
   - Isi Nama Aplikasi
   - Pilih Kategori
   - URL Slug auto-generate

3. **Step 2: Upload Excel**
   - Pilih "Upload Excel"
   - Drop Excel file (.xlsx, .xls, .csv)
   - System auto-detect fields dari header
   - Data auto-import

4. **Step 3: Pages & Views**
   - Tambah pages (List, Form, Calendar, Dashboard)
   - Configure view type

5. **Step 4: Workflows** (Optional)
   - Tambah automation rules
   - If-This-Then-That logic
   - Email notifications

6. **Step 5: Publish**
   - Click "Simpan Aplikasi"
   - App live di `/apps/[slug]`

### ✅ Kelebihan:
- ⚡ Paling cepat
- 🎯 Auto-detect field types
- 📥 Bulk data import
- 🔄 Excel → App dalam minit

### ⚠️ Limitations:
- Limited UI customization
- Layout auto-generated
- Styling preset

---

## 🎨 Mode 2: VISUAL-DRIVEN Builder

### 🎯 Best For:
- Custom UI/UX design
- Branding-specific apps
- User yang mahir design
- Complex layouts

### 📝 Cara Guna:

1. **Buka Visual Builder**
   - Click "Visual-Driven" card di Hub
   - Atau direct: `builder.php`

2. **Step 1: Maklumat Asas**
   - Nama aplikasi, slug, kategori

3. **Step 2: Build Form Fields**
   - Drag & drop field components
   - Text, Number, Date, Select, etc
   - Configure properties

4. **Step 3: Customize Styling**
   - Theme colors
   - Layout options
   - Custom CSS (advanced)

5. **Step 4: Configure Pages**
   - Multiple pages
   - Different view types
   - Page icons & labels

6. **Step 5: Enable Features**
   - Search, Export, Dashboard
   - CRUD operations

7. **Publish**
   - Save & deploy

### ✅ Kelebihan:
- 🎨 Full UI control
- 🖱️ Drag & drop interface
- 🎭 Custom branding
- 📱 Responsive design

### ⚠️ Limitations:
- Takes longer to build
- Requires design skills
- Manual field creation

---

## ⚙️ Mode 3: LOGIC-DRIVEN Builder

### 🎯 Best For:
- Complex business logic
- Multi-step workflows
- Approval processes
- API integrations

### 📝 Cara Guna:

**⚠️ Status: Coming Soon!**

Visual workflow builder sedang dalam development. 

**Workaround Sekarang:**
- Guna Wizard Builder (Step 4: Workflows)
- Configure basic If-This-Then-That rules
- Email notifications available

**Planned Features:**
- Visual workflow canvas
- Drag & drop logic nodes
- Complex conditions (AND/OR)
- Multiple action types
- Scheduled triggers (cron)
- API webhooks
- Custom calculations

### ✅ Kelebihan (Future):
- ⚙️ Complex automation
- 🔗 System integrations
- 📊 Advanced logic
- 🔄 Multi-step processes

---

## 🔄 Mode Switching

**Boleh tukar mode tengah-tengah?**

**Current:** ❌ Not yet supported

**Future:** ✅ Planned feature
- Start data-driven → Switch to visual for custom UI
- Import Excel → Enhance with workflows
- Seamless mode transitions

---

## 📊 Comparison Table

| Feature | Data-Driven | Visual-Driven | Logic-Driven |
|---------|-------------|---------------|--------------|
| **Setup Time** | ⚡ 5 minit | ⏱️ 15-30 minit | ⏳ 30+ minit |
| **Difficulty** | ✅ Mudah | ⚠️ Sederhana | 🔴 Advanced |
| **Excel Import** | ✅ Yes | ❌ No | ❌ No |
| **UI Customization** | ⚠️ Limited | ✅ Full | ⚠️ Basic |
| **Workflows** | ✅ Basic | ✅ Basic | ✅ Advanced |
| **Best For** | Quick digitization | Custom design | Complex logic |
| **File** | `wizard.php` | `builder.php` | `workflow_builder.php` |

---

## 🎯 Which Mode to Choose?

### Scenario 1: "Saya ada Excel borang staf, nak digitize cepat"
**→ Data-Driven Builder** ✅
- Upload Excel
- Auto-generate form
- 5 minit siap

### Scenario 2: "Nak buat CRM dengan UI custom, matching brand color"
**→ Visual-Driven Builder** ✅
- Design dari kosong
- Full styling control
- Custom layout

### Scenario 3: "Nak buat approval system dengan multi-level workflow"
**→ Logic-Driven Builder** ⚠️ (Coming Soon)
- Sementara guna Wizard + manual workflow config
- Future: Visual workflow designer

### Scenario 4: "Tak pasti, nak try dulu"
**→ Data-Driven Builder** ✅
- Paling mudah untuk start
- Boleh enhance kemudian

---

## 🛠️ Technical Details

### Unified Metadata Format

Semua 3 builders output ke format JSON yang sama:

```json
{
  "app_info": {...},
  "data_schema": {
    "fields": [...],
    "relationships": [...]
  },
  "ui_layout": {
    "pages": [...],
    "theme": {...}
  },
  "workflows": [...],
  "settings": {...}
}
```

Stored in: `custom_apps.metadata` (JSON column)

### Rendering Engine

`engine.php` - Universal renderer untuk semua apps:
- Read metadata
- Generate UI components
- Handle CRUD operations
- Execute workflows

### Workflow Processor

`workflow_processor.php` - Execute automation:
- Trigger detection (created, updated)
- Condition evaluation
- Action execution (email, notification)
- Logging

---

## 📁 File Structure

```
myapps/
├── nocode_hub.php              # 🏠 Main entry - choose mode
├── wizard.php                  # 📊 Data-driven builder
├── builder.php                 # 🎨 Visual builder
├── workflow_builder.php        # ⚙️ Logic builder (preview)
├── builder_save.php            # 💾 Save handler
├── engine.php                  # 🎬 Universal renderer
├── workflow_processor.php      # ⚙️ Workflow engine
└── ARCHITECTURE_3IN1_BUILDER.md # 📖 Technical docs
```

---

## 🐛 Troubleshooting

### Issue: "Saya upload Excel tapi fields tak masuk"

**Solution:**
- ❌ Jangan guna "Import Excel" button dalam `builder.php`
- ✅ Guna `wizard.php` (Data-Driven mode) untuk Excel import
- Button dalam `builder.php` adalah untuk instant app creation (separate flow)

### Issue: "Button Simpan Aplikasi tak respond"

**Check:**
1. Buka Browser Console (F12)
2. Check for JavaScript errors
3. Pastikan semua required fields diisi:
   - Nama Aplikasi
   - URL Slug
   - Kategori

**Debug Mode:**
- Console akan show detailed logs
- Look for: `🔥 Form submit triggered!`
- If no logs → JavaScript error sebelum event listener

### Issue: "Confirm dialog keluar - tiada medan borang"

**Explanation:**
- System detect no fields in builder
- Confirm dialog ask: publish blank form atau cancel?

**Solutions:**
- Click **Cancel** → Tambah fields manually
- Click **OK** → Publish blank form (boleh edit kemudian)
- **Better:** Guna `wizard.php` untuk Excel import

---

## 🚀 Roadmap

### ✅ Phase 1: Foundation (DONE)
- [x] Unified entry point (`nocode_hub.php`)
- [x] Data-driven builder (`wizard.php`)
- [x] Visual builder (`builder.php`)
- [x] Basic workflow engine
- [x] Universal renderer (`engine.php`)

### 🔄 Phase 2: Enhancements (IN PROGRESS)
- [ ] Smart type inference (email, phone, currency)
- [ ] Relationship detection (foreign keys)
- [ ] True drag-drop canvas
- [ ] Component library
- [ ] Real-time preview

### 📅 Phase 3: Logic Builder (PLANNED)
- [ ] Visual workflow designer
- [ ] Complex conditions (AND/OR/NOT)
- [ ] More action types (API, calculations)
- [ ] Scheduled triggers (cron jobs)
- [ ] Multi-step approvals

### 🎯 Phase 4: Advanced (FUTURE)
- [ ] Mode switching mid-build
- [ ] Template library
- [ ] API endpoints per app
- [ ] User permissions/roles
- [ ] Version control
- [ ] Multi-language support

---

## 📞 Support

**Documentation:**
- [Architecture Guide](ARCHITECTURE_3IN1_BUILDER.md)
- [Test Results](TEST_RESULTS.md)
- [System Ready](SYSTEM_READY.md)

**Quick Links:**
- Builder Hub: `/myapps/nocode_hub.php`
- Data-Driven: `/myapps/wizard.php`
- Visual Builder: `/myapps/builder.php`
- Workflow Builder: `/myapps/workflow_builder.php`

---

**Last Updated:** 2026-02-09
**Version:** 2.0 - Flexible Builder System
**Status:** ✅ Production Ready (Data & Visual modes)
