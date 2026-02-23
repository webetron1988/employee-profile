# Employee Profile — Productionization Plan

## Executive Summary

Transform the current static HTML prototype into a production-ready, maintainable employee profile application. The project today is a single 14,184-line HTML file with hardcoded data, 14 inline scripts, 165 disconnected form elements, and no build system. This plan modularizes the codebase, adds a data layer, makes forms functional, completes the two placeholder tabs, and establishes a proper development workflow.

---

## Current State Audit

| Metric | Value |
|--------|-------|
| Main file | `workforce-profile.html` (14,184 lines) |
| Inline `<script>` blocks | 14 |
| External scripts (CDN) | 7 (jQuery, Select2, Tagify, Chart.js, etc.) |
| `<input>` elements | 115 |
| `<select>` elements | 35 |
| `<textarea>` elements | 12 |
| `<form>` tags | 3 (none functional — no `action`, no `name` attrs) |
| Offcanvas edit panels | 21 |
| Main tabs | 7 (5 complete, 2 "coming soon") |
| Sub-tabs | 35+ |
| API calls | 0 (all data hardcoded) |
| Build system | None |
| Package manager | None |
| Tests | None |
| Dev server | None |

---

## Phase 1: Project Scaffolding & Build System

### 1.1 — Initialize npm project

Create `package.json` with project metadata and scripts.

```
npm init -y
```

**Dev dependencies to install:**
- `vite` — Fast dev server with hot reload + production bundler
- `vite-plugin-handlebars` — HTML partials/templates support
- `eslint` + `eslint-config-jquery` — JavaScript linting
- `prettier` — Code formatting
- `stylelint` — CSS linting
- `concurrently` — Run multiple npm scripts
- `rimraf` — Cross-platform clean script

**npm scripts:**
```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview",
    "lint:js": "eslint src/**/*.js",
    "lint:css": "stylelint src/**/*.css",
    "lint": "npm run lint:js && npm run lint:css",
    "format": "prettier --write src/",
    "clean": "rimraf dist"
  }
}
```

### 1.2 — Configure Vite

Create `vite.config.js` for multi-page HTML app with Handlebars partials.

**Key config:**
- Input: `src/` directory with HTML entry points
- Output: `dist/` directory
- Handlebars partials from `src/partials/`
- Alias `@assets` → `src/assets/`
- Dev server on port 3000 with live reload
- Production: minify HTML/CSS/JS, asset hashing

### 1.3 — Create project directory structure

```
employee-profile/
├── package.json
├── vite.config.js
├── .eslintrc.json
├── .prettierrc
├── .stylelintrc.json
├── .gitignore                          # node_modules, dist, .env
├── src/
│   ├── index.html                      # Workforce Profile (main entry)
│   ├── partials/                       # Handlebars HTML partials
│   │   ├── head.hbs                    # <head> content (meta, CSS links)
│   │   ├── header-mobile.hbs           # Mobile header
│   │   ├── sidebar.hbs                 # Left aside menu
│   │   ├── header-desktop.hbs          # Desktop header + search + user menu
│   │   ├── profile-banner.hbs          # Employee banner (avatar, name, stats)
│   │   ├── tab-nav.hbs                 # Main tab navigation bar
│   │   ├── tabs/
│   │   │   ├── personal-profile.hbs    # Tab 1: Personal Profile
│   │   │   ├── identity-compliance.hbs # Tab 2: Identity & Compliance (NEW)
│   │   │   ├── job-organization.hbs    # Tab 3: Job & Organization
│   │   │   ├── ta-onboarding.hbs       # Tab 4: TA & Onboarding (NEW)
│   │   │   ├── performance.hbs         # Tab 5: Performance
│   │   │   ├── talent-management.hbs   # Tab 6: Talent Management
│   │   │   └── learning-development.hbs# Tab 7: Learning & Development
│   │   ├── offcanvas/
│   │   │   ├── basic-info.hbs          # oC_basic-info panel
│   │   │   ├── health-info.hbs         # oC_health-info panel
│   │   │   ├── family-details.hbs      # oC_family-details panel
│   │   │   ├── contact-info.hbs        # oC_contact-info panel
│   │   │   ├── add-language.hbs        # oC_add-language panel
│   │   │   ├── volunteer-activity.hbs  # oC_volunteer-activity panel
│   │   │   ├── hobbies-sports.hbs      # oC_hobbie-sports-talent panel
│   │   │   ├── appreciation.hbs        # oC_appreciation panel
│   │   │   ├── patent.hbs             # oC_patent panel
│   │   │   ├── physical-location.hbs   # oC_physical_location panel
│   │   │   ├── working-condition.hbs   # oC_working-condition panel
│   │   │   ├── add-goal.hbs           # oC_add-goal panel
│   │   │   ├── feedback-checkins.hbs   # oC_feedback-checkins panel
│   │   │   ├── add-training.hbs       # oC_add-training panel
│   │   │   ├── add-certificate.hbs    # oC_add-certificate panel
│   │   │   ├── add-idp-goal.hbs       # oC_add-IDP-goal panel
│   │   │   ├── talent-education.hbs   # oC_talent-education panel
│   │   │   ├── add-experience.hbs     # oC_add-experience panel
│   │   │   ├── add-skill.hbs         # oC_add-skill panel
│   │   │   ├── career-aspirations.hbs # oC_career-aspirations panel
│   │   │   └── mobility-preferences.hbs# oC_mobility-preferences panel
│   │   ├── modals/
│   │   │   └── toast-notification.hbs  # Success/error toast
│   │   └── footer.hbs                 # Scripts + footer
│   ├── assets/
│   │   ├── css/                        # (migrated from assets/css/)
│   │   │   ├── workforce-profile-module.css
│   │   │   ├── employee-profile-v2.css
│   │   │   ├── ui-v2.css
│   │   │   ├── bn-style.css
│   │   │   ├── responsive.css
│   │   │   └── ...
│   │   ├── js/
│   │   │   ├── app.js                 # Main application entry
│   │   │   ├── modules/
│   │   │   │   ├── subnav-scroll.js    # Extracted: sub-navigation scroll handler
│   │   │   │   ├── offcanvas.js        # Extracted: offcanvas panel manager
│   │   │   │   ├── select2-init.js     # Extracted: Select2 initialization
│   │   │   │   ├── datepicker-init.js  # Extracted: datepicker initialization
│   │   │   │   ├── toast.js            # Extracted: toast notification handler
│   │   │   │   ├── touchspin-init.js   # Extracted: touchspin initialization
│   │   │   │   ├── tagify-init.js      # Extracted: tagify initialization
│   │   │   │   ├── charts.js           # Extracted: Chart.js config (ratings, sentiment)
│   │   │   │   └── form-toggles.js     # Extracted: checkbox/toggle behaviors
│   │   │   ├── data/
│   │   │   │   ├── employee-data.js    # Structured employee profile data
│   │   │   │   ├── dropdown-options.js # Select2 options (countries, statuses, etc.)
│   │   │   │   └── chart-data.js       # Chart datasets
│   │   │   └── services/
│   │   │       ├── data-service.js     # CRUD abstraction (localStorage → API-ready)
│   │   │       ├── form-service.js     # Form populate/collect/validate
│   │   │       └── event-bus.js        # Simple pub/sub for module communication
│   │   ├── media/                     # (migrated from assets/media/)
│   │   ├── fonts/                     # (migrated from assets/fonts/)
│   │   ├── plugins/                   # (migrated from assets/plugins/)
│   │   └── images/                    # (migrated from assets/images/)
│   └── vendor/                        # Third-party libs not on CDN
│       └── ...
├── dist/                              # Production build output (gitignored)
└── legacy/                            # Original monolithic file (reference)
    └── workforce-profile.html
```

### 1.4 — Create `.gitignore`

```
node_modules/
dist/
.env
.env.local
*.log
.DS_Store
```

---

## Phase 2: HTML Modularization

### 2.1 — Split monolithic HTML into Handlebars partials

Break `workforce-profile.html` (14,184 lines) into ~35 partial files organized by function.

**Extraction map:**

| Lines (approx) | Partial File | Content |
|----------------|-------------|---------|
| 1–30 | `head.hbs` | `<head>` tag: meta, CSS links, favicon |
| 34–600 | `header-mobile.hbs` | Mobile header: logo, search, notifications, user toggle |
| 615–750 | `sidebar.hbs` | Left aside menu |
| 750–855 | `header-desktop.hbs` | Desktop header: search, notifications, user panel |
| 860–986 | `profile-banner.hbs` | Employee banner card |
| 988–1054 | `tab-nav.hbs` | Main 7-tab navigation strip |
| 1056–2758 | `tabs/personal-profile.hbs` | Tab 1 + 7 sub-tabs |
| 2759–2761 | `tabs/identity-compliance.hbs` | Tab 2 (to be built) |
| 2763–3852 | `tabs/job-organization.hbs` | Tab 3 + 8 sub-tabs |
| 3853–3855 | `tabs/ta-onboarding.hbs` | Tab 4 (to be built) |
| 3857–5356 | `tabs/performance.hbs` | Tab 5 + 4 sub-tabs |
| 5358–8871 | `tabs/talent-management.hbs` | Tab 6 + 10 sub-tabs |
| 8873–13400 | `tabs/learning-development.hbs` | Tab 7 + 6 sub-tabs |
| Various | `offcanvas/*.hbs` | 21 offcanvas edit panels |
| 13400–13410 | `modals/toast-notification.hbs` | Toast component |
| 13427–14184 | `footer.hbs` | Scripts + initialization |

### 2.2 — Create main `index.html` assembling partials

```html
<!DOCTYPE html>
<html lang="en">
{{> head }}
<body id="kt_body" class="...">
  <div id="kt_header_mobile">{{> header-mobile }}</div>
  <div class="d-flex flex-column flex-root">
    <div class="d-flex flex-row flex-column-fluid page">
      {{> sidebar }}
      <div class="d-flex flex-column flex-row-fluid wrapper">
        {{> header-desktop }}
        <div class="content" id="kt_content">
          <div class="container-fluid">
            <div class="ui-v2">
              {{> profile-banner }}
              {{> tab-nav }}
              <div class="tab-content">
                {{> tabs/personal-profile }}
                {{> tabs/identity-compliance }}
                {{> tabs/job-organization }}
                {{> tabs/ta-onboarding }}
                {{> tabs/performance }}
                {{> tabs/talent-management }}
                {{> tabs/learning-development }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  {{> offcanvas/basic-info }}
  {{> offcanvas/health-info }}
  <!-- ... all 21 offcanvas partials ... -->
  {{> modals/toast-notification }}
  {{> footer }}
</body>
</html>
```

### 2.3 — Parameterize partials with Handlebars context

Pass employee data to partials so they render dynamically:

```javascript
// vite.config.js — handlebars context
{
  context: {
    employee: require('./src/assets/js/data/employee-data.js')
  }
}
```

Partials reference data like:
```handlebars
<h4 class="text-black font-weight-bolder mb-0">{{employee.fullName}}</h4>
<span class="text-muted">{{employee.jobTitle}}</span>
```

---

## Phase 3: JavaScript Modularization

### 3.1 — Extract inline scripts into separate modules

**14 inline scripts → 9 module files:**

| Inline Script (Line) | Target Module | Description |
|----------------------|---------------|-------------|
| 13428–13430 (KTAppSettings) | `app.js` | Global config — keep as global |
| 13438–13440 (Perfect Scrollbar) | `app.js` | One-liner init |
| 13442–13573 (Sub-nav scroll) | `modules/subnav-scroll.js` | Tab scroll with arrows, centering |
| 13574–13633 (Offcanvas) | `modules/offcanvas.js` | Open/close, overlay, ESC handler |
| 13637–13750 (Select2) | `modules/select2-init.js` | Country select2 with flags + templates |
| 13752–13892 (Datepicker) | `modules/datepicker-init.js` | 8 datepicker field initializations |
| 13895–13931 (Toast) | `modules/toast.js` | Toast notification with timer |
| 13933–13969 (Touchspin) | `modules/touchspin-init.js` | Numeric spinner init |
| 13971–13995 (Tagify) | `modules/tagify-init.js` | Hobbies/sports/talents tagify |
| 13996–14017 (Accommodation) | `modules/form-toggles.js` | Checkbox show/hide logic |
| 14019–14103 (Rating chart) | `modules/charts.js` | Chart.js line chart |
| 14104–14154 (Sentiment chart) | `modules/charts.js` | Chart.js doughnut chart |
| 14156–14180 (Current job toggle) | `modules/form-toggles.js` | Datepicker enable/disable |

### 3.2 — Create `app.js` entry point

```javascript
// src/assets/js/app.js
import { initSubnavScroll } from './modules/subnav-scroll.js';
import { initOffcanvas } from './modules/offcanvas.js';
import { initSelect2 } from './modules/select2-init.js';
import { initDatepickers } from './modules/datepicker-init.js';
import { initToast } from './modules/toast.js';
import { initTouchspin } from './modules/touchspin-init.js';
import { initTagify } from './modules/tagify-init.js';
import { initCharts } from './modules/charts.js';
import { initFormToggles } from './modules/form-toggles.js';
import { DataService } from './services/data-service.js';
import { FormService } from './services/form-service.js';

$(document).ready(function() {
  // Initialize all modules
  initSubnavScroll();
  initOffcanvas();
  initSelect2();
  initDatepickers();
  initToast();
  initTouchspin();
  initTagify();
  initCharts();
  initFormToggles();

  // Initialize data and form services
  window.dataService = new DataService();
  window.formService = new FormService(window.dataService);
});
```

### 3.3 — Module pattern for each extracted script

Each module exports an `init` function using the revealing module pattern (jQuery-compatible):

```javascript
// src/assets/js/modules/offcanvas.js
export function initOffcanvas() {
  let activeOffcanvas = null;

  document.addEventListener('click', function(e) {
    var trigger = e.target.closest('.offcanvas-trigger');
    if (!trigger) return;
    // ... existing logic, cleaned up ...
  });

  // ... close, ESC handler, overlay ...
}
```

---

## Phase 4: Data Layer

### 4.1 — Create structured employee data model

Extract all hardcoded employee data into `src/assets/js/data/employee-data.js`:

```javascript
var EmployeeData = {
  // Basic Information
  personal: {
    id: 'EMP-2022-0847',
    firstName: 'John',
    middleName: 'David',
    lastName: 'Mitchell',
    fullName: 'John David Mitchell',
    pronouns: 'He/Him',
    dateOfBirth: '1989-03-15',
    gender: 'Male',
    maritalStatus: 'Married',
    marriageDate: '2015-06-20',
    nationality: 'American',
    citizenshipType: 'Natural Born',
    religion: 'Christianity',
    passportNumber: 'US-XXXX5678',
    passportExpiry: '2029-03-14',
    panNumber: 'ABCDE1234F',
    aadharNumber: 'XXXX-XXXX-5678',
    avatar: 'assets/media/users/300_21.jpg',
    status: 'Active'
  },

  // Physical & Health
  health: {
    bloodGroup: 'O+',
    bmi: 24.4,
    height: { feet: 5, inches: 11 },
    weight: 175,
    weightUnit: 'lbs',
    lastMedicalCheckup: '2024-01-15',
    visionStatus: 'Corrected',
    correctionType: 'Contact Lenses',
    allergies: 'None Known',
    chronicConditions: 'None',
    physicalDisability: 'None',
    distinguishingMarks: ''
  },

  // Contact & Social
  contact: {
    workEmail: 'john.mitchell@company.com',
    personalEmail: 'john.m@personal.com',
    workPhone: '+1 (212) 555-0147',
    personalPhone: '+1 (212) 555-0148',
    location: 'New York, USA',
    currentAddress: {
      street: '123 Main St',
      city: 'New York',
      state: 'New York',
      country: 'USA',
      zipCode: '10001'
    },
    permanentAddress: {
      street: '456 Oak Ave',
      city: 'Boston',
      state: 'Massachusetts',
      country: 'USA',
      zipCode: '02101'
    },
    socialMedia: {
      linkedin: '',
      twitter: '',
      github: '',
      website: ''
    }
  },

  // Job & Organization
  job: {
    title: 'Senior Data Scientist',
    code: 'DS-SR-003',
    positionId: 'POS-2022-0847',
    family: 'Data Science & Analytics',
    subFamily: 'Machine Learning',
    department: 'Data Science & AI Division',
    grade: 'L5',
    status: 'Active',
    yearsOfService: 8.5,
    rating: 4.8,
    awards: 12
  },

  // Performance
  performance: {
    currentRating: 4.5,
    goalAchievement: 115,
    streak: 5,
    averageRating: 4.3,
    ratingHistory: [
      { year: 2020, rating: 4.3 },
      { year: 2021, rating: 4.2 },
      { year: 2022, rating: 4.8 },
      { year: 2023, rating: 4.5 },
      { year: 2024, rating: 4.5 }
    ],
    sentiment: { positive: 4, neutral: 2, negative: 1 }
  },

  // Education
  education: [
    {
      degree: 'M.S.',
      field: 'Data Science',
      institution: 'Stanford University',
      yearFrom: 2014,
      yearTo: 2016,
      gpa: '3.9/4.0',
      honors: 'Magna Cum Laude',
      researchWork: 'Deep Learning Applications in NLP'
    }
  ],

  // Skills
  skills: {
    validated: [
      { name: 'Machine Learning', endorsements: 15 },
      { name: 'Python', endorsements: 12 },
      { name: 'Statistical Analysis', endorsements: 10 },
      { name: 'SQL', endorsements: 8 },
      { name: 'Deep Learning', endorsements: 11 },
      { name: 'TensorFlow', endorsements: 9 },
      { name: 'Data Visualization', endorsements: 7 },
      { name: 'AWS', endorsements: 6 }
    ],
    developing: ['GenAI/LLMs', 'MLOps'],
    totalEndorsements: 78
  },

  // Learning & Development
  learning: {
    totalHours: 156,
    coursesCompleted: 24,
    investmentAmount: 8500,
    hoursThisYear: 40
  },

  // Family
  family: [],

  // Languages
  languages: [],

  // Work Experience
  experience: [],

  // Certifications
  certifications: [],

  // Hobbies
  hobbies: [],
  sports: [],
  talents: []
};
```

### 4.2 — Create dropdown options data

`src/assets/js/data/dropdown-options.js`:

```javascript
var DropdownOptions = {
  countries: [
    { id: 'AT', text: 'Austria', flag: '001-austria.svg' },
    { id: 'IN', text: 'India', flag: '010-india.svg' },
    // ... full country list
  ],
  pronouns: ['He/Him', 'She/Her', 'They/Them', 'Other'],
  maritalStatus: ['Single', 'Married', 'Divorced', 'Widowed', 'Separated'],
  bloodGroups: ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'],
  visionStatus: ['Normal', 'Corrected', 'Impaired'],
  correctionTypes: ['Glasses', 'Contact Lenses', 'LASIK', 'None'],
  relationships: ['Spouse', 'Child', 'Parent', 'Sibling', 'Other'],
  genders: ['Male', 'Female', 'Non-binary', 'Prefer not to say'],
  proficiencyLevels: ['Beginner', 'Intermediate', 'Advanced', 'Expert', 'Native'],
  skillProficiency: ['Novice', 'Beginner', 'Competent', 'Proficient', 'Expert'],
  certificationStatus: ['Active', 'Expired', 'In Progress', 'Planned'],
  trainingTypes: ['Online Course', 'Workshop', 'Conference', 'Certification Prep', 'On-the-job'],
  goalCategories: ['Technical', 'Leadership', 'Business', 'Personal Development'],
  feedbackTypes: ['Manager Review', 'Peer Feedback', '360 Feedback', 'Self Assessment'],
  idDocumentTypes: ['Passport', 'National ID', 'Driving License', 'Social Security', 'Work Permit', 'Visa'],
  complianceStatus: ['Compliant', 'Pending Review', 'Non-Compliant', 'Expired'],
  onboardingStatus: ['Not Started', 'In Progress', 'Completed', 'Overdue'],
  workArrangements: ['On-site', 'Remote', 'Hybrid'],
  accommodationTypes: ['Ergonomic Setup', 'Screen Reader', 'Height-adjustable Desk', 'Other']
};
```

### 4.3 — Create Data Service (localStorage → API-ready)

`src/assets/js/services/data-service.js`:

```javascript
function DataService() {
  this.STORAGE_KEY = 'employee_profile_data';
}

// Load employee data (localStorage now, API later)
DataService.prototype.load = function() {
  var stored = localStorage.getItem(this.STORAGE_KEY);
  if (stored) {
    return JSON.parse(stored);
  }
  // Fall back to default data
  return JSON.parse(JSON.stringify(EmployeeData));
};

// Save employee data
DataService.prototype.save = function(data) {
  localStorage.setItem(this.STORAGE_KEY, JSON.stringify(data));
  $(document).trigger('employee:data:saved', [data]);
};

// Update a specific section
DataService.prototype.updateSection = function(section, values) {
  var data = this.load();
  data[section] = $.extend(true, data[section], values);
  this.save(data);
  return data[section];
};

// Add item to an array section (family, education, experience, etc.)
DataService.prototype.addItem = function(section, item) {
  var data = this.load();
  if (!Array.isArray(data[section])) data[section] = [];
  item._id = Date.now().toString(36);
  data[section].push(item);
  this.save(data);
  return item;
};

// Remove item from array section
DataService.prototype.removeItem = function(section, itemId) {
  var data = this.load();
  data[section] = (data[section] || []).filter(function(item) {
    return item._id !== itemId;
  });
  this.save(data);
};

// Reset to defaults
DataService.prototype.reset = function() {
  localStorage.removeItem(this.STORAGE_KEY);
};
```

### 4.4 — Create Form Service

`src/assets/js/services/form-service.js`:

```javascript
function FormService(dataService) {
  this.dataService = dataService;
  this._bindOffcanvasSaveButtons();
}

// Populate offcanvas form from data
FormService.prototype.populateForm = function(offcanvasId, section) {
  var data = this.dataService.load();
  var sectionData = data[section];
  var $panel = $(offcanvasId);

  $panel.find('[data-field]').each(function() {
    var field = $(this).data('field');
    var value = sectionData[field];
    if (value !== undefined) {
      if ($(this).hasClass('select2')) {
        $(this).val(value).trigger('change');
      } else if ($(this).is(':checkbox')) {
        $(this).prop('checked', value);
      } else {
        $(this).val(value);
      }
    }
  });
};

// Collect form data from offcanvas
FormService.prototype.collectForm = function(offcanvasId) {
  var result = {};
  $(offcanvasId).find('[data-field]').each(function() {
    var field = $(this).data('field');
    if ($(this).hasClass('select2')) {
      result[field] = $(this).val();
    } else if ($(this).is(':checkbox')) {
      result[field] = $(this).is(':checked');
    } else {
      result[field] = $(this).val();
    }
  });
  return result;
};

// Validate required fields
FormService.prototype.validateForm = function(offcanvasId) {
  var valid = true;
  $(offcanvasId).find('[data-required]').each(function() {
    if (!$(this).val() || $(this).val().trim() === '') {
      $(this).addClass('is-invalid');
      valid = false;
    } else {
      $(this).removeClass('is-invalid');
    }
  });
  return valid;
};

// Bind save buttons in all offcanvas panels
FormService.prototype._bindOffcanvasSaveButtons = function() {
  var self = this;
  $(document).on('click', '.offcanvas [data-save-section]', function() {
    var $btn = $(this);
    var section = $btn.data('save-section');
    var offcanvasId = '#' + $btn.closest('.offcanvas').attr('id');

    if (!self.validateForm(offcanvasId)) return;

    var formData = self.collectForm(offcanvasId);
    self.dataService.updateSection(section, formData);

    // Show success toast
    $(document).trigger('toast:show', ['Changes saved successfully.']);

    // Close offcanvas
    $(offcanvasId).removeClass('offcanvas-on');
    $('body').removeClass('modal-open');
    $('.offcanvas-overlay').remove();
  });
};
```

---

## Phase 5: Form System — Make Forms Functional

### 5.1 — Add `data-field` attributes to all form inputs

Every form input gets a `data-field` attribute mapping it to the employee data model:

**Example (Basic Info offcanvas):**
```html
<!-- Before (current) -->
<input type="text" class="form-control" name="" placeholder="Enter text here">

<!-- After (productionized) -->
<input type="text" class="form-control" data-field="firstName" data-required placeholder="First Name">
```

**Mapping for all 21 offcanvas panels:**

| Offcanvas | Section | Fields |
|-----------|---------|--------|
| `oC_basic-info` | `personal` | firstName, middleName, lastName, pronouns, dateOfBirth, gender, country, state, city, maritalStatus, marriageDate, nationality, citizenshipType, passportNumber, passportExpiry, panNumber, aadharNumber, religion |
| `oC_health-info` | `health` | bloodGroup, height.feet, height.inches, weight, lastMedicalCheckup, visionStatus, correctionType, allergies, chronicConditions, physicalDisability, distinguishingMarks |
| `oC_family-details` | `family[]` | name, relationship, occupation, age, gender, contactNumber |
| `oC_contact-info` | `contact` | workPhone, personalPhone, extension, workEmail, personalEmail, currentAddress.*, permanentAddress.*, socialMedia.* |
| `oC_add-language` | `languages[]` | language, proficiency |
| `oC_volunteer-activity` | `volunteer[]` | activity, organization, role, hours, recognition |
| `oC_hobbie-sports-talent` | `hobbies`, `sports`, `talents` | (tagify arrays) |
| `oC_appreciation` | `appreciations[]` | type, title, description, date, value |
| `oC_patent` | `patents[]` | title, description, filingDate, status, patentNumber, amount |
| `oC_physical_location` | `job.location` | office, building, floor, seat, workArrangement, daysPerWeek, timezone, country, address |
| `oC_working-condition` | `job.workingConditions` | accommodationType, details, assessmentDate, requirements |
| `oC_add-goal` | `goals[]` | title, category, weight, milestone |
| `oC_feedback-checkins` | `feedback[]` | type, reviewer, topic, description, date |
| `oC_add-training` | `training[]` | title, type, provider, date, hours, status |
| `oC_add-certificate` | `certifications[]` | name, issuer, obtainedDate, expiryDate, cost |
| `oC_add-IDP-goal` | `idpGoals[]` | goal, category, target, status, cost, notes |
| `oC_talent-education` | `education[]` | degree, field, institution, yearFrom, yearTo, gpa, honors, researchWork |
| `oC_add-experience` | `experience[]` | title, company, startDate, endDate, isCurrent, location, responsibilities |
| `oC_add-skill` | `skills.validated[]` | name, proficiency |
| `oC_career-aspirations` | `career` | aspiredRole, timeline, readiness, preferences, interests, notes |
| `oC_mobility-preferences` | `mobility` | departments, locations, roles, availableDate, requirements |

### 5.2 — Add save/cancel buttons to each offcanvas

Each offcanvas panel footer gets:
```html
<div class="offcanvas-footer d-flex gap-10 p-5 border-top">
  <button class="btn btn-primary" data-save-section="personal">Save Changes</button>
  <button class="btn btn-outline-secondary offcanvas-close">Cancel</button>
</div>
```

### 5.3 — Wire display sections to data

When employee data changes, update the read-only display sections automatically:

```javascript
// Listen for data changes and re-render affected display sections
$(document).on('employee:data:saved', function(e, data) {
  renderProfileBanner(data);
  renderBasicInfo(data.personal);
  renderHealthInfo(data.health);
  renderContactInfo(data.contact);
  // ... etc for each display section
});
```

---

## Phase 6: Complete "Coming Soon" Tabs

### 6.1 — Tab 2: Identity & Compliance

**Sub-tabs to build (following existing design patterns):**

| Sub-tab | Content |
|---------|---------|
| Identity Documents | ID cards, passport, driving license, social security — view/edit with document upload placeholder |
| Background Verification | Background check status, verification date, provider, result |
| Compliance Training | Mandatory trainings (anti-harassment, data privacy, safety) — completion status |
| Policy Acknowledgements | Company policies accepted/pending — sign dates, versions |
| Export Control | ITAR/EAR classification, citizenship verification, restricted party screening |
| Right to Work | Work authorization type, visa status, expiry, sponsorship details |

**Offcanvas panels to add (3 new):**
- `oC_add-document` — Upload/register ID documents
- `oC_background-check` — Record background verification
- `oC_compliance-training` — Record compliance training completion

**Data model additions:**
```javascript
{
  identity: {
    documents: [],      // { type, number, issueDate, expiryDate, issuingCountry, verified }
    backgroundCheck: {}, // { status, date, provider, result, notes }
    complianceTraining: [], // { name, completedDate, expiryDate, status, certificate }
    policyAcknowledgements: [], // { name, version, signedDate, status }
    exportControl: {},  // { classification, verified, screeningDate }
    rightToWork: {}     // { type, visaStatus, expiryDate, sponsorship }
  }
}
```

### 6.2 — Tab 4: TA & Onboarding

**Sub-tabs to build:**

| Sub-tab | Content |
|---------|---------|
| Recruitment Summary | Source, application date, interview rounds, offer details |
| Offer Details | Compensation, benefits, start date, signing bonus |
| Pre-Boarding Checklist | Tasks before day-1 (equipment, accounts, badge) — checklist with status |
| Onboarding Plan | Day-1 to Day-90 milestones, tasks, assigned buddy/mentor |
| Probation Tracker | Probation period, review dates, status, extension notes |
| Induction Training | Induction sessions attended, pending, completion % |

**Offcanvas panels to add (2 new):**
- `oC_onboarding-task` — Add/edit onboarding task
- `oC_probation-review` — Record probation review

**Data model additions:**
```javascript
{
  recruitment: {
    source: '',
    applicationDate: '',
    interviewRounds: [],
    offerDetails: {}
  },
  onboarding: {
    preBoardingTasks: [],   // { task, status, dueDate, completedDate }
    onboardingPlan: [],     // { milestone, dueDate, status, assignee }
    probation: {},          // { startDate, endDate, status, reviews: [] }
    inductionTraining: []   // { session, date, status }
  }
}
```

---

## Phase 7: CSS Organization

### 7.1 — Audit and consolidate CSS

**Current state:** 335+ CSS files in `assets/css/`, many duplicated or versioned copies.

**Action items:**
- Remove backup/dated copies: `style.bundle - Copy.css`, `style.bundle-10nov2023.css`, `style.bundle-21sep23.css`, `style.bundle-25aug.css`, `style.bundle-29aug24.css`
- Remove RTL copies if not needed (20+ `.rtl.css` files)
- Keep only the active CSS files referenced by the HTML

**Active CSS files (referenced in `<head>`):**
1. `plugins/global/plugins.bundle.css` — Plugin styles
2. `plugins/custom/prismjs/prismjs.bundle.css` — Code highlighting
3. `css/style.bundle.css` — Main theme styles
4. `css/responsive.css` — Responsive overrides
5. `css/bn-style.css` — Custom brand styles
6. `css/themes/layout/header/base/light.css` — Header theme
7. `css/themes/layout/header/menu/light.css` — Menu theme
8. `css/themes/layout/brand/light.css` — Brand theme
9. `css/themes/layout/aside/light.css` — Sidebar theme
10. `css/ui-v2.css` — V2 UI system
11. `css/workforce-profile-module.css` — Module-specific styles

### 7.2 — Add CSS custom properties

Create a `css/variables.css` consolidating the design tokens used across the codebase:

```css
:root {
  /* Colors (from KTAppSettings + bn-style.css) */
  --color-primary: #3699FF;
  --color-success: #1BC5BD;
  --color-info: #4e3eb4;
  --color-warning: #FFA800;
  --color-danger: #F64E60;
  --color-dark: #181C32;

  /* Borders */
  --border-light: #E4E6EF;
  --border-light-primary-v2: #c5d9f7;

  /* Typography */
  --font-family: 'Poppins', sans-serif;

  /* Spacing */
  --gap-5: 5px;
  --gap-10: 10px;
  --gap-15: 15px;
  --gap-20: 20px;

  /* Border radius */
  --radius-sm: 0.42rem;
  --radius-md: 0.65rem;
  --radius-lg: 0.85rem;
  --radius-xl: 1rem;
}
```

---

## Phase 8: Development Workflow & Quality

### 8.1 — ESLint configuration

`.eslintrc.json`:
```json
{
  "env": { "browser": true, "jquery": true, "es6": true },
  "globals": { "KTAppSettings": "readonly", "EmployeeData": "writable", "DropdownOptions": "readonly" },
  "rules": {
    "no-unused-vars": "warn",
    "no-undef": "error",
    "eqeqeq": "warn",
    "no-eval": "error"
  }
}
```

### 8.2 — Prettier configuration

`.prettierrc`:
```json
{
  "semi": true,
  "singleQuote": true,
  "tabWidth": 2,
  "printWidth": 120
}
```

### 8.3 — Git hooks (optional)

```json
// package.json
"husky": {
  "hooks": {
    "pre-commit": "npm run lint"
  }
}
```

---

## Implementation Order

```
Phase 1: Project Scaffolding          ← Foundation (do first)
  ├── 1.1 npm init + dependencies
  ├── 1.2 Vite config
  ├── 1.3 Directory structure
  └── 1.4 .gitignore
          │
Phase 2: HTML Modularization          ← Break up monolith
  ├── 2.1 Extract 35+ partials
  ├── 2.2 Assemble index.html
  └── 2.3 Parameterize with data
          │
Phase 3: JS Modularization            ← Clean up scripts
  ├── 3.1 Extract 14 inline scripts → 9 modules
  ├── 3.2 Create app.js entry
  └── 3.3 Module pattern
          │
Phase 4: Data Layer                   ← Enable functionality
  ├── 4.1 Employee data model
  ├── 4.2 Dropdown options
  ├── 4.3 Data service (localStorage)
  └── 4.4 Form service
          │
Phase 5: Form System                  ← Make forms work
  ├── 5.1 Add data-field attributes
  ├── 5.2 Save/cancel buttons
  └── 5.3 Wire display ↔ data
          │
Phase 6: Complete Missing Tabs        ← Finish UI
  ├── 6.1 Identity & Compliance (6 sub-tabs)
  └── 6.2 TA & Onboarding (6 sub-tabs)
          │
Phase 7: CSS Organization             ← Clean up styles
  ├── 7.1 Remove duplicates
  └── 7.2 CSS variables
          │
Phase 8: Dev Workflow                  ← Quality gates
  ├── 8.1 ESLint
  ├── 8.2 Prettier
  └── 8.3 Git hooks
```

---

## Summary of Deliverables

| Category | Count |
|----------|-------|
| New HTML partials | ~35 files |
| New JS modules | 9 module files + 3 service files + 3 data files |
| New offcanvas panels | 5 (for new tabs) |
| New sub-tabs | 12 (6 per new tab) |
| Modified form elements | ~165 (add `data-field` + `data-required` attrs) |
| CSS files cleaned | ~20 removed (duplicates/backups) |
| Config files | 5 (package.json, vite.config.js, .eslintrc, .prettierrc, .gitignore) |
| **Monolithic HTML → Partials** | **14,184 lines → ~35 manageable files** |
