# ArtM CategoryPage Module Knowledge Base

This document is a full module snapshot intended to avoid re-reading the whole module before future edits.

## 1) Module Identity
- Module name: ArtM_CategoryPage
- Registration file: registration.php
- Setup version: 1.0.0
- Area coverage: Adminhtml + Frontend
- Core feature: Category showcase with grouped category cards, manual cards, manual groups, image uploads, ordering, enable/disable toggles.

## 2) Dependencies and Wiring
- Declared sequence in module.xml:
  - Magento_Backend
  - Magento_Catalog
  - Magento_Config
  - Magento_Store
  - Magento_Ui
- DI preference in etc/di.xml:
  - ArtM\CategoryPage\Api\CategoryProviderInterface -> ArtM\CategoryPage\Model\CategoryProvider

## 3) Routes and Entry Points
### Frontend route
- frontName: categoryshowcase
- route id: categoryshowcase
- file: etc/frontend/routes.xml

### Admin route
- frontName: categorypage
- route id: categorypage
- file: etc/adminhtml/routes.xml

### Admin menu
- Single menu item under Catalog:
  - id: ArtM_CategoryPage::catalog_showcase
  - action: categorypage/category/index
  - file: etc/adminhtml/menu.xml

### ACL resources
- Root: ArtM_CategoryPage::catalog_showcase
- Children:
  - ArtM_CategoryPage::manage_categories
  - ArtM_CategoryPage::manual_categories
  - ArtM_CategoryPage::settings
- file: etc/acl.xml

## 4) DB Schema (Declarative)
Defined in etc/db_schema.xml.

### Table: artm_categorypage_config
- Purpose: per-category showcase config
- Key columns:
  - entity_id (PK)
  - category_id (unique, FK to catalog_category_entity.entity_id)
  - custom_label
  - category_image
  - banner_image
  - banner_link
  - is_enabled
  - sort_order
  - created_at, updated_at
- Indexes:
  - sort_order
  - is_enabled

### Table: artm_categorypage_manual
- Purpose: manual cards inside showcase
- Key columns:
  - entity_id (PK)
  - parent_category_id (FK to catalog_category_entity.entity_id)
  - group_category_id (nullable; supports positive category groups and negative manual group references)
  - title
  - image
  - url
  - sort_order
  - is_enabled
  - created_at, updated_at
- Indexes:
  - (parent_category_id, sort_order)
  - (group_category_id, sort_order)
  - is_enabled

### Table: artm_categorypage_manual_groups
- Purpose: custom groups per main category
- Key columns:
  - entity_id (PK)
  - category_id (FK to catalog_category_entity.entity_id)
  - label
  - sort_order
  - is_enabled
  - created_at, updated_at
- Indexes:
  - (category_id, sort_order)
  - is_enabled

## 5) Configuration (Stores -> Configuration)
Defined in etc/adminhtml/system.xml under section artm_categorypage.

Group: general
- enabled
- show_empty_categories
- enable_banner
- enable_images
- sidebar_width
- default_placeholder_image
- cache_lifetime
- animation_speed

Accessor model: Model/Config.php

## 6) Core Service Layer
## CategoryProviderInterface (Api/CategoryProviderInterface.php)
Methods:
- getMainCategories(?int $storeId = null, bool $includeDisabled = false): array
- getShowcasePayload(int $categoryId, ?int $storeId = null, bool $includeDisabled = false): array
- clearCacheByCategoryId(int $categoryId): void

## CategoryProvider (Model/CategoryProvider.php)
Responsibilities:
- Build sidebar categories
- Build full showcase payload with groups/cards
- Merge manual cards and manual groups
- Resolve labels/images/links
- Cache payloads and sidebar data

Caching:
- Prefix: artm_categorypage_
- Tag: ARTM_CATEGORYPAGE
- Sidebar cache id shape: main_{storeId}_{includeDisabled}
- Payload cache id shape: payload_{storeId}_{categoryId}_{includeDisabled}

Image resolution behavior (current):
- For any configured image key:
  - use configured path if present
  - else use category image if present
- For category_image only:
  - fallback to first product image in that category
- Final fallback:
  - configured placeholder image

Important payload fields:
- category_id
- config
- label
- banner
- banner_link
- image
- groups[]

Group composition logic:
- Manual groups from artm_categorypage_manual_groups -> heading_category_id is negative of manual group id
- Category-based groups from 2nd-level categories under selected main category
- Group items include:
  - 3rd-level category cards
  - manual cards attached by group_category_id

Notable code details:
- banner_link currently falls back to category URL generated from base link + url_path + .html
- There is a commented debug line near payload return
- There are numeric comment lines in getFirstProductImageForCategory method (leftover debug comments)

## ConfigRepository (Model/ConfigRepository.php)
Responsibilities:
- Read/write category config rows
- Read/write manual cards
- Read/write manual groups
- Save sort orders (config/manual scope)
- Normalize DB rows
- Cache invalidation by tag/category

Key validations:
- saveCategoryConfig: category_id required
- saveManualCategory: parent_category_id required, title required
- saveSortOrders: scope must be config or manual

Manual group mapping rule:
- deleteManualGroup deletes manual cards where group_category_id = -{groupEntityId}

## ImageUploader (Model/ImageUploader.php)
Responsibilities:
- Validate upload input
- Save image under media/artm/categorypage/{subdir}
- Return relative path and absolute media URL

Allowed extensions:
- jpg, jpeg, png, gif, webp, svg

## 7) Blocks and Page Config JSON
## Block/Admin/Page.php
Provides admin page config JSON:
- mode
- storeId
- baseAdminUrl
- loadUrl
- saveConfigUrl
- saveManualUrl
- deleteManualGroupUrl
- saveSortUrl
- uploadImageUrl
- settingsUrl
- placeholderImage
- sidebarWidth
- animationSpeed
- initialCategoryId
- mainCategories

## Block/Frontend/Page.php
Provides frontend page config JSON:
- mode = frontend
- storeId
- loadUrl
- placeholderImage
- sidebarWidth
- animationSpeed
- initialCategoryId
- mainCategories

## 8) Controllers
## Adminhtml page controllers
- Controller/Adminhtml/Category/Index.php
  - ACL: manage_categories
  - title/menu: Category Showcase
- Controller/Adminhtml/Index/Index.php
  - ACL: catalog_showcase
- Controller/Adminhtml/Manual/Index.php
  - ACL: manual_categories
  - title/menu: Manual Categories
- Controller/Adminhtml/Settings/Index.php
  - ACL: settings

## Adminhtml AJAX controllers
All return JSON.
- Adminhtml/Ajax/Load.php
  - includeDisabled = true
- Adminhtml/Ajax/SaveConfig.php
  - saves category config and clears cache by category
- Adminhtml/Ajax/SaveManual.php
  - supports two paths:
    - is_manual_group=1 -> saveManualGroup
    - otherwise saveManualCategory
- Adminhtml/Ajax/SaveSort.php
  - calls saveSortOrders(scope, items)
- Adminhtml/Ajax/UploadImage.php
  - uploads image by field/folder
- Adminhtml/Ajax/DeleteManualGroup.php
  - deletes group and related manual cards

## Frontend AJAX controllers
- Ajax/Load.php
  - includeDisabled = false
- Ajax/SaveConfig.php, Ajax/SaveManual.php, Ajax/SaveSort.php, Ajax/UploadImage.php
  - backend-action based classes exposed under frontend route
  - primarily mirror admin write logic

## 9) Layout and Templates
## Admin layouts
- view/adminhtml/layout/categorypage_category_index.xml
  - includes admin-category-page.js
  - renders admin/page.phtml with mode=manage
- view/adminhtml/layout/categorypage_index_index.xml
  - mode=overview
- view/adminhtml/layout/categorypage_manual_index.xml
  - mode=manual
- view/adminhtml/layout/categorypage_settings_index.xml
  - mode=settings (still uses admin/page.phtml)

## Admin templates
- view/adminhtml/templates/admin/page.phtml
  - full admin shell + CSS + modals
  - embeds page config JSON
  - requires ArtM_CategoryPage/js/admin-category-page
- view/adminhtml/templates/admin/settings.phtml
  - simple static settings helper panel
  - appears not used by current layout (settings layout points to admin/page.phtml)

## Frontend layout
- view/frontend/layout/default.xml
  - injects css category-page.css
  - injects header drawer block into header-wrapper

## Frontend template
- view/frontend/templates/frontend/header_drawer.phtml
  - mobile drawer trigger + drawer + overlay
  - includes customer profile sidebar markup
  - initializes category-showcase.js lazily on drawer open
  - forces config.initialCategoryId = 0 in drawer init

## 10) JavaScript Assets
## view/frontend/web/js/category-showcase.js
Current behavior:
- Caches loaded category payloads in categoryCache
- Caches rendered panes in paneCache for instant tab switching
- Tracks pending AJAX per category in pendingRequest
- Sidebar filtered by enabled items
- Sidebar includes hardcoded Brands tile (/brands)
- Main banner link logic:
  - getSidebarCategoryUrl(activeCategoryId) or banner_link or payload url
- Builds group card grids and handles retry on load failure

Notable design points:
- Does not auto-fetch if currentId is 0 unless sidebar has first item
- Active state handled by setActiveSidebar
- Strong client-side caching improves perceived speed

## view/adminhtml/web/js/admin-category-page.js
Current behavior:
- Renders admin sidebar and group/card editor UI
- Supports:
  - save category config
  - save card config (category cards)
  - add/edit manual cards via modal
  - add/edit/delete manual groups
  - toggle visibility for groups/cards
  - upload images for category, group cards, manual cards
  - sort save for config and manual scopes
- Manual card Save Card inline button has been removed from cards; edit button path is used

Important current implementation details:
- Uses many inline HTML strings
- Contains image path handling inconsistency:
  - some places prefix /media/ manually
  - other places use URL as-is
- Contains legacy/backup file in same folder: admin-category-page.js-

## view/adminhtml/web/js/admin-category-page.js-
- Appears to be old backup snapshot
- Contains duplicate functions and older logic
- Should not be loaded by layout, but remains in module tree

## 11) CSS Asset
## view/frontend/web/css/category-page.css
- Contains both drawer and page styles
- Includes many mobile-specific overrides
- Uses high z-index overlay/drawer styles
- Includes theme-specific selectors and account/profile styles

## 12) File-by-File Purpose Index
- registration.php: module registration
- Api/CategoryProviderInterface.php: service contract
- Block/Admin/Page.php: admin page JSON and helpers
- Block/Frontend/Page.php: frontend page JSON and helpers
- Controller/Adminhtml/Ajax/DeleteManualGroup.php: delete manual group JSON endpoint
- Controller/Adminhtml/Ajax/Load.php: admin payload load JSON endpoint
- Controller/Adminhtml/Ajax/SaveConfig.php: admin config save JSON endpoint
- Controller/Adminhtml/Ajax/SaveManual.php: admin manual card/group save JSON endpoint
- Controller/Adminhtml/Ajax/SaveSort.php: admin sort save JSON endpoint
- Controller/Adminhtml/Ajax/UploadImage.php: admin upload JSON endpoint
- Controller/Adminhtml/Category/Index.php: manage page entry
- Controller/Adminhtml/Index/Index.php: overview page entry
- Controller/Adminhtml/Manual/Index.php: manual page entry
- Controller/Adminhtml/Settings/Index.php: settings page entry
- Controller/Ajax/Load.php: frontend payload load JSON endpoint
- Controller/Ajax/SaveConfig.php: frontend config save JSON endpoint
- Controller/Ajax/SaveManual.php: frontend manual save JSON endpoint
- Controller/Ajax/SaveSort.php: frontend sort save JSON endpoint
- Controller/Ajax/UploadImage.php: frontend upload JSON endpoint
- etc/acl.xml: ACL resources
- etc/db_schema.xml: declarative schema
- etc/di.xml: interface preference
- etc/module.xml: module declaration
- etc/adminhtml/menu.xml: admin menu
- etc/adminhtml/routes.xml: admin route
- etc/adminhtml/system.xml: config fields
- etc/frontend/routes.xml: frontend route
- Model/CategoryProvider.php: payload and category tree assembly + cache
- Model/Config.php: config value accessors
- Model/ConfigRepository.php: persistence layer for config/manual/group
- Model/ImageUploader.php: file upload helper
- view/adminhtml/layout/categorypage_category_index.xml: manage layout
- view/adminhtml/layout/categorypage_index_index.xml: overview layout
- view/adminhtml/layout/categorypage_manual_index.xml: manual layout
- view/adminhtml/layout/categorypage_settings_index.xml: settings layout
- view/adminhtml/templates/admin/page.phtml: admin UI shell and modals
- view/adminhtml/templates/admin/settings.phtml: static settings info template
- view/adminhtml/web/js/admin-category-page.js: admin behavior
- view/adminhtml/web/js/admin-category-page.js-: backup/legacy JS snapshot
- view/frontend/layout/default.xml: inject drawer and css
- view/frontend/templates/frontend/header_drawer.phtml: drawer markup and init
- view/frontend/web/css/category-page.css: style sheet
- view/frontend/web/js/category-showcase.js: frontend behavior

## 13) Known Quirks and Risks
- admin-category-page.js includes ad-hoc debug comments and mixed image URL handling.
- admin-category-page.js- backup file can confuse developers; consider deleting or renaming outside web/js.
- settings layout uses admin/page.phtml, while settings.phtml exists unused.
- Frontend route exposes save endpoints implemented with backend action classes.

## 14) Fast Edit Playbook
For future changes, you usually only need these files:
- Data logic: Model/CategoryProvider.php, Model/ConfigRepository.php
- Admin behavior/UI: view/adminhtml/web/js/admin-category-page.js and view/adminhtml/templates/admin/page.phtml
- Frontend behavior/UI: view/frontend/web/js/category-showcase.js, view/frontend/templates/frontend/header_drawer.phtml, view/frontend/web/css/category-page.css
- Endpoint wiring: relevant Controller/*/Ajax/*.php
- Config/schema changes: etc/adminhtml/system.xml, Model/Config.php, etc/db_schema.xml

## 15) Suggested Cleanup Tasks (Optional)
- Remove backup file view/adminhtml/web/js/admin-category-page.js-
- Normalize all image URL creation in admin JS to one approach
- Remove leftover debug comments from CategoryProvider.php
- Decide whether settings page should use settings.phtml or remove it
- Review frontend write endpoints exposure if not needed
