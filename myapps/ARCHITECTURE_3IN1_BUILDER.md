# 🏗️ Architecture: 3-in-1 Flexible No-Code Builder

## 🎯 Vision
Satu unified builder yang support **3 pendekatan berbeza** untuk cipta aplikasi, semua output ke format metadata yang sama.

---

## 📊 Mode 1: DATA-DRIVEN (AppSheet Style)

### Concept
**"Start with data, UI follows automatically"**

### User Flow
1. **Upload Data Source**
   - Excel/CSV upload
   - Google Sheets integration (future)
   - Database table import
   
2. **Auto-Generate Everything**
   - Fields auto-detected from headers
   - Data types inferred (text, number, date, email, phone)
   - Relationships detected (foreign keys)
   - Default views created (list, form, detail)

3. **Quick Customization**
   - Rename fields
   - Change field types
   - Set validation rules
   - Configure display format

### Key Features
- ✅ Excel/CSV upload with auto-field detection
- ✅ Smart type inference (email, phone, currency, date)
- ✅ Bulk data import
- ✅ Auto-generate CRUD views
- ✅ Reference/lookup fields (relations between tables)
- 🔄 Sync with external data sources

### Current Status
- ✅ `wizard.php` - Partially implemented (Excel upload + field extraction)
- ⚠️ Need enhancement: Type inference, relationships, auto-views

---

## 🎨 Mode 2: VISUAL-DRIVEN (Glide/Adalo Style)

### Concept
**"Design the UI first, connect data later"**

### User Flow
1. **Choose Template or Start Blank**
   - Pre-built templates (CRM, Inventory, HR, etc)
   - Blank canvas with components library

2. **Drag & Drop Interface**
   - Add components: Forms, Lists, Cards, Charts, Maps
   - Arrange layout with visual grid
   - Style with theme picker
   - Preview in real-time

3. **Connect Data**
   - Bind components to data sources
   - Configure actions (submit, navigate, filter)
   - Set conditional visibility

### Key Features
- ✅ Component library (forms, tables, cards, charts)
- ✅ Drag & drop layout builder
- ✅ Real-time preview
- ✅ Theme customization
- 🔄 Conditional visibility rules
- 🔄 Component actions (click, submit, navigate)
- 🔄 Multi-page navigation

### Current Status
- ✅ `builder.php` - Partially implemented (field builder, page manager)
- ⚠️ Need enhancement: True drag-drop canvas, component library, data binding

---

## ⚙️ Mode 3: LOGIC-DRIVEN (Bubble.io Style)

### Concept
**"Define workflows and business logic first"**

### User Flow
1. **Define Data Schema**
   - Create data types (like database tables)
   - Define fields and relationships
   - Set constraints and validation

2. **Build Workflows**
   - Triggers: User action, schedule, webhook, data change
   - Conditions: If-then-else logic
   - Actions: Create/update data, send email, API call, navigate
   - Complex logic: Loops, calculations, multi-step processes

3. **Design Interface**
   - Add UI elements
   - Connect to workflows
   - Set dynamic content

### Key Features
- ✅ If-This-Then-That workflow engine
- ✅ Email notifications
- 🔄 Scheduled workflows (cron jobs)
- 🔄 API integrations (webhooks, REST)
- 🔄 Custom calculations/formulas
- 🔄 Multi-step approval processes
- 🔄 Conditional logic (AND/OR/NOT)
- 🔄 Data transformations

### Current Status
- ✅ `workflow_processor.php` - Basic implementation (trigger, condition, action)
- ⚠️ Need enhancement: Complex conditions, more action types, scheduled triggers

---

## 🔗 Unified Architecture

### Core Concept
**All 3 modes output to the same metadata format**, stored in `custom_apps.metadata`:

```json
{
  "app_info": {
    "name": "My App",
    "slug": "my-app",
    "category": "internal",
    "builder_mode": "data-driven|visual-driven|logic-driven"
  },
  
  "data_schema": {
    "fields": [
      {
        "name": "customer_name",
        "type": "text",
        "label": "Customer Name",
        "required": true,
        "validation": {...}
      }
    ],
    "relationships": [
      {
        "type": "one-to-many",
        "from": "orders",
        "to": "customers",
        "key": "customer_id"
      }
    ]
  },
  
  "ui_layout": {
    "pages": [
      {
        "id": "list_page",
        "type": "list",
        "components": [
          {
            "type": "table",
            "data_source": "main",
            "columns": ["customer_name", "email"],
            "actions": ["view", "edit", "delete"]
          }
        ]
      }
    ],
    "theme": {
      "primary_color": "#3b82f6",
      "layout": "sidebar"
    }
  },
  
  "workflows": [
    {
      "id": "wf_1",
      "trigger": "record_created",
      "conditions": [
        {
          "field": "status",
          "operator": "equals",
          "value": "pending"
        }
      ],
      "actions": [
        {
          "type": "send_email",
          "to": "admin@example.com",
          "subject": "New record pending approval"
        }
      ]
    }
  ],
  
  "settings": {
    "enable_search": true,
    "enable_export": true,
    "enable_api": false,
    "permissions": {...}
  }
}
```

### Shared Components

**1. Data Layer** (`engine.php` + `custom_app_data` table)
- CRUD operations
- Data validation
- Relationships/lookups

**2. Workflow Engine** (`workflow_processor.php`)
- Trigger detection
- Condition evaluation
- Action execution

**3. Renderer** (`engine.php`)
- Read metadata
- Generate UI from components
- Handle user interactions

**4. Metadata Manager**
- Validate metadata structure
- Version control
- Migration tools

---

## 🚀 Implementation Plan

### Phase 1: Unified Entry Point ✅ START HERE
**File:** `nocode_hub.php`

Create a landing page where users choose their builder mode:

```
┌─────────────────────────────────────────────┐
│     🎯 Choose Your Building Style           │
├─────────────────────────────────────────────┤
│                                             │
│  📊 DATA-DRIVEN          🎨 VISUAL-DRIVEN  │
│  Start with Excel        Drag & Drop UI    │
│  [Quick & Easy]          [Full Control]    │
│                                             │
│  ⚙️ LOGIC-DRIVEN          📋 IMPORT        │
│  Workflows First         From Template     │
│  [Advanced]              [Fast Start]      │
│                                             │
└─────────────────────────────────────────────┘
```

### Phase 2: Enhance Existing Builders
1. **wizard.php (Data-Driven)**
   - ✅ Excel upload (done)
   - 🔄 Smart type inference
   - 🔄 Auto-generate views
   - 🔄 Relationship detection

2. **builder.php (Visual-Driven)**
   - ✅ Field builder (done)
   - 🔄 True drag-drop canvas
   - 🔄 Component library
   - 🔄 Real-time preview

3. **workflow_builder.php (Logic-Driven)** - NEW
   - 🔄 Visual workflow designer
   - 🔄 Complex conditions
   - 🔄 More action types

### Phase 3: Unified Metadata Format
- Standardize all builders to output same JSON structure
- Build metadata validator
- Create migration tools

### Phase 4: Advanced Features
- API endpoints for each app
- Mobile responsive views
- User permissions/roles
- Multi-language support
- Version control & rollback

---

## 📁 File Structure

```
myapps/
├── nocode_hub.php              # Main entry point - choose builder mode
├── wizard.php                  # Data-driven builder (AppSheet style)
├── builder.php                 # Visual builder (Glide style)
├── workflow_builder.php        # Logic builder (Bubble style) - NEW
├── builder_save.php            # Save metadata from any builder
├── engine.php                  # Universal renderer
├── workflow_processor.php      # Workflow execution engine
├── components/
│   ├── metadata_validator.php # Validate metadata structure
│   ├── type_inference.php     # Smart field type detection
│   ├── relationship_detector.php # Find FK relationships
│   └── template_library.php   # Pre-built app templates
└── assets/
    ├── builder_components.js  # Reusable UI components
    └── workflow_designer.js   # Visual workflow editor
```

---

## 🎨 UI/UX Principles

### Consistency
- Same header/navigation across all builders
- Unified save/publish flow
- Consistent terminology

### Flexibility
- Switch between modes mid-build
- Import/export between modes
- Hybrid approach (start data-driven, customize visually)

### Progressive Disclosure
- Simple mode by default
- "Advanced" options hidden until needed
- Contextual help & tooltips

### Real-time Feedback
- Live preview as you build
- Validation errors immediately
- Success confirmations

---

## 🔄 Mode Switching

Users can **switch modes** during build:

```
Data-Driven → Visual-Driven
(Excel uploaded, fields auto-created)
↓
(User wants custom layout)
↓
Switch to Visual Builder
(Fields preserved, now can drag-drop UI)
```

**Implementation:**
- Save current state to session/temp metadata
- Load into new builder mode
- Preserve all data + add new capabilities

---

## 💡 Unique Selling Points

1. **3-in-1 Flexibility** - Choose your style or combine them
2. **No Lock-in** - Switch modes anytime
3. **Smart Defaults** - Auto-generate everything, customize what you need
4. **Local & Self-hosted** - Full control, no monthly fees
5. **Malaysian Context** - Bahasa Malaysia, local workflows, government forms

---

## 📊 Comparison with Competitors

| Feature | MyApps | AppSheet | Glide | Bubble.io |
|---------|--------|----------|-------|-----------|
| Data-driven | ✅ | ✅ | ✅ | ❌ |
| Visual UI builder | ✅ | ⚠️ | ✅ | ✅ |
| Workflow engine | ✅ | ✅ | ⚠️ | ✅ |
| Self-hosted | ✅ | ❌ | ❌ | ❌ |
| Free | ✅ | ❌ | ⚠️ | ⚠️ |
| Bahasa Malaysia | ✅ | ❌ | ❌ | ❌ |
| Mode switching | ✅ | ❌ | ❌ | ❌ |

---

## 🎯 Next Steps

1. ✅ Create `nocode_hub.php` - Unified entry point
2. 🔄 Enhance `wizard.php` - Better data-driven features
3. 🔄 Upgrade `builder.php` - True visual builder
4. 🔄 Build `workflow_builder.php` - Advanced logic editor
5. 🔄 Standardize metadata format
6. 🔄 Add mode switching capability
7. 🔄 Build template library
8. 🔄 Add API layer

---

**Status:** 🚧 Architecture Defined - Ready for Implementation
**Priority:** Create `nocode_hub.php` first as unified entry point
