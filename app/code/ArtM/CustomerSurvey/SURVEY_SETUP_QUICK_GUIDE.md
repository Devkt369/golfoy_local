# CustomerSurvey Quick Setup and Field Reference

This guide now includes all major admin fields and exactly what each field does.

For long-form architecture details, see:

- DOCUMENTATION.md

## A) Fast Setup Checklist

1. Create questions in Question Bank.
2. Create one or more Locations with valid CSS selector and correct page type.
3. Create Survey Journey and map Questions + Locations.
4. Set Display Type:
   - Inline for embedded block
   - Popup for trigger-based modal
5. Configure Frequency and Limits.
6. Set Survey status to Active.
7. Test storefront behavior and validate reports.

## B) Menu Wise Purpose

- Question Bank
  - Create and manage reusable questions and options.

- Locations
  - Define where survey appears using selector + position.

- Survey Journeys
  - Build complete survey flow by mapping questions and locations.

- Reports and Analytics
  - Track impressions, opens, responses, completion rate, and exports.

## C) Question Bank - All Fields

### C1) Question list page

- Search
  - Filters by title/description.

- Type filter
  - Filter by question type.

- Status filter
  - Enabled/Disabled filter.

- Limit
  - Rows per page.

- Mass Action
  - Delete / Enable / Disable selected questions.

### C2) Question form fields

- Title (required)
  - Customer-visible question text.

- Type (required)
  - Input style and validation behavior.
  - Available types:
    - text: one-line free text
    - textarea: multi-line free text
    - radio: single select from options
    - checkbox: multi-select from options
    - dropdown: single select from dropdown
    - rating: star rating input
    - yes_no: yes/no choice
    - image_selection: select from option images

- Is Required
  - If Yes, question must be answered to move forward.

- Status
  - Enabled/Disabled for selection in surveys.

- Sort Order
  - Order hint for display or management.

- Min Selection (checkbox only)
  - Minimum allowed selected options.

- Max Selection (checkbox only)
  - Maximum allowed selected options.

- Description
  - Optional helper text shown under question title.

### C3) Question options panel fields

- Title
  - Option label shown to customer.

- Value
  - Option stored value.

- Image
  - Optional image for option.
  - Most relevant for image_selection type.

- Sort Order
  - Option ordering.

- Remove
  - Deletes option row.

Validation notes:

- Option-based types require at least one option.
- Each non-empty option row must contain Title and Value.
- For image_selection, if Value left blank, Title is used as Value.

## D) Locations - All Fields

### D1) Location list page

- Columns:
  - ID, Name, Page Type, CSS Selector, Position, Status, Updated At
- Mass Action:
  - Delete / Enable / Disable

### D2) Location form fields

- Name (required)
  - Admin label for location.

- Description
  - Internal notes.

- Page Type (required)
  - Determines where location can match.
  - Values:
    - Product
    - Category
    - CMS
    - Cart
    - Checkout
    - Success
    - Customer
    - Custom URL

- URL Pattern
  - Required only when Page Type = Custom URL.
  - Example: `/golf/*`

- CSS Selector (required)
  - DOM selector where survey is inserted.
  - Example: `#maincontent .product-info-main`

- Position (required)
  - How survey is inserted relative to selector.
  - Values:
    - Before Selector
    - After Selector
    - Inside Selector
    - Replace Selector

- Screenshot / Image
  - Admin reference screenshot for verification in Survey form.

- Status
  - Enabled/Disabled location.

## E) Survey Journeys - All Fieldsets and Fields

### E1) Basic Information

- Name (required)
  - Survey title displayed to visitor.

- Description
  - Intro/helper text for survey.

- Status (required)
  - Draft / Active / Disabled.

- Store Views
  - Empty = all store views.

- Priority
  - Higher priority can win when multiple surveys are eligible.

- Sort Order
  - Secondary sorting order.

- Start Date
  - Optional activation start date.

- End Date
  - Optional deactivation end date.

### E2) Questions fieldset

- Select Questions
  - Multi-select question IDs to include.

- Question Mappings (question_id:order per line)
  - Optional explicit order map.
  - Example:
    - `12:1`
    - `45:2`

Usage:

- If mapping is not provided, selected questions get default sequence order.

### E3) Locations fieldset

- Select Locations
  - Multi-select location IDs where survey may render.

- Location screenshot preview
  - Shows selected location images for visual verification.

### E4) Display Settings fieldset

- Display Type
  - Inline or Popup.

- Popup Triggers (for popup mode)
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
  - Used when trigger includes After X seconds.

- Popup After Page Views
  - Used when trigger includes After X page views.

Important behavior:

- Inline mode ignores popup trigger fields.
- Actual inline placement comes from selected Location selector + position.

### E5) Frequency and Limits fieldset

- If Not Answered
  - Show every eligible visit
  - Show once and never show again
  - Show again after X days

- Not Answered: Show Again After X Days
  - Used only when above setting = Show again after X days.

- If Answered
  - Never show again
  - Show again after X days

- Answered: Show Again After X Days
  - Used only when above setting = Show again after X days.

- Maximum Impressions
  - Hard cap for total impressions.

- Daily Limit
  - Max impressions per day.

- Weekly Limit
  - Max impressions per week.

- Monthly Limit
  - Max impressions per month.

- Maximum Response Count
  - Stops survey after response count reaches limit.

## F) Reports and Analytics - Fields and Controls

### F1) Report index page controls

- From Date / To Date
  - Global date filter for all metrics.

- Survey dropdown
  - Select specific survey or All.

- Export CSV / Export Excel
  - Exports report rows.

### F2) Report KPIs

- Impressions
  - Count of survey render events.

- Opens
  - Popup open events.
  - Inline open is tracked after 3 seconds visibility in viewport.

- Responses
  - Increments when first question is answered (response intent), and by persisted response data.

- Completion Rate
  - Formula: `(Responses * 100) / Impressions`

### F3) Detail page controls and tables

- Show Answers toggle
  - Expands response answer details.

- Per Page
  - Paginates user-wise responses.

- Device Details button
  - Displays device/browser/ip details modal.

## G) Store Configuration Fields

Path: Stores -> Configuration -> Customer Survey

### G1) Privacy

- Store IP Address in Survey Responses
  - Yes: IP is stored with response records.
  - No: avoid IP storage.

### G2) Analytics and Performance

- Enable Analytics Cache
  - Enables report data caching.

- Cache TTL (seconds)
  - Cache lifetime for report payloads.

- Aggregation Window (days)
  - Number of recent days cron re-aggregates.

- Raw Analytics Retention (days)
  - Raw event/response cleanup window.

- Summary Retention (days)
  - Daily summary table cleanup window.

## H) Critical Behavior Notes

- Impression increments when survey is rendered.
- Open increments:
  - Popup: when popup opens.
  - Inline: when survey is visible for 3 seconds.
- Response increments when first question is answered, even if full survey is not completed.

## I) Suggested End-to-End Validation

1. Open target page where survey should render.
2. Confirm impression +1.
3. For inline: keep survey visible for at least 3 seconds.
4. Confirm open +1.
5. Answer only one question.
6. Confirm response +1.
7. Validate completion rate in report against totals.
