# ArtM_MassProducts

Internal admin module for bulk product intake, staged draft editing, queued product creation, media handling, single-SKU editing, and category reorder workflows.

## Admin entry points

- Module name: `ArtM_MassProducts`
- Admin route/frontName: `massproducts`
- Menu path: `Catalog > Inventory > Mass Upload Product`
- Main page controller: `Controller/Adminhtml/Index/Index.php`
- Ajax tab loader: `Controller/Adminhtml/Index/Tab.php`
- Dashboard block: `Block/Adminhtml/Dashboard/Tabs.php`

## Dashboard structure

Top tabs:

- `products_upload`
- `configuration`

Top-level layout and shell:

- `view/adminhtml/layout/massproducts_index_index.xml`
- `view/adminhtml/templates/dashboard.phtml`

## Products Upload tab map

Parent template:

- `view/adminhtml/templates/tabs/products_upload.phtml`

Child tabs:

### Upload

- Template: `view/adminhtml/templates/tabs/upload.phtml`
- JS: `view/adminhtml/web/js/mapping.js`
- Main routes:
  - `massproducts/upload/save`
  - `massproducts/upload/validate`
  - `massproducts/attributesets/index`
  - `massproducts/attribute/bySet`
  - `massproducts/mapping/save`
  - `massproducts/draft/create`
- Main tables:
  - `artm_intake_uploads`
  - `artm_massupload_mapping`
  - `artm_massproducts_draft`
  - `artm_massproducts_draft_values`
  - `artm_massproducts_column_mapping`

### Re-Sort Products

- Template: `view/adminhtml/templates/tabs/reorder_products.phtml`
- Main routes:
  - `massproducts/categoryreorder/tree`
  - `massproducts/categoryreorder/search`
  - `massproducts/categoryreorder/meta`
  - `massproducts/categoryreorder/download`
  - `massproducts/categoryreorder/uploadvalidate`
  - `massproducts/categoryreorder/confirm`
  - `massproducts/categoryreorder/history`
  - `massproducts/categoryreorder/rollback`
  - `massproducts/categoryreorder/historydownload`
- Main owner: `Model/CategoryReorder/Manager.php`
- Main tables:
  - `artm_category_reorder_history`
  - `artm_category_reorder_item`

### Draft Products

- Template: `view/adminhtml/templates/tabs/draft.phtml`
- JS: `view/adminhtml/web/js/draft-attribute-set-tab-v2.js`
- Main routes:
  - `massproducts/draft/tabMatrix`
  - `massproducts/draft/tabRowSave`
  - `massproducts/draft/massMoveToReady`
  - `massproducts/draft/tabMassDelete`
  - `massproducts/category/tree`
  - `massproducts/media/upload`
  - `massproducts/media/delete`
  - `massproducts/media/updateRoles`
- Main tables:
  - `artm_massproducts_draft`
  - `artm_massproducts_draft_values`
  - `artm_massproducts_draft_media`
  - `artm_massproducts_user_store_pref`

### Ready

- Template: `view/adminhtml/templates/tabs/ready.phtml`
- JS: `view/adminhtml/web/js/ready-attribute-set-tab.js`
- Main routes:
  - `massproducts/ready/tabMatrix`
  - `massproducts/ready/moveToDraft`
  - `massproducts/ready/moveToQueue`
  - `massproducts/ready/addProduct`
- Main tables:
  - `artm_massproducts_draft`
  - `artm_massproducts_draft_values`
  - `artm_massproducts_product_log`
- Behavior note: Ready-tab status rules are critical. If this area breaks, inspect status transitions first.

### Failed

- Template: `view/adminhtml/templates/tabs/failed.phtml`
- JS: `view/adminhtml/web/js/ready-attribute-set-tab.js`
- Main routes:
  - `massproducts/failed/tabMatrix`
  - `massproducts/failed/moveToReady`
  - `massproducts/ready/addProduct`
- Main tables:
  - `artm_massproducts_draft`
  - `artm_massproducts_product_log`

### Success

- Template: `view/adminhtml/templates/tabs/success.phtml`
- JS:
  - `view/adminhtml/web/js/ready-attribute-set-tab.js`
  - `view/adminhtml/web/js/product-image-upload.js`
- Main routes:
  - `massproducts/success/tabMatrix`
  - `massproducts/success/uploadProductImage`
  - `massproducts/media/ajaxupload`
  - `massproducts/media/deleteajaxupload`
  - `massproducts/success/addImagesToProduct`
  - `massproducts/success/setproductimage`
- Main tables:
  - `artm_massproducts_draft`
  - `artm_massproducts_product_log`
  - `artm_massproducts_draft_media`

### SKU Editor

- Template: `view/adminhtml/templates/tabs/single_sku.phtml`
- Main routes:
  - `massproducts/product/load`
  - `massproducts/product/save`
  - `massproducts/store/context`
  - `massproducts/singleskulayout/groups`
  - `massproducts/singleskulayout/templateload`
- Main tables:
  - `artm_massproducts_user_store_pref`
  - `artm_single_sku_layout`
  - `artm_single_sku_group_layout`
  - `artm_single_sku_template`

## Configuration tab map

Parent template:

- `view/adminhtml/templates/tabs/configuration.phtml`

Child tabs:

### Column Mapping

- Template: `view/adminhtml/templates/tabs/column_mapping.phtml`
- JS: `view/adminhtml/web/js/column-mapping-tab.js`
- Routes:
  - `massproducts/mapping/tabList`
  - `massproducts/mapping/tabSave`
  - `massproducts/mapping/tabDelete`
  - `massproducts/mapping/tabAttributes`
- Table: `artm_massproducts_column_mapping`

### Attributes Assignment

- Template: `view/adminhtml/templates/tabs/attributes_assignment.phtml`
- JS: `view/adminhtml/web/js/attributes-assignment-tab.js`
- Routes:
  - `massproducts/attributesassignment/sets`
  - `massproducts/attributesassignment/attributes`
  - `massproducts/attributesassignment/save`
- Table: `artm_massproducts_attr_assignment`

### Attribute Sorting

- Template: `view/adminhtml/templates/tabs/attribute_sorting.phtml`
- JS: `view/adminhtml/web/js/attribute-sorting-tab.js`
- Routes:
  - `massproducts/attributesort/items`
  - `massproducts/attributesort/save`
- Table: `artm_massproducts_attr_sort`

### Single SKU Config

- Template: `view/adminhtml/templates/tabs/single_sku_config.phtml`
- Routes:
  - `massproducts/attributesassignment/sets`
  - `massproducts/singleskulayout/attributes`
  - `massproducts/singleskulayout/save`
  - `massproducts/singleskulayout/groups`
  - `massproducts/singleskulayout/groupsave`
- Tables:
  - `artm_single_sku_layout`
  - `artm_single_sku_group_layout`

### Single SKU Template

- Template: `view/adminhtml/templates/tabs/single_sku_template.phtml`
- Routes:
  - `massproducts/attributesassignment/sets`
  - `massproducts/singleskulayout/groups`
  - `massproducts/singleskulayout/groupsave`
  - `massproducts/singleskulayout/templateload`
  - `massproducts/singleskulayout/templatesave`
- Main owner: `Model/SingleSkuTemplate/TemplateService.php`
- Tables:
  - `artm_single_sku_group_layout`
  - `artm_single_sku_template`

### Logs

- Template: `view/adminhtml/templates/tabs/logs.phtml`
- Routes:
  - `massproducts/logs/listing`
  - `massproducts/upload/download`
- Tables:
  - `artm_massproducts_cron_log`
  - `artm_massproducts_product_log`
  - `artm_massproduct_activity_log`
  - `artm_intake_uploads`

## Queue and cron behavior

- Cron owner: `Cron/ProcessQueuedProducts.php`
- Schedule declaration: `etc/crontab.xml`
- Config namespace: `artm_massproducts/cron/*`

Use `Cron/ProcessQueuedProducts.php` first when the issue is actual product creation, queue progression, or cron timing.

## Known structural quirks

- `Controller/Adminhtml/Index/Tab.php` can still load `queued`, `processing`, `inventory_update`, `cron_settings`, and `upload` templates directly.
- The configuration parent template explicitly says queued and processing tabs were removed from the visible configuration UI.
- `view/adminhtml/templates/tabs/queued.phtml` references `massproducts/queued/processAll`, but there is no matching controller file under `Controller/Adminhtml/Queued`.

## Fast routing guide

- Tab does not load: start with `Controller/Adminhtml/Index/Tab.php`, then the parent tab template loader JS.
- Wrong draft/ready/failed/success counts: start with `Controller/Adminhtml/Stats/TabCounts.php` and the relevant `* /TabMatrix.php` controller.
- Draft row save, media, or staging issue: start with `view/adminhtml/templates/tabs/draft.phtml`, `Controller/Adminhtml/Draft/*`, and draft tables.
- Ready-to-queue or queue-to-product issue: start with `Controller/Adminhtml/Ready/*` and `Cron/ProcessQueuedProducts.php`.
- Single SKU designer issue: start with `Controller/Adminhtml/Singleskulayout/*`, `Model/SingleSkuTemplate/TemplateService.php`, and single-SKU tables.
- Category reorder issue: start with `Controller/Adminhtml/CategoryReorder/*` and `Model/CategoryReorder/Manager.php`.