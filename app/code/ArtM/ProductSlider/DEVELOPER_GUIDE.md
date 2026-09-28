# ArtM_ProductSlider — Developer Guide

Internal architecture/reference doc. `README.md` covers integration (widgets, shortcodes, layout XML); this file covers how the module actually works internally, the full data model, and known quirks — read this before changing templates, JS, or controllers.

## 1. Identity

- Module: `ArtM_ProductSlider`, composer `artm/module-product-slider`, PHP `^8.2`.
- Depends on `magento/module-backend`, `magento/module-ui`, `magento/module-config`.
- `etc/di.xml`: preferences wire `SliderRepositoryInterface → SliderRepository`, `SliderGroupRepositoryInterface → SliderGroupRepository` (+ search results). Plugin `Plugin\Cms\TemplateFilterPlugin` on `Magento\Cms\Model\Template\Filter` rewrites `{{product_slider ...}}` / `{{product_slider_group ...}}` shortcodes into native `{{block ...}}` directives. It also contains a **site-specific patch**: strips any CMS block reference to `template="Magento_Theme::html/test/slider_test.phtml"` (a broken/nonexistent template elsewhere on the site) and retries — not a generic module feature, just a workaround for bad content.
- `etc/widget.xml`: registers `artm_product_slider` (Block\Slider) and `artm_product_slider_group` (Block\Group) as Page Builder/CMS widgets.
- `Controller/Ajax/Products/Interceptor.php`, `Controller/Ajax/GroupTab/Interceptor.php`, `Controller/Index/View/Interceptor.php`, `Block/Slider/Interceptor.php` are **hand-written compatibility shims**, not generated cruft — kept in source control deliberately for environments where DI-compiled interceptors are unavailable. Don't delete them assuming they're stray generated files.

## 2. Architecture: Group ↔ Slider ↔ Products

A **Slider** is a fully self-contained entity (own product source, display config, card config, date/store/customer-group scoping). It can render standalone, or be embedded as a tab inside a **Group**.

- The real Group→Slider relationship is a JSON blob: `artm_product_slider_group.tabs_json` = `{"settings": {...}, "tabs": [{slider_id, tab_name, view_all_override, manual_url, sort_order, is_default}, ...]}`. Built/normalized by `Controller\Adminhtml\Group\Save`, read by `Model\Frontend\GroupDataProvider::getGroup()` (resolves each `slider_id` → identifier, drops disabled/missing sliders, sorts, guarantees exactly one `is_default`).
- **Dead infrastructure (do not build on these expecting them to be "the" relationship):**
  - `artm_product_slider_group_items` table + `SliderGroupItem` model/API/resource/collection — fully implemented, never read or written anywhere.
  - `artm_product_slider_products` table + `SliderProduct` model/API/resource/collection — fully implemented, never read or written. Slider↔product membership is always resolved **live** from CSV columns via `Model\ProductEngine`, never from this join table.
  - The legacy singular `Slider.group_id` DB column still exists and is set by `Controller\Adminhtml\Slider\Save`, but the admin form's multi-select `slider[group_ids][]` only persists the **first** selected group — assigning one slider to multiple groups via that control does not actually work.

## 3. Database schema (`etc/db_schema.xml`)

### `artm_product_slider_group`
`group_id` (PK), `name`, `identifier` (unique), `description`, `sort_order`, `tabs_json`, `status`, `created_at`, `updated_at`.

### `artm_product_slider` (~65 columns)
- **Identity/status:** `slider_id`, `group_id` (nullable FK, `ON DELETE SET NULL`), `title`, `identifier` (unique), `status`
- **Visibility:** `store_view_ids`, `customer_group_ids` (CSV), `from_date`, `to_date`, `show_desktop`, `show_tablet`, `show_mobile`
- **Content/CTA:** `heading`, `sub_heading`, `content_description`, `button_text`, `button_link`, `button_link_type` (`manual`|`category`)
- **Product source:** `source_type` (`category`|`products`|`mixed`), `source_value` (usually unused — see §7.6 fallback mechanism), `category_ids`, `allow_multiple_categories`, `include_child_categories`, `exclude_product_ids`, `source_product_ids`, `mixed_category_ids`, `mixed_product_ids`, `mixed_excluded_product_ids`, `mixed_priority` (`category_then_manual`|`manual_then_category`)
- **Limits:** `max_products_desktop` (12), `max_products_tablet` (8), `max_products_mobile` (4), `product_limit` (12)
- **Sorting:** `sort_by`, `sort_direction`, `sorting_type`, `manual_sort_product_ids`, `custom_sort_attribute`
- **Display/layout:** `slides_to_show` (**not editable in admin UI — see §7.7**), `display_type` (`slider`|`grid`), `desktop_items`/`tablet_items`/`mobile_items` (4/2/1), `space_between` (16), `grid_desktop_columns`/`grid_tablet_columns`/`grid_mobile_columns` (4/2/1)
- **Mechanics:** `show_arrows`, `show_dots`, `infinite_loop`, `autoplay`, `autoplay_delay` (3000), `swipe`, `mouse_drag`, `touch_support`, `lazy_load`, `animation_speed` (300), `autoplay_speed` (3000 — **redundant with `autoplay_delay`, see §7.8**)
- **Card rendering:** `card_components` (CSV, order-significant), `custom_attributes` (CSV of attribute codes), `hover_image_enabled`, `image_role`, `show_name`, `show_price`, `show_add_to_cart`
- **Misc:** `preview_notes`, `sort_order`, `created_at`, `updated_at`

### `artm_product_slider_products` (dead — see §2)
`item_id`, `slider_id` (FK CASCADE), `product_id` (FK CASCADE), `position`. Unique `(slider_id, product_id)`.

### `artm_product_slider_group_items` (dead — see §2)
`item_id`, `group_id` (FK CASCADE), `slider_id` (FK CASCADE), `tab_title`, `position`, `status`. Unique `(group_id, slider_id)`.

## 4. API coverage gap

`Api\Data\SliderInterface` only defines typed getters/setters for ~18 of the ~65 `artm_product_slider` columns: `SLIDER_ID`, `GROUP_ID`, `TITLE`, `IDENTIFIER`, `STATUS`, `SOURCE_TYPE`, `SOURCE_VALUE`, `SORT_BY`, `SORT_DIRECTION`, `PRODUCT_LIMIT`, `SLIDES_TO_SHOW`, `AUTOPLAY`, `AUTOPLAY_SPEED`, `SHOW_NAME`, `SHOW_PRICE`, `SHOW_ADD_TO_CART`, `PREVIEW_NOTES`, `SORT_ORDER`, `CREATED_AT`, `UPDATED_AT`. Every other field (`heading`, `display_type`, `card_components`, all the `mixed_*`/`grid_*`/`*_items` columns, etc.) is accessed everywhere via `getData('field_name')` / raw array access — no compile-time safety, watch for typos.

`Api\Data\SliderGroupInterface` has full parity with all 9 group columns.

## 5. Source/option classes (`Model/Source/*`)

| Class | Options |
|---|---|
| `Status` | `1` Enabled / `0` Disabled |
| `YesNo` | `'1'` Yes / `'0'` No |
| `ProductSourceType` | `category`, `products`, `mixed` |
| `ButtonLinkType` | `manual`, `category` |
| `DisplayType` | `slider`, `grid` |
| `SortBy` | `entity_id`, `created_at`, `name`, `price`, `position` |
| `SortDirection` | `ASC`, `DESC` |
| `SortingType` | `manual`, `category_position`, `newest`, `price_asc`, `price_desc`, `name_asc`, `name_desc`, `sku`, `random`, `custom_attribute` |
| `SlidesToShow` | `'1'`…`'6'`, `'8'` — **field hidden in admin UI, see §7.7** |
| `ImageRole` | `image`, `small_image`, `thumbnail`, `category_image` |
| `CardComponents` | `image`, `hover_image`, `product_name`, `short_description`, `sku`, `price`, `special_price`, `discount_percent`, `rating`, `review_count`, `stock_status`, `add_to_cart`, `wishlist`, `compare`, `custom_attributes` — **`special_price`/`discount_percent` have no render branch in `cards.phtml`, see §7.9**; `hover_image` only renders nested inside `image`, not standalone |
| `SliderGroups` | DB-backed list of groups ordered by name |

## 6. Admin: two parallel implementations — only one is live

The admin UI you actually see is **hand-rolled `.phtml` + vanilla JS**, driven by plain `Magento\Backend\Block\Template` blocks (`Block\Adminhtml\{Slider,Group}\{Edit,Index}`) and layout XML that places them directly — no `uiComponent` references anywhere.

The full set of `Magento_Ui` component definitions also present in the module — `view/adminhtml/ui_component/artm_productslider_slider_form.xml`, `_group_form.xml`, `_slider_listing.xml`, `_slider_group_listing.xml`, their `FormDataProvider`/`ListingDataProvider` classes, `SliderActions`/`SliderGroupActions` columns, and the `Block\Adminhtml\*\Edit\*Button` classes — are **fully implemented but orphaned**: nothing references these ui_component names from any layout. Treat them as dead/superseded code, not the real admin surface.

Real admin behavior lives in:
- `slider/edit.phtml` / `group/edit.phtml` — plain forms posting to `*/*/save`, inline `<script>` for conditional field show/hide (`data-dep`), live "Preview" panel (AJAX → `PreviewProducts`), product picker with drag-drop reorder (AJAX → `ProductSearch`), group tab drag-drop editor.
- `slider/index.phtml` / `group/index.phtml` — plain `<table>` grids, GET filters, POST mass-actions, shared "Embed" modal generating shortcode/PHTML snippets.

## 7. Controllers

**Adminhtml/Slider:** `Index` (grid), `Edit`/`NewAction` (form), `Save` (normalizes CSV/int fields, resolves `group_ids[]`→`group_id`, auto-identifier from title, dynamic fallback storage — see §7.6), `Delete`, `Duplicate` (clone + " (Copy)" title/`-copy-<time>` identifier), `MassEnable`/`MassDisable`/`MassDelete`, `PreviewProducts` (AJAX, admin preview tab), `ProductSearch` (AJAX, product picker grid).

**Adminhtml/Group:** `Index`, `Edit`/`NewAction`, `Save` (builds `tabs_json` from posted `settings`+`tabs[]`, heals malformed rows, validates `view_all_override`, ensures one `is_default`), `Delete`, `MassEnable`/`MassDisable`/`MassDelete`.

**Adminhtml/Settings:** `Index` — just redirects to core `adminhtml/system_config/edit?section=artm_productslider`; no custom UI.

**Ajax (public, frontend):**
- `Products` (`artm_productslider/ajax/products`) — params `identifier`, `page`, `page_size` (cap 48). Returns `{success, data:{html, page, page_size, total, has_more}}`, `html` = server-rendered `slider/cards.phtml`. `Cache-Control: public, max-age=300, s-maxage=900`. Used by `slider.js` for lazy pagination.
- `GroupTab` (`artm_productslider/ajax/grouptab`) — params `identifier`/`group_id`, `tab_index`. Returns `{success, data:{tab_index, html}}`, `html` = nested `Block\Slider` render for that tab. Used by `group-tabs.js` for lazy tab activation.

**Index/View** (`artm_productslider/index/view`) — generic standalone page rendering `Block\Slider` directly; resolves identifier from query params, including bare-token fallback (`?some-identifier`).

### 7.6 Schema-drift fallback (Slider\Save)
`Save.php` calls `describeTable()` on every save; any posted field that isn't a real DB column gets stashed as JSON inside `source_value` under an `__artm_slider_fallback` key, with an admin notice to run `setup:upgrade`. `Block\Adminhtml\Slider\Edit::mergeFallbackValues()` reads it back to repopulate the form. So `source_value` may hold either legit product-source data (rare — not surfaced in the real admin form) or this fallback JSON, depending on environment DB state.

### 7.7 `slides_to_show` is dead in the UI
Defined in schema/interface/model/source/orphaned-ui-component, but **commented out** in the live `slider/edit.phtml` (~line 168-171). Cannot be edited through admin; stays at its saved/default value (`'4'`).

### 7.8 `autoplay_delay` vs `autoplay_speed`
Two separate columns/admin fields, both default 3000ms, both admin-editable, both included in `SliderRenderer::getJsConfig()`. Only `autoplayDelay` actually drives `slider.js`'s autoplay interval; `autoplay_speed` appears to have no distinct runtime effect (`animation_speed`/`animationSpeed` drives transition duration instead). Likely redundant/incompletely wired — don't assume changing `autoplay_speed` does anything on the frontend.

### 7.9 `special_price` / `discount_percent` card components
Selectable in admin's card-components list, but `cards.phtml` has no `if/elseif` branch for them — no visible effect. Discount is shown only inline as part of the `price` component.

## 8. Frontend rendering flow (end to end)

1. **Entry points:** layout XML block placement, CMS shortcode (rewritten by `TemplateFilterPlugin`), Magento Widget, or `artm_productslider/index/view` controller.
2. `Block\Slider` resolves `identifier` (block data → request param → id lookup → bare-token query fallback). Implements `IdentityInterface` (FPC tag `ARTM_PRODUCTSLIDER_<id>`), `getCacheLifetime()` = 3600s, or uncacheable when `?ps_debug=1`/`?debug=1`.
3. `Model\Frontend\SliderDataProvider::getSliderByIdentifier()` loads by identifier+status=1, filters by `isVisibleForCurrentContext()` (store, customer group via `HttpContext`, date range via `TimezoneInterface`).
4. `Block\Slider::getInitialProductsPayload()` → `SliderRenderer::getInitialPageSize()` (page size = `product_limit`/`max_products_desktop` for grid, `desktop_items` for slider) → `SliderDataProvider::getProductPage()` → `Model\ProductEngine::resolvePageIds()`.
5. `ProductEngine::resolveProductIds()` branches on `source_type`:
   - `category` — products in `category_ids` (+ children if `include_child_categories`) minus `exclude_product_ids`
   - `products` — `source_product_ids` verbatim
   - `mixed` — union of `mixed_category_ids` products + `mixed_product_ids`, ordered by `mixed_priority`, minus exclusions
   Then `filterDisplayableIds()` (enabled/visible/store) and `sortIds()` per `sorting_type`.
6. `Block\Slider::getCardsHtml()` renders `slider/cards.phtml` server-side with `items`, `slider`, `renderer` (`ViewModel\SliderRenderer`), `card_view_model` (`ViewModel\ProductCard`) — used both for initial embed and for the AJAX lazy-load response.
7. `slider/view.phtml` outputs `.artm-productslider` with `data-mage-init` config from `SliderRenderer::getJsConfig()`.
8. `slider.js` (AMD, **100% custom vanilla-JS carousel — no jQuery/Slick/Swiper**) reads config, computes items-per-breakpoint via `matchMedia`, does CSS-grid layout (`grid`) or `transform: translateX()` sliding (`slider`), drag/swipe, keyboard nav, autoplay, dots, and AJAX infinite pagination via `Ajax\Products` (client-side page cache in `state.cache`). Contains a fully-built debug/event-log subsystem that is permanently dead (`debugEnabled` hardcoded `false`, never wired to any config/URL param — not the same thing as the functional `?ps_debug=1` cache-bypass switch on `Block\Slider`).
9. **Group flow:** `Block\Group::getGroupData()` → `GroupDataProvider::getGroup()` → `group/view.phtml` renders all tab buttons but eagerly renders only the default tab's slider HTML (via `Block\Group::getTabHtml()`); other panes stay empty/`hidden` with `data-loaded="0"`.
10. `group-tabs.js` (jQuery + `mage/apply/main`) binds tab clicks, lazy-loads non-default panes via `Ajax\GroupTab`, caches per-tab HTML, and calls `mageApply()` + triggers `contentUpdated` so the injected slider's own `data-mage-init` actually binds.

## 9. `slider/view.phtml` — current state (⚠ needs cleanup)

**Lines ~6-27 unconditionally dump ObjectManager-sourced timezone debug HTML on every render** — direct `\Magento\Framework\App\ObjectManager::getInstance()` call (violates this module's own "No ObjectManager usage" standard, see README §Standards) and raw unescaped `echo` of a `<h3>Timezone Information</h3>` diagnostics block **before any real slider markup**, on every page that renders a slider or group (group panes recursively render Slider blocks, so this leaks into group pages too). This is leftover debugging code, not a feature — remove it before shipping any other changes to this file.

After that debug block, real logic:
- `$slider = $block->getSliderData()`; if null, tries a **Group fallback**: reads `identifier` request param, renders `Block\Group` instead (so one URL works for either a slider or a group identifier) — see lines ~47-61.
- If neither resolves: client-side script rewriting bare-token query strings (`?some-identifier` → `?identifier=some-identifier`) plus a `.artm-productslider-debug--warn` "Slider Group Not Rendered" message.
- Main container: `.artm-productslider.artm-productslider--{slider|grid}` with device-visibility class, `data-role`, `data-display-type`, `data-total`, `data-has-more`, `data-page-size`, `data-mage-init`.
- Header (`h3.artm-productslider__title`, optional `p.artm-productslider__subtitle`).
- Prev/next nav buttons — only when `display_type === 'slider'`.
- `.artm-productslider__viewport > .artm-productslider__track[data-role=track]` — holds server-rendered card HTML from `getCardsHtml()`.
- Dots container — only when `display_type === 'slider'` (populated client-side).

## 10. `slider/cards.phtml`

Loops `$items` (product collection), skips items without an id. Per product computes URL, name, prices/discount (`ViewModel\ProductCard`), review summary (direct SQL on `review_entity_summary`), filtered `custom_attributes` (only non-empty, non-"No" values), sanitized `short_description`, image/hover-image URLs (role = slider's `image_role`).

Renders `<article class="artm-productcard" data-product-id>` and **iterates `card_components` in the exact stored order** (default `['image','product_name','price','add_to_cart']` if empty), one `if/elseif` branch per key: `image` (+ nested `hover_image` if selected), `product_name`, `short_description`, `sku`, `price`, `rating`/`review_count` (only if >0), `stock_status`, `wishlist`/`compare` (`data-post` JSON buttons, consumed by `slider.js`), `add_to_cart` (real POST form to `checkout/cart/add`), `custom_attributes`. No branch for `special_price`/`discount_percent` (§7.9).

## 11. `group/view.phtml`

Returns early if `getGroupData()` is null. Outer `.artm-productslider-group` with device-visibility class, `data-group-identifier`, `data-group-id`, `data-mage-init` for `group-tabs.js`. Optional title/subtitle from `settings.heading`/`settings.sub_heading` (fallback to group name). Tab nav (`role=tablist`, one button per tab, label = `tab_name` or "Tab N"). Panes: one `section[data-tab-index][data-loaded][data-slider-identifier]` per tab — only default starts visible/populated. Each pane also renders a "View All" link per `getTabViewAll()` (`view_all_override`: `hide` / `manual_url` / `use_slider_button` [default — uses that tab's own slider's `button_link`/`button_text`]), plus a hidden loading spinner.

## 12. CSS

- `view/frontend/web/css/productslider.css` — `--ps-*` custom properties scoped to `.artm-productslider`; CSS grid by default, flex+transform for `--slider` variant; breakpoints at 1024px/767px; `--ps-tablet-items`/`--ps-mobile-items` set inline by JS; `.artm-ps-hide-desktop/tablet/mobile` utility classes for device visibility. Also styles `.artm-productcard*`, `.artm-productslider-debug*` (used by both the leftover timezone dump and the dead JS debug panel), `.artm-productslider-group*`.
- `view/adminhtml/web/css/productslider-admin.css` — cosmetic skin for the hand-rolled admin pages only.

## 13. File map for frontend template work

| Concern | File |
|---|---|
| Slider markup | `view/frontend/templates/slider/view.phtml` |
| Card markup | `view/frontend/templates/slider/cards.phtml` |
| Group markup | `view/frontend/templates/group/view.phtml` |
| Slider JS | `view/frontend/web/js/slider.js` |
| Group tabs JS | `view/frontend/web/js/group-tabs.js` |
| CSS | `view/frontend/web/css/productslider.css` |
| View models | `ViewModel/SliderRenderer.php`, `ViewModel/ProductCard.php` |
| Blocks | `Block/Slider.php`, `Block/Group.php` |
| Data resolution | `Model/Frontend/SliderDataProvider.php`, `Model/Frontend/GroupDataProvider.php`, `Model/ProductEngine.php` |
| Layout | `view/frontend/layout/default.xml` (CSS include), `view/frontend/layout/artm_productslider_index_view.xml` |

## 14. Other quirks worth knowing

- `etc/adminhtml/menu.xml` nests the "Product Sliders" admin menu under **`ArtM_BlogEsg::BlogEsgmenu`** (a different module's menu) with no declared dependency on that module — if `ArtM_BlogEsg` is absent/disabled, this menu item may fail to render.
- Two different conventions for CSV DB fields in the admin form: some (`card_components`, `store_view_ids`, `customer_group_ids`, `group_ids`) use real `<select multiple>`; others (`category_ids`, `exclude_product_ids`, `source_product_ids`, `mixed_*`) are free-text/JS-picker-managed comma-separated inputs.
