# ArtM_LuxuryCategory

Adds a `/luxe` variant to every Magento category URL (e.g. `/men` → `/men/luxe`) that shows only products where the `luxury` (Yes/No) attribute is set to Yes — using the existing category page, existing category tree, and existing product assignments. No duplicate categories, no virtual categories, no redirects.

## How it works end to end

1. **`Controller/Router.php`** — a custom frontend router (registered in `etc/frontend/di.xml`, sortOrder `25` — after the core `urlrewrite` router at `20`, before `standard` at `30` and `cms` at `60`). It matches any request path ending in `/luxe`, strips that suffix, looks up the remaining path in the standard `url_rewrite` table to resolve the real category entity id (no hardcoded path mapping — works for any depth, e.g. `/men/perfumes/luxe`), marks luxury mode, and **forwards** (not redirects) to the native `catalog/category/view` controller with that category id. The browser URL never changes.
2. **`Service/LuxuryModeService.php`** — the single source of truth for "are we in luxury mode right now". Reads/writes a request param so the flag survives the internal forward, and falls back to re-deriving it from `getOriginalPathInfo()` (which Magento preserves even after the router changes module/controller/action) so any class can call `isLuxuryMode()` at any point in the request lifecycle and get the right answer. Also owns all URL transforms (`getLuxuryCategoryUrl()`, `appendLuxuryToUrl()`, `removeLuxuryFromUrl()`) — nothing else in the module does its own URL string concatenation.
3. **`Plugin/Catalog/Model/LuxuryLayerProductCollectionPlugin.php`** — `afterGetProductCollection` on `Magento\Catalog\Model\Layer\Category` (not the base `Layer`, so full-text search results are never touched). When luxury mode is on, applies `addAttributeToFilter('luxury', 1)` — the same call Magento's own layered navigation attribute filter makes when a shopper clicks a facet value. **Requires `luxury` to be configured as filterable in layered navigation** (and, on Elasticsearch/OpenSearch stores, indexed for search) — once it is, this goes through the same native search-engine filter pipeline the facet click uses, so `getSize()`, pagination and candidate-id selection are all correctly narrowed *before* paging is applied. (An earlier version of this plugin used `addIdFilter()` on a resolved id list instead — that only restricts what gets fetched from an *already-paginated* candidate set, and was found to under-count/under-fill pages on this store once measured against the real layered-nav facet count; `addAttributeToFilter` does not have that problem because it participates in the search engine's own query construction.)
4. **`Plugin/Catalog/Model/LuxuryCollectionSizePlugin.php`** — defensive safety net: recomputes `getSize()` via a genuine SQL `COUNT` against the fully-conditioned select for any collection the plugin above flagged, in case the search engine's own reported total ever drifts from what the filter actually matches. A no-op once `luxury` is properly indexed (the recomputed count simply matches), so it's kept as insurance rather than removed.

   **Known limitation on search-engine-backed stores:** the search engine applies its own `LIMIT`/`OFFSET` when assembling a page of matching category ids *before* our id filter narrows that set further. On a category where luxury products are a small fraction of a large total, this can under-fill a page (fewer than the configured page size render) rather than backfilling from the next batch. This does not happen on stores without Elasticsearch/OpenSearch (plain MySQL collections apply all filters before pagination). If this becomes a practical problem, the robust fix is injecting the `luxury` filter into the search engine's own request/aggregation (native ES/OpenSearch filter) rather than filtering the collection afterwards — a larger change than the current scope covers.
5. **`Plugin/Theme/Block/Html/AddLuxuryTopmenuItemPlugin.php`** — a `beforeGetHtml` plugin on `Magento\Theme\Block\Html\Topmenu`, registered at sortOrder `10` so it runs *after* `Magento_Catalog`'s own `catalogTopmenu` plugin (sortOrder `0`, unspecified) has already built the category node tree. It walks that tree, and for every category node, checks (via one bulk SQL query) whether that category contains at least one `luxury=1` product; if so, inserts a `Luxe` child node whose URL is `appendLuxuryToUrl()` of the category's own URL. No categories are created; nothing is duplicated; categories with zero luxury products get no `Luxe` node at all.
6. **`Model/ResourceModel/LuxuryProductChecker.php`** — the performance-critical piece behind steps 3 and 5. One query, `catalog_category_product` joined to `catalog_product_entity_int` for the `luxury` attribute (store-scoped with the standard store-then-default EAV fallback). No product collection is ever loaded. This scales the same way regardless of catalog size.
7. **`Plugin/Theme/Block/Html/AddLuxuryBreadcrumbPlugin.php`** — `beforeToHtml` on `Magento\Theme\Block\Html\Breadcrumbs`; appends a trailing `Luxe` crumb when luxury mode is active. Existing crumbs (built by `Magento\Catalog\Block\Category\View`) are untouched.
8. **`Observer/AddLuxuryLayoutHandleObserver.php`** (on `layout_load_before`) — adds the `catalog_category_view_luxe` layout handle whenever luxury mode is active. **`view/frontend/layout/catalog_category_view_luxe.xml`** currently only sets `<body class="luxury-mode">` via that handle, but it's the intended extension point for any future luxury-mode-specific layout/theme customization without touching the router or plugins.

## Why this is Full Page Cache safe

- The router performs an internal **forward**, not a redirect — so `/men/luxe` and `/men` are cached as two independent, correctly-keyed FPC entries, exactly like any other two distinct category URLs.
- The category product collection filter, breadcrumb, layout handle and body class are all derived purely from the URL (via `LuxuryModeService`), not from customer/session state — so they're safe to serve from a shared, anonymous-visitor FPC cache entry.
- The top-menu injection depends only on catalog-wide data (which categories currently contain a luxury product), not on the current visitor or even on whether the *current* request is luxury mode — so the same injected menu HTML is correct for every cached page that shares that menu block.

**Known cache-invalidation caveat:** the injected `Luxe` menu node's presence is invalidated whenever the *category* is saved (it reuses Magento's own category cache tags), but toggling a single product's `luxury` attribute does not, by itself, invalidate the top-menu block's cache. In practice this self-heals on the next category reindex/cache event that already touches that category; if you need instant menu updates on a lone attribute toggle, add a plugin invalidating `Magento\Catalog\Model\Category::CACHE_TAG . '_' . $categoryId` for that product's categories on `catalog_product_save_after`.

## Extending to `/product-name/luxe` later

The router already isolates entity-type resolution in one place (`Router::resolveCategoryId()`, checked against `UrlRewrite::getEntityType() === 'category'`). To support product URLs later: add a sibling check for `entity_type === 'product'`, forward to `catalog/product/view` instead, and extend `LuxuryModeService`/the collection plugin as needed for whatever "luxury variant" of a product page means. No changes to the routing/mode-detection architecture are required.

## Files

```
registration.php
composer.json
etc/module.xml
etc/frontend/routes.xml               (pre-existing placeholder route; unused by the /luxe matching itself — the custom Router below handles all matching directly via RouterList, not a frontName)
etc/frontend/di.xml                   (RouterList entry + all plugin registrations)
etc/frontend/events.xml               (layout_load_before observer registration)
Controller/Router.php
Service/LuxuryModeService.php
Model/ResourceModel/LuxuryProductChecker.php
Plugin/Catalog/Model/LuxuryLayerProductCollectionPlugin.php
Plugin/Theme/Block/Html/AddLuxuryTopmenuItemPlugin.php
Plugin/Theme/Block/Html/AddLuxuryBreadcrumbPlugin.php
Observer/AddLuxuryLayoutHandleObserver.php
view/frontend/layout/catalog_category_view_luxe.xml
```

## Requirements

- The `luxury` Yes/No product attribute must already exist (attribute code `luxury`, backend type `int`) — this module reads it, does not create it.
- Every category you want a `Luxe` variant for must have a normal, resolvable URL rewrite (i.e. must be a real, saved category — no special setup beyond that).

## Install

```
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

No database schema is introduced by this module, so `setup:upgrade` only runs module registration — safe to run at any time.
