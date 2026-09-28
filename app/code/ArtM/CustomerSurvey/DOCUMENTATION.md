# ArtM CustomerSurvey - Complete Documentation

## 1) Module Purpose

The module provides a full survey system for storefront visitors with:

- Question Bank management
- Location targeting using CSS selectors
- Survey Journey creation (questions + locations + display + frequency)
- Frontend rendering (inline and popup)
- Analytics and reports (impressions, opens, responses, completion rate)

Main module path:

- `code/ArtM/CustomerSurvey`

---

## 2) Admin Menu Structure

Menu config source:

- `etc/adminhtml/menu.xml`

Top level (under ArtM / BlogEsg):

- Customer Surveys

Sub menus:

1. Question Bank
2. Locations
3. Survey Journeys
4. Reports and Analytics

ACL permissions source:

- `etc/acl.xml`

ACL includes separate resources for:

- View each menu
- Save/Delete for Questions, Locations, Surveys
- Mass actions
- Report export

---

## 3) System Configuration (Stores -> Configuration)

Source:

- `etc/adminhtml/system.xml`

Section:

- Customer Survey

### 3.1 Privacy group

- Store IP Address in Survey Responses (Yes/No)
  - Path: `customersurvey/privacy/store_ip_address`
  - If enabled, IP may be stored in response data.

### 3.2 Analytics and Performance group

- Enable Analytics Cache (Yes/No)
  - Path: `customersurvey/analytics/enable_cache`

- Cache TTL (seconds)
  - Path: `customersurvey/analytics/cache_ttl`
  - Used for report query caching.

- Aggregation Window (days)
  - Path: `customersurvey/analytics/aggregation_days`
  - Number of recent days re-aggregated by cron.

- Raw Analytics Retention (days)
  - Path: `customersurvey/analytics/retention_days`
  - Purge retention for raw event and response data.

- Summary Retention (days)
  - Path: `customersurvey/analytics/summary_retention_days`
  - Purge retention for summary tables.

---

## 4) Question Bank

List page source:

- `view/adminhtml/templates/question/index.phtml`

Edit page source:

- `view/adminhtml/templates/question/edit.phtml`

Save logic:

- `Controller/Adminhtml/Question/Save.php`

### 4.1 Question list page features

- Add New Question
- Filters:
  - Search (title/description)
  - Type
  - Status
  - Per-page limit
- Mass actions:
  - Delete
  - Enable
  - Disable
- Row actions:
  - Edit
  - Delete

### 4.2 Question form fields and usage

#### Question Details panel

- Title (required)
  - Main question text shown to customer.

- Type (required)
  - Determines input style and whether options are needed.
  - Available values from `Model/Question.php`:
    - text
    - textarea
    - radio
    - checkbox
    - dropdown
    - rating
    - yes_no
    - image_selection

- Is Required (Yes/No)
  - If Yes, customer must answer to continue for that question.

- Status
  - Enables/disables question for survey usage.

- Sort Order
  - Controls default ordering behavior where used.

- Min Selection / Max Selection
  - Used only for checkbox type.
  - Validation: cannot be negative, min cannot exceed max.

- Description
  - Optional helper text for the question.

#### Question Options panel

Shown for option-based types:

- radio
- checkbox
- dropdown
- image_selection

Option row fields:

- Title
- Value
- Image (optional)
- Sort Order
- Remove action

Validation rules:

- For option-based types, at least one option must exist.
- Each non-empty option row must have both title and value.
- For image_selection, if Value is empty and Title exists, Title is used as Value.

Image handling:

- Upload path under media: `customersurvey/options/*`

Note:

- Current save controller contains debug logging into:
  - `var/log/customersurvey_upload_debug.log`

---

## 5) Locations

UI sources:

- `view/adminhtml/ui_component/customersurvey_location_listing.xml`
- `view/adminhtml/ui_component/customersurvey_location_form.xml`

Save logic:

- `Controller/Adminhtml/Location/Save.php`

### 5.1 Location list page features

- Add New Location
- Grid columns:
  - ID
  - Name
  - Page Type
  - CSS Selector
  - Position
  - Status
  - Updated At
- Mass actions:
  - Delete
  - Enable
  - Disable

### 5.2 Location form fields and usage

- Name (required)
  - Admin label for identifying placement target.

- Description
  - Internal notes.

- Page Type (required)
  - Values from `Model/Source/PageType.php`:
    - Product
    - Category
    - CMS
    - Cart
    - Checkout
    - Success
    - Customer
    - Custom URL

- URL Pattern
  - Required when Page Type = Custom URL.
  - Example: `/golf/*`

- CSS Selector (required)
  - DOM anchor where survey is inserted.
  - Example: `#maincontent .product-info-main`
  - Validation enforces allowed CSS selector characters.

- Position (required)
  - Values from `Model/Source/Position.php`:
    - Before Selector
    - After Selector
    - Inside Selector
    - Replace Selector

- Screenshot / Image
  - Admin reference image for this location.
  - Uploaded through `customersurvey/location/upload`.

- Status (Yes/No)
  - Enables/disables location.

---

## 6) Survey Journeys

UI sources:

- `view/adminhtml/ui_component/customersurvey_survey_listing.xml`
- `view/adminhtml/ui_component/customersurvey_survey_form.xml`

Save logic:

- `Controller/Adminhtml/Survey/Save.php`

### 6.1 Survey Journey list page features

- Add New Survey Journey
- Grid columns:
  - ID
  - Name
  - Status
  - Priority
  - Timeline
  - Number of Questions
  - Number of Locations
  - Updated At
- Mass actions:
  - Delete
  - Set Draft
  - Set Active
  - Set Disabled

### 6.2 Survey form tabs/fieldsets and all fields

#### A) Basic Information

- Name (required)
  - Survey title shown in frontend widget.

- Description
  - Optional survey subtitle/description.

- Status (required)
  - Draft, Active, Disabled.

- Store Views
  - Empty means all store views.

- Priority
  - Higher priority appears first during eligibility processing.

- Sort Order
  - Secondary ordering.

- Start Date / End Date
  - Optional date range for eligibility.

#### B) Questions

- Select Questions
  - Multi-select from Question Bank.

- Question Mappings (question_id:order)
  - Optional explicit question sequence.
  - Format: one mapping per line, example `12:1`.

Behavior:

- Save clears and recreates survey-question mappings.
- If order map exists, it is used; otherwise default incremental order.

#### C) Locations

- Select Locations
  - Multi-select from saved locations.

- Selected location screenshots preview
  - Visual verification area in admin.

Behavior:

- Save clears and recreates survey-location mappings.

#### D) Display Settings

- Display Type
  - Inline or Popup.

- Popup Triggers (popup only)
  - Options from `Model/Source/SurveyPopupTrigger.php`:
    - On page load
    - After X seconds
    - After X page views
    - On login
    - On logout
    - On add to cart
    - On cart page
    - On checkout page
    - On order success
    - Exit intent

- Popup Delay (seconds)
  - Used with trigger: after_seconds.

- Popup After Page Views
  - Used with trigger: after_page_views.

Notes:

- For inline display, popup trigger fields are hidden in UI.
- Inline placement location comes from selected Locations and their selector+position.

#### E) Frequency and Limits

- If Not Answered
  - Show every eligible visit
  - Show once and never show again
  - Show again after X days

- Not Answered: Show Again After X Days
  - Used only when If Not Answered = after_days.

- If Answered
  - Never show again
  - Show again after X days

- Answered: Show Again After X Days
  - Used only when If Answered = after_days.

- Maximum Impressions
- Daily Limit
- Weekly Limit
- Monthly Limit
- Maximum Response Count

All limit fields are nullable integers. Empty means no limit for that rule.

---

## 7) Reports and Analytics

Sources:

- `view/adminhtml/templates/report/index.phtml`
- `view/adminhtml/templates/report/view.phtml`
- `Model/Analytics/ReportService.php`

### 7.1 Report index page

Features:

- Date filter: From / To
- Survey filter
- KPI cards:
  - Impressions
  - Opens
  - Responses
  - Completion Rate
- Survey performance table
- Trend and distribution charts
- Export buttons:
  - CSV
  - Excel

### 7.2 Report detail page

Features:

- Survey-level KPIs
- Daily trend chart
- Question wise answered vs not answered charts
- Answer distribution table
- User-wise responses
- Optional answer visibility toggle
- Device details popup in row

### 7.3 Metric definitions (current)

- Impression
  - Survey render event.

- Open
  - Popup open event.
  - For inline surveys, open is tracked when survey stays visible in viewport for 3 seconds.

- Response
  - Counted when first valid answer is tracked and through response persistence.
  - Current frontend also tracks response intent at first non-empty answer.

- Completion Rate
  - Formula: `(responses * 100) / impressions`

---

## 8) Frontend Behavior

Frontend bootstrap:

- `view/frontend/templates/bootstrap.phtml`
- `Block/Frontend/Bootstrap.php`

Main renderer:

- `view/frontend/web/js/survey-renderer.js`

### 8.1 Rendering flow

1. Build visitor context and request eligible surveys.
2. Pick survey and matching location.
3. Render as inline or popup by display_type.

### 8.2 Tracking flow

- Impression:
  - Posted on survey render.

- Popup open:
  - Popup mode: when popup actually opens from trigger.
  - Inline mode: when inline survey is visible in viewport for 3 seconds.

- Answered:
  - Posted once on first non-empty answer input.

- Submit:
  - Answers are persisted while progressing and on final submit.

- Closed:
  - Posted when popup is closed.

### 8.3 Option images

- If option has image, frontend renders the image with option title.
- Uses media base URL from bootstrap config.

---

## 9) How To Add Everything (End-to-End)

### Step 1: Add questions

1. Go to Customer Surveys -> Question Bank.
2. Click Add New Question.
3. Fill Title, Type, Required, Status.
4. For checkbox type, set Min/Max selection.
5. For option-based types, add options rows.
6. Add option images if needed.
7. Save.

### Step 2: Add locations

1. Go to Customer Surveys -> Locations.
2. Click Add New Location.
3. Fill Name and Page Type.
4. Add CSS Selector and Position.
5. If page type is Custom URL, set URL Pattern.
6. Upload screenshot (optional, recommended).
7. Save.

### Step 3: Create survey journey

1. Go to Customer Surveys -> Survey Journeys.
2. Click Add New Survey Journey.
3. Fill Basic Information.
4. In Questions fieldset, select questions and optional order map.
5. In Locations fieldset, select one or more locations and verify screenshots.
6. In Display Settings:
   - Choose Inline or Popup.
   - If Popup, select triggers and set delay/page views where needed.
7. In Frequency and Limits, define repeat rules and caps.
8. Set status to Active.
9. Save.

### Step 4: Verify on storefront

1. Open a matching page/location.
2. Confirm survey placement and design.
3. For inline surveys, scroll until the survey enters viewport.
4. Confirm open tracking after 3 seconds visible.
5. Answer at least one question and verify response tracking.

### Step 5: Verify analytics

1. Open Customer Surveys -> Reports and Analytics.
2. Filter by date and survey.
3. Validate impressions/opens/responses.
4. Validate completion rate formula against totals.
5. Export CSV/Excel if needed.

---

## 10) Developer Reference (Important Files)

Admin and config:

- `etc/adminhtml/menu.xml`
- `etc/adminhtml/system.xml`
- `etc/acl.xml`

Survey UI:

- `view/adminhtml/ui_component/customersurvey_survey_listing.xml`
- `view/adminhtml/ui_component/customersurvey_survey_form.xml`
- `Controller/Adminhtml/Survey/Save.php`

Location UI:

- `view/adminhtml/ui_component/customersurvey_location_listing.xml`
- `view/adminhtml/ui_component/customersurvey_location_form.xml`
- `Controller/Adminhtml/Location/Save.php`

Question UI:

- `view/adminhtml/templates/question/index.phtml`
- `view/adminhtml/templates/question/edit.phtml`
- `Controller/Adminhtml/Question/Save.php`

Frontend:

- `view/frontend/templates/bootstrap.phtml`
- `Block/Frontend/Bootstrap.php`
- `view/frontend/web/js/survey-renderer.js`

Reports:

- `view/adminhtml/templates/report/index.phtml`
- `view/adminhtml/templates/report/view.phtml`
- `Model/Analytics/ReportService.php`

---

## 11) Practical Notes and Best Practices

- Always test CSS selectors in browser dev tools before saving location.
- Keep question wording short and clear for better completion rates.
- Prefer 1 to 3 questions per popup survey for higher response rates.
- For inline at page bottom (example before footer), open is now visibility-based, not just render-based.
- Keep date range and status consistent when validating reports.
