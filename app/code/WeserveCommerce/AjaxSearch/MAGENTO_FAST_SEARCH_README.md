# Magento 2 Fast Search

> Developer + AI implementation specification for a Magento 2 extension that provides fast AJAX autocomplete and search by querying Magento's configured search backend directly, with OpenSearch as the primary target.

## 1. Project Summary

This project is a Magento 2 extension concept for high-performance catalog search and autocomplete.

The goal is to avoid unnecessary Magento application-layer product loading for every search hit. Instead of taking search results and repeatedly loading full Magento product entities through repositories/models, the extension should query the configured search engine directly and return only the fields required by the frontend.

The current prototype uses a standalone Node.js + Fastify API connected directly to OpenSearch. That prototype has already proven the core search query against the existing Magento product index.

The production target is a self-contained Magento 2 module with the same direct-search concept.

---

## 2. Current Prototype

### 2.1 Prototype stack

```text
Windows
  |
  +-- OpenSearch
  |     http://localhost:9200
  |
  +-- Node.js + Fastify
        http://localhost:3000
              |
              +-- OpenSearch client
              |
              +-- Magento product index
```

The Node.js API currently:

- connects directly to OpenSearch;
- searches Magento's existing product index;
- supports a store ID parameter;
- uses a store-specific Magento index pattern;
- supports pagination;
- supports minimum/maximum price filtering;
- supports stock filtering;
- searches product name, SKU, brand, tags, description and short description;
- returns JSON product records.

### 2.2 Important prototype discovery

Magento's `store_id` field exists in `_source`, but on the inspected product index it is mapped as:

```json
{
  "type": "text",
  "index": false
}
```

Therefore `store_id` cannot be used as an OpenSearch `term` filter on that field.

The working approach is to query the store-specific index pattern instead:

```text
store_id = 1
        -> golfoy_product_1_*
```

For example, the tested index was:

```text
 golfoy_product_1_v54
```

Do **not** hard-code the version (`v54`) in the Magento extension. Magento indexes can be rebuilt/recreated and the active version can change.

---

## 3. Problem Being Solved

A conventional Magento search flow may involve several application-layer steps after search execution. For a high-traffic autocomplete endpoint, this can create avoidable work when only a small amount of product information is required.

Typical expensive pattern to avoid for autocomplete:

```text
User input
   -> Magento search request
   -> search results / product IDs
   -> load product entities
   -> ProductRepository / model loading
   -> EAV attribute loading
   -> pricing / media / URL work
   -> serialize response
```

Target approach:

```text
User input
   -> Magento AJAX endpoint
   -> configured search-engine adapter
   -> OpenSearch/Elasticsearch query
   -> return selected indexed fields
   -> compact JSON response
```

The extension is intentionally optimized for the use case where the search engine already contains the data needed to render the result.

---

## 4. Product Goals

### Primary goals

1. Fast autocomplete response.
2. Fast full search response.
3. Direct use of Magento's configured search backend.
4. No duplicate product search database.
5. No hard-coded OpenSearch index version.
6. Minimal JSON response payload.
7. Multi-store support.
8. Pluggable search-engine adapter design.
9. Safe Magento installation with minimal configuration.
10. Clear extension APIs so another developer or AI can continue implementation without rediscovering the architecture.

### Non-goals

- Replacing Magento's catalog indexing process.
- Maintaining a second product search index.
- Rebuilding Magento's EAV/product catalog system.
- Making OpenSearch publicly accessible.
- Loading complete product entities for every autocomplete result.
- Hard-coding a single merchant's index name/version.

---

## 5. Target Architecture

```text
                 Magento 2 Storefront
                        |
                        | AJAX
                        v
              Fast Search Controller
                        |
                        v
               Search Service Layer
                        |
                        v
             SearchEngineAdapterInterface
                    /          \
                   /            \
                  v              v
        OpenSearchAdapter   ElasticsearchAdapter
                  |              |
                  +-------> Search Backend
                              |
                              v
                     Magento Product Index
                              |
                              v
                       Minimal JSON result
```

### Key principle

The frontend must communicate with Magento, not directly with OpenSearch.

```text
Browser -> Magento -> OpenSearch
```

Never expose the OpenSearch server directly to the browser.

---

## 6. Magento Configuration Integration

The extension should use Magento's existing catalog search configuration wherever possible.

The current store configuration contains settings such as:

```text
Catalog Search

Minimal Query Length        1
Maximum Query Length        128
Number of top search results to cache 100
Enable EAV Indexer          No
Autocomplete Limit          8
Search Engine               OpenSearch
OpenSearch Hostname         localhost
OpenSearch Port             9200
OpenSearch Index Prefix     golfoy
OpenSearch HTTP Auth        No
OpenSearch Server Timeout   15
```

These values are **configuration inputs**, not values that should be hard-coded for one store.

### Extension behavior

By default:

```text
Search engine = Magento configured search engine
Host          = Magento configured search host
Port          = Magento configured search port
Index prefix  = Magento configured prefix
Auth          = Magento configured authentication settings
Timeout       = Magento configured timeout
```

The extension may provide optional overrides, but Magento configuration should be the default source of truth.

---

## 7. Do Not Hard-Code Product Index Versions

Magento product indexes may look like:

```text
golfoy_product_1_v54
golfoy_product_7_v48
golfoy_product_6_v49
```

The extension must not contain code such as:

```php
$index = 'golfoy_product_1_v54';
```

Instead, derive the correct index/pattern from Magento's configured search index information and the current store context.

A safe abstraction is:

```php
interface ProductIndexResolverInterface
{
    public function resolve(int $storeId): string;
}
```

The resolver should encapsulate all Magento-specific index-name discovery.

---

## 8. Search Engine Adapter Design

Use an interface so the extension is not permanently coupled to OpenSearch.

```php
interface SearchEngineAdapterInterface
{
    /**
     * @param string $query
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search(
        string $query,
        array $filters = [],
        int $limit = 20,
        int $offset = 0
    ): array;

    /**
     * @param string $query
     * @param array $filters
     * @param int $limit
     * @return array
     */
    public function autocomplete(
        string $query,
        array $filters = [],
        int $limit = 8
    ): array;
}
```

Primary implementation:

```text
Vendor\\FastSearch\\Model\\Search\\OpenSearchAdapter
```

Future implementation:

```text
Vendor\\FastSearch\\Model\\Search\\ElasticsearchAdapter
```

Magento DI should choose the adapter based on the configured engine.

---

## 9. Search Query Strategy

The prototype currently uses weighted fields similar to:

```js
[
  'name^5',
  'sku^4',
  'brand_value^3',
  'search_tags^3',
  'description',
  'short_description'
]
```

This means:

- product name has the strongest relevance weight;
- SKU is highly important;
- brand and search tags are important;
- descriptions are secondary.

This strategy should be preserved initially, then benchmarked with real search terms.

### Fuzzy search

The prototype currently uses:

```text
fuzziness = AUTO
```

Do not assume fuzzy matching is always faster. It should be benchmarked, especially for autocomplete. Consider using different query strategies for autocomplete and full search.

---

## 10. Store Handling

The extension must be Magento-store aware.

The browser may call:

```http
GET /fastsearch/search?q=golf
```

The backend should derive the Magento store context from the request rather than trusting an arbitrary store ID sent by the browser.

Internal logic should conceptually be:

```text
Magento Store Context
        |
        v
Current Store ID
        |
        v
Product Index Resolver
        |
        v
Current store's product index/pattern
```

A browser-provided `store_id` may be accepted only when explicitly required and validated against Magento's allowed store context.

---

## 11. Product Visibility and Status

The extension should normally return products that are valid for catalog search/display.

The inspected product documents contain fields such as:

```text
status = 1
visibility = 4
is_out_of_stock = 0
```

The implementation should define a clear policy for:

- enabled/disabled products;
- catalog/search visibility;
- out-of-stock products;
- website/store availability.

Do not assume that `visibility = 4` is universally the only acceptable value without validating the store's catalog requirements.

Filtering rules should be configurable or encapsulated in a policy class rather than scattered across controllers.

Suggested interface:

```php
interface ProductFilterPolicyInterface
{
    public function buildFilters(int $storeId, array $requestFilters = []): array;
}
```

---

## 12. Autocomplete Endpoint

The main endpoint should be designed specifically for very small responses.

Example:

```http
GET /fastsearch/autocomplete?q=golf
```

Suggested response:

```json
{
  "query": "golf",
  "total": 8,
  "products": [
    {
    "sku": "123065-BKRG",
    "name": "Skechers Women's Elite 5...",
    "price": 9895,
    "special_price": null,
      "url": "/product-url",
      "image": "/media/catalog/product/example.jpg",
    "brand": "Skechers",
    "attributes": {}
    }
  ]
}
```

Only return fields required by the autocomplete UI.

Do not return the complete Magento `_source` document.

The inspected product documents can contain large `description`, category, attribute and metadata fields. Returning all of that data on every keystroke defeats the purpose of a low-latency endpoint.

---

## 13. Full Search Endpoint

Example:

```http
GET /fastsearch/search?q=golf&page=1&limit=20
```

Suggested response:

```json
{
  "query": "golf",
  "page": 1,
  "limit": 20,
  "total": 1298,
  "products": [
    {
    "sku": "123065-BKRG",
    "name": "Skechers Women's Elite 5...",
    "price": 9895,
    "special_price": null,
      "url": "/product-url",
      "image": "/media/catalog/product/example.jpg",
    "brand": "Skechers",
    "attributes": {}
    }
  ]
}
```

Each product response contains only `sku`, `name`, `price`, `special_price`,
`url`, `image`, `brand`, and configured `attributes`.

---

## 14. Suggested Search Filters

Initial filters:

```text
q
page
limit
min_price
max_price
in_stock
brand
category
```

Potential future filters:

```text
color
size
rating
discount
category path
custom attributes
sort
```

Filters should be translated into OpenSearch queries by the adapter rather than assembled directly inside the HTTP controller.

---

## 15. Price Handling

The current Magento product documents contain both:

```text
price_0_1 = "9895.000000"
customprice = 989500
```

The prototype uses `customprice` for price-range filtering and treats it as an integer value in paise.

Example:

```text
₹9,895
=> 989500 paise
```

The extension must not blindly assume this field exists in every Magento installation.

Create a price-field abstraction/configuration:

```php
interface PriceFieldResolverInterface
{
    public function getSearchField(): string;
}
```

The production module should verify the actual indexed price field before applying price filters.

---

## 16. Stock Handling

The inspected documents contain:

```text
is_out_of_stock = 0
```

The prototype interprets:

```text
0 = in stock
1 = out of stock
```

Keep this logic configurable/encapsulated because inventory/index implementations can vary.

Suggested interface:

```php
interface StockFilterInterface
{
    public function build(bool $inStock): array;
}
```

---

## 17. Caching

The extension should support layered caching where useful.

### Layer 1: Browser debounce

Do not send one request for every raw keystroke instantly.

Example:

```text
User types: g
User types: go
User types: gol
User types: golf

Only send request after a small debounce interval.
```

### Layer 2: Magento cache

Cache identical autocomplete queries for a short period if appropriate.

### Layer 3: Search-engine cache

Rely on OpenSearch's normal query/index caches where applicable.

Avoid building a large custom Magento cache before measuring real traffic.

---

## 18. Performance Targets

The original business goal is approximately:

```text
~200 ms API response target
```

This should be treated as an engineering target, not a guaranteed SLA.

Measure separately:

```text
Browser -> Magento controller
Magento controller -> search engine
Search engine execution
PHP response serialization
Network transfer
```

The extension should expose debug timing only in developer mode, for example:

```json
{
  "meta": {
    "search_ms": 24,
    "total_ms": 41
  }
}
```

Do not expose internal timing data by default in production responses.

---

## 19. Performance Rules

### Rule 1: Never load full products for autocomplete unless required

Bad pattern:

```php
$productRepository->getById($id);
```

for every search hit.

Preferred pattern:

```text
OpenSearch
  -> selected fields
  -> JSON
```

### Rule 2: Use small result sizes

Autocomplete default:

```text
8
```

Full search default:

```text
20
```

### Rule 3: Return selected fields only

Use OpenSearch source filtering when supported by the adapter:

```text
_source includes:
  id/SKU/name/price/url/image/stock
```

### Rule 4: Avoid large descriptions in autocomplete

Descriptions are expensive in payload size and are normally unnecessary for suggestions.

### Rule 5: Avoid expensive queries unless necessary

Fuzzy search, wildcard queries, large aggregations and unbounded pagination must be benchmarked.

### Rule 6: Never use deep pagination casually

Prefer a pagination strategy appropriate for the backend if very deep result pages must be supported.

---

## 20. Security

### Never expose OpenSearch to the browser

Incorrect:

```text
Browser -> http://search.example.com:9200
```

Correct:

```text
Browser -> Magento -> private OpenSearch
```

### Authentication

The extension must support Magento-configured OpenSearch authentication when enabled.

Credentials must never be returned in the API response.

### Input validation

Validate:

- query length;
- page;
- limit;
- numeric price values;
- allowed filters;
- sort options;
- store context.

Do not pass arbitrary user input into raw OpenSearch DSL.

### Rate limiting

Consider rate limiting or lightweight abuse controls for public autocomplete endpoints because they can receive a very high request volume.

---

## 21. Frontend Integration

The extension should provide a minimal JavaScript module that attaches to the Magento search input.

Conceptually:

```text
Input event
   |
   v
Debounce
   |
   v
GET /fastsearch/autocomplete?q=...
   |
   v
Render suggestions
```

On Enter:

```text
GET /fastsearch/search?q=...
```

The UI should be replaceable without changing the backend search adapter.

Suggested separation:

```text
ViewModel / UI
Controller / HTTP
Service / business logic
Adapter / search backend
Resolver / Magento-specific index details
```

---

## 22. Magento Module Structure

Suggested final module structure:

```text
app/code/Vendor/FastSearch/
├── Api/
│   ├── SearchEngineAdapterInterface.php
│   ├── ProductIndexResolverInterface.php
│   ├── ProductFilterPolicyInterface.php
│   └── PriceFieldResolverInterface.php
│
├── Controller/
│   └── Search/
│       ├── Autocomplete.php
│       └── Index.php
│
├── Model/
│   ├── Config.php
│   ├── Search/
│   │   ├── SearchEngineAdapterInterface.php
│   │   ├── OpenSearchAdapter.php
│   │   └── ElasticsearchAdapter.php
│   ├── Index/
│   │   └── ProductIndexResolver.php
│   ├── Filter/
│   │   ├── ProductFilterPolicy.php
│   │   ├── PriceFieldResolver.php
│   │   └── StockFilter.php
│   └── Response/
│       └── ProductResponseMapper.php
│
├── Block/
│   └── Search.php
│
├── Helper/
│   └── Data.php
│
├── etc/
│   ├── module.xml
│   ├── di.xml
│   ├── frontend/
│   │   ├── routes.xml
│   │   └── events.xml
│   ├── adminhtml/
│   │   └── system.xml
│   └── acl.xml
│
├── view/frontend/
│   ├── layout/
│   │   └── default.xml
│   ├── templates/
│   │   └── search.phtml
│   └── web/
│       ├── js/
│       │   ├── autocomplete.js
│       │   └── search.js
│       └── css/
│           └── fast-search.css
│
├── composer.json
├── registration.php
└── README.md
```

The exact structure may be simplified during implementation. The important requirement is separation of responsibilities.

---

## 23. Controller Responsibilities

Controllers should stay thin.

Example:

```php
public function execute()
{
    $query = (string)$this->request->getParam('q', '');

    $result = $this->searchService->autocomplete(
        $query,
        $this->storeManager->getStore()->getId()
    );

    return $this->jsonFactory->create()->setData($result);
}
```

The controller should **not** contain large OpenSearch query-building logic.

---

## 24. Search Service

Suggested service:

```php
interface SearchServiceInterface
{
    public function autocomplete(string $query, int $storeId): array;

    public function search(
        string $query,
        int $storeId,
        array $filters = [],
        int $page = 1,
        int $limit = 20
    ): array;
}
```

Service responsibilities:

1. validate query;
2. resolve store context;
3. resolve current product index;
4. build filter policy;
5. call configured adapter;
6. map search hits to API response;
7. return compact JSON-safe structures.

---

## 25. Response Mapping

Do not expose the raw search document.

Create a mapper:

```php
interface ProductResponseMapperInterface
{
    public function map(array $hit): array;
}
```

Example mapping:

```text
OpenSearch document
        |
        v
ProductResponseMapper
        |
        v
id
sku
name
price
url
image
in_stock
```

This also protects the API from accidentally exposing internal Magento fields.

---

## 26. Search Result Ranking

Initial relevance strategy:

```text
name            ^5
sku             ^4
brand           ^3
search_tags     ^3
description      1
short_description 1
```

The ranking must be validated against real search queries.

Test categories should include:

```text
Exact product name
Partial product name
SKU
Brand
Misspelling
Generic category term
Product + brand
Short query
Long query
```

Do not optimize ranking based only on one example query such as `golf`.

---

## 27. Autocomplete vs Full Search

They should not necessarily use exactly the same query.

### Autocomplete

Prioritize:

- low latency;
- very small result count;
- product name/SKU/brand relevance;
- minimal response size;
- aggressive debounce;
- optional lightweight caching.

### Full search

Can use:

- broader fields;
- richer filters;
- pagination;
- sorting;
- aggregations/facets if later required.

The adapter should expose separate methods so these behaviors can evolve independently.

---

## 28. Facets and Filters — Future Phase

Do not implement every Magento catalog feature in version 1.

Possible future support:

```text
Category facet
Brand facet
Price ranges
Size
Color
Rating
Availability
Discount
Custom EAV attributes
```

First establish a fast, stable product-result endpoint.

---

## 29. Error Handling

The API should return predictable JSON errors.

Example:

```json
{
  "error": {
    "code": "SEARCH_BACKEND_UNAVAILABLE",
    "message": "Search is temporarily unavailable."
  }
}
```

Do not expose:

- OpenSearch connection strings;
- credentials;
- internal stack traces;
- raw search DSL;
- server filesystem paths.

Log technical details server-side in developer/production-safe logging.

---

## 30. Empty and Invalid Queries

Define explicit rules.

Example:

```text
q = ""        -> either return empty suggestions or configured popular items
q = "g"       -> allowed if minimum query length = 1
q > 128 chars -> reject/truncate according to configured maximum
```

The extension should read Magento's minimum and maximum query-length settings where appropriate.

---

## 31. Installation Goals

The extension should be as close as possible to plug-and-play.

Expected installation flow:

```bash
composer require vendor/module-fast-search

bin/magento module:enable Vendor_FastSearch
bin/magento setup:upgrade
bin/magento cache:flush
```

The module should then:

1. detect Magento configuration;
2. detect the active search engine;
3. validate search-backend connectivity;
4. resolve the current catalog index;
5. expose AJAX endpoints;
6. attach autocomplete UI to the configured search box.

If compatibility cannot be detected automatically, show a clear Magento Admin configuration error rather than silently failing.

---

## 32. Admin Configuration Plan

Suggested configuration section:

```text
Stores
  -> Configuration
    -> Catalog
      -> Fast Search
```

### General

```text
Enabled
Autocomplete Enabled
Full Search Enabled
Autocomplete Limit
Search Result Limit
Search Results Loading Mode
Minimum Query Length
Maximum Query Length
```

`Search Results Loading Mode` is available under **Stores > Configuration > Catalog > Fast Search > General Configuration**. Choose **Numbered Pagination** to navigate between result pages or **Infinite Scroll** to append the next result page automatically as the shopper scrolls. The infinite-scroll mode also provides a load-more button for manual loading and accessibility.

### Search Engine

```text
Use Magento Search Engine Configuration
Search Engine Override (optional)
Connection Override (optional)
Request Timeout
```

### Search behavior

```text
Fuzzy Search
Search Name
Search SKU
Search Brand
Search Tags
Search Description
```

### Result behavior

```text
Hide Disabled Products
Hide Non-Searchable Products
Hide Out-of-Stock Products
```

Only expose settings that have a clear business purpose.

---

## 33. Compatibility Strategy

The extension must first determine the Magento environment before using low-level search-engine internals.

During installation/startup, verify:

```text
Magento version
Configured search engine
Search client availability
Search backend connectivity
Index availability
Expected product fields
```

Do not assume that every Magento installation has exactly the same index field mappings.

The extension should have capability detection rather than relying entirely on hard-coded fields.

---

## 34. Field Detection / Capability Checks

Because Magento installations can differ due to Magento version, indexing configuration and third-party extensions, create a capability layer where practical.

Example:

```php
interface SearchCapabilitiesInterface
{
    public function supports(string $field): bool;

    public function getProductFields(): array;
}
```

At minimum, validate required fields before enabling direct-search mode.

Required baseline fields may include:

```text
sku
name
status
visibility
price
```

Optional fields:

```text
brand
image
url
stock
rating
discount
```

---

## 35. Testing Strategy

### Unit tests

Test:

- query validation;
- pagination;
- price conversion;
- stock filter construction;
- store/index resolution;
- response mapping;
- engine adapter selection.

### Integration tests

Test against a Magento installation with OpenSearch:

```text
search
autocomplete
empty query
SKU query
brand query
price filter
stock filter
store switching
index rebuild
missing optional field
search backend unavailable
```

### Browser tests

Test:

```text
Typing
Debounce
Keyboard navigation
Enter
Escape
Mouse selection
Mobile input
Slow network
Network failure
```

---

## 36. Performance Benchmark Plan

Benchmark using realistic catalog sizes.

Target test sizes:

```text
10,000 products
50,000 products
100,000 products
250,000+ products
```

Test queries:

```text
short generic term
brand
SKU
partial name
misspelling
category term
```

Measure:

```text
P50
P95
P99
error rate
OpenSearch CPU
OpenSearch heap
PHP CPU
PHP memory
network payload size
```

Do not declare the extension "200 ms" simply because one local request returned in 200 ms. The real target must be measured under representative load.

---

## 37. Suggested Development Phases

### Phase 1 — Search core

Build:

```text
Module skeleton
Config reader
OpenSearch adapter
Index resolver
Search service
Autocomplete controller
Search controller
Response mapper
```

Goal:

```text
Magento -> direct OpenSearch query -> compact JSON
```

### Phase 2 — Frontend

Build:

```text
Search-box integration
Debounce
Autocomplete dropdown
Keyboard navigation
Enter -> full search
Loading/error state
```

### Phase 3 — Filters

Add:

```text
price
stock
brand
category
```

### Phase 4 — Performance

Add and benchmark:

```text
_source filtering
cache
query tuning
connection reuse
payload reduction
```

### Phase 5 — Compatibility

Test across supported Magento/OpenSearch combinations and installations with different indexing configurations.

### Phase 6 — Production hardening

Add:

```text
security validation
rate limiting strategy
logging
admin diagnostics
health checks
upgrade-safe configuration
```

---

## 38. AI Development Instructions

An AI developer working on this repository should follow these rules.

### Rule A — Preserve architecture

Do not move OpenSearch query logic into controllers just because it is faster to implement.

Keep:

```text
Controller
  -> Service
  -> Adapter
  -> Search backend
```

### Rule B — Do not call ProductRepository for every autocomplete result

Only load a Magento product entity when the indexed document does not contain a required value and there is a demonstrated business requirement.

### Rule C — Never hard-code a versioned Magento index

Bad:

```text
golfoy_product_1_v54
```

Good:

```text
Magento configuration/index resolver/current index metadata
```

### Rule D — Never expose OpenSearch to the browser

The browser talks only to Magento.

### Rule E — Return minimal fields

Especially for autocomplete.

### Rule F — Do not assume mappings

Check field availability/mapping when implementation depends on a field.

### Rule G — Keep OpenSearch-specific code isolated

Do not spread OpenSearch query syntax throughout the module.

### Rule H — Measure before optimizing

Any performance claim must be backed by benchmark data.

### Rule I — Preserve Magento conventions

Use dependency injection, configuration, service contracts where appropriate, ACL/configuration conventions, controller conventions, and Magento deployment practices.

### Rule J — Prefer upgrade-safe integrations

Avoid copying Magento core classes unless absolutely necessary. Prefer public APIs/configuration and isolated compatibility adapters.

---

## 39. OpenSearch Query Prototype

The current Node.js prototype is conceptually doing the following:

```js
const result = await client.search({
  index: `golfoy_product_${storeId}_*`,
  body: {
    from,
    size: limit,
    query: {
      bool: {
        must: query
          ? [{
              multi_match: {
                query,
                fields: [
                  'name^5',
                  'sku^4',
                  'brand_value^3',
                  'search_tags^3',
                  'description',
                  'short_description'
                ],
                fuzziness: 'AUTO'
              }
            }]
          : [{ match_all: {} }],
        filter: filters
      }
    }
  }
});
```

This is a **prototype reference**, not the final Magento implementation.

The Magento module should translate the same intent into the PHP search adapter.

---

## 40. Prototype Result Example

The current working prototype searched:

```text
q=golf
store_id=1
limit=5
```

and returned results from:

```text
golfoy_product_1_v54
```

with a reported total of:

```text
1298
```

The returned documents contain fields such as:

```text
id
sku
name
url_key
brand_value
status
visibility
price_0_1
customprice
is_out_of_stock
discount
crecommend
bestseller
```

The final extension should map only the fields actually required by the storefront.

---

## 41. Suggested API Contract

### Autocomplete

```http
GET /fastsearch/autocomplete?q={query}
```

Optional parameters:

```text
limit
```

Response:

```json
{
  "query": "golf",
  "total": 8,
  "products": []
}
```

### Search

```http
GET /fastsearch/search?q={query}
```

Optional parameters:

```text
page
limit
min_price
max_price
in_stock
brand
category
sort
```

Response:

```json
{
  "query": "golf",
  "page": 1,
  "limit": 20,
  "total": 1298,
  "products": []
}
```

The exact route names can be changed to fit Magento conventions.

---

## 42. Operational Diagnostics

Provide an admin or CLI diagnostic command such as:

```bash
bin/magento fastsearch:diagnose
```

It should report:

```text
Magento version
Store ID
Configured search engine
Search host
Search port
Index prefix
Authentication enabled/disabled
Connection status
Resolved product index
Required field availability
```

Never print credentials.

Optional:

```bash
bin/magento fastsearch:test-search golf
```

This can make installation troubleshooting much easier.

---

## 43. Failure Modes

### Search backend unavailable

Return a controlled application error and log the technical exception.

### Index not found

Show a clear diagnostic message. Do not automatically create a duplicate product index.

### Field missing

Use capability checks and a safe fallback where possible.

### Magento index rebuild

The extension must continue working after Magento recreates the product index.

### Search engine changed

If the merchant changes the Magento search engine, adapter selection should follow configuration rather than remaining locked to OpenSearch.

---

## 44. Version 1 Acceptance Criteria

Version 1 is considered functional when all of the following are true:

- Magento module installs normally.
- Extension reads Magento search configuration.
- OpenSearch connection succeeds.
- Current product index is resolved without hard-coded version names.
- Autocomplete endpoint works.
- Full search endpoint works.
- Current Magento store is respected.
- Only relevant/allowed products are returned.
- Autocomplete returns a small response.
- No ProductRepository loop is used for basic autocomplete results.
- Frontend search box can display autocomplete results.
- Enter can execute full search.
- Search failure produces a safe JSON error.
- Credentials and internal backend details are not exposed.
- The module survives Magento product index rebuilds.

---

## 45. Recommended Next Implementation Step

Start with **Phase 1** and build the Magento module without changing the search behavior unnecessarily.

Implementation order:

```text
1. registration.php
2. module.xml
3. composer.json
4. Magento search configuration reader
5. Search engine resolver
6. Product index resolver
7. OpenSearch adapter
8. Search service
9. Response mapper
10. Autocomplete controller
11. Full search controller
12. CLI diagnostics
13. Frontend AJAX
14. Performance tests
```

Use the existing Node.js API as the functional reference while moving the logic into Magento PHP.

---

## 46. Final Architectural Decision

The project should be treated as a **Magento-native direct-search extension** rather than as a separate search platform.

The intended architecture is:

```text
Magento 2
   |
   +-- Existing Magento product/indexing process
   |
   +-- Existing configured search engine
   |        |
   |        +-- OpenSearch
   |        +-- Elasticsearch (future adapter)
   |
   +-- Fast Search Extension
            |
            +-- AJAX Controllers
            +-- Search Service
            +-- Search Engine Adapter
            +-- Product Index Resolver
            +-- Product Response Mapper
            +-- Frontend Autocomplete
```

The extension **uses Magento's existing indexed catalog data**. It does not create a second catalog search database unless a future requirement explicitly calls for one.

The Node.js prototype has already validated the key idea: direct search against Magento's OpenSearch product index can return catalog results without first loading every result through Magento's product repository layer.

The production extension should now focus on making that approach Magento-native, upgrade-safe, multi-store aware, configurable, secure and measurable.

---

## 47. Repository / Handoff Notes

When this README is used by a new developer or AI agent, start by reading this document first.

Then inspect:

```text
Magento module source
composer.json
etc/di.xml
etc/adminhtml/system.xml
controllers
search adapters
index resolver
frontend JS
```

Before changing search behavior:

1. Confirm the Magento version.
2. Confirm the configured search engine.
3. Confirm the current product index naming/mapping.
4. Confirm required product fields.
5. Run an existing search test.
6. Measure the current response time.
7. Make the smallest isolated change.
8. Re-run functional and performance tests.

The primary engineering objective is **fast catalog search without sacrificing Magento compatibility or upgrade safety**.


```
└── 📁WeserveCommerce
    └── 📁AjaxSearch
        └── 📁Api
            ├── PriceFieldResolverInterface.php
            ├── ProductFilterPolicyInterface.php
            ├── ProductIndexResolverInterface.php
            ├── ProductResponseMapperInterface.php
            ├── SearchEngineAdapterInterface.php
            ├── SearchServiceInterface.php
        └── 📁Block
            ├── Search.php
        └── 📁Console
            └── 📁Command
                ├── DiagnoseCommand.php
        └── 📁Controller
            └── 📁Search
                ├── Autocomplete.php
        └── 📁etc
            └── 📁adminhtml
                ├── system.xml
            └── 📁frontend
                ├── routes.xml
            ├── acl.xml
            ├── config.xml
            ├── di.xml
            ├── indexer.xml
            ├── module.xml
            ├── mview.xml
        └── 📁Model
            └── 📁Config
                └── 📁Source
                    ├── SearchableProductAttributes.php
            └── 📁Filter
                ├── PriceFieldResolver.php
                ├── ProductFilterPolicy.php
                ├── StockFilter.php
            └── 📁Index
                ├── ProductIndexResolver.php
            └── 📁Indexer
                ├── QuickSearch.php
            └── 📁Response
                ├── ProductResponseMapper.php
            └── 📁Search
                ├── ElasticsearchAdapter.php
                ├── OpenSearchAdapter copy.php
                ├── OpenSearchAdapter.php
            ├── Config.php
            ├── SearchService.php
        └── 📁view
            └── 📁frontend
                └── 📁layout
                    ├── default.xml
                └── 📁templates
                    └── 📁form
                        ├── form.mini.phtml
                    ├── search.phtml
                └── 📁web
                    └── 📁css
                        ├── ajax-search.css
                        ├── fast-search.css
                    └── 📁js
                        ├── ajax-search.js
                        ├── autocomplete.js
                ├── requirejs-config.js
        ├── composer.json
        ├── MAGENTO_FAST_SEARCH_README.md
        └── registration.php
```






curl -s -X GET "http://127.0.0.1:9200/golfoy_store_1_product/_search?pretty" \
-H 'Content-Type: application/json' \
-d '{
  "size": 10,
  "query": {
    "match": {
      "sku": "WGD601000"
    }
  }
}'





curl -s -X GET "http://127.0.0.1:9200/golfoy_store_1_product/_search?pretty" \
-H 'Content-Type: application/json' \
-d '{
  "size": 1,
  "query": {
    "match_all": {}
  }
}'




curl -s "http://127.0.0.1:9200/_cat/indices?v"

curl -s "http://127.0.0.1:9200/golfoy_store_1_product/_count?pretty"

watch -n 1 "mysql -u magento -p'magento123' -e 'SHOW FULL PROCESSLIST;'"

curl -s "http://127.0.0.1:9200/golfoy_store_1_product/_search?pretty" -H 'Content-Type: application/json' -d '{"size":3,"query":{"match_phrase":{"name":"Golfoy Basics Golf Club Pen Gift Set"}}}' - bypasses all PHP query logic. If this returns 0 hits, it's an indexing problem (not indexed, wrong store_id, or skipped by the configurable-child logic),