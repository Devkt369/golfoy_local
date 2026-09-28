# ArtM_ProductImageApi

Magento 2 custom REST API module that lets you assign a product image by passing a **SKU** and an **absolute file path**. The image is automatically set as `base_image`, `small_image`, and `thumbnail`.

---

## Installation

### Option A — Copy into `app/code`

```
app/code/ArtM/ProductImageApi/
```

### Option B — Composer (local path repository)

```json
// composer.json (root)
"repositories": [
    {
        "type": "path",
        "url": "path/to/ArtM/ProductImageApi"
    }
]
```

```bash
composer require artm/module-product-image-api
```

### Enable the module

```bash
php bin/magento module:enable ArtM_ProductImageApi
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

---

## REST API Reference

### Endpoint

| Method | URL                              |
|--------|----------------------------------|
| POST   | `/rest/V1/artm/product-image/set` |

### Authentication

Use a standard Magento Bearer token in the `Authorization` header.

```bash
# 1. Obtain a token
curl -X POST https://your-store.com/rest/V1/integration/admin/token \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"your_password"}'
```

### Request Body

```json
{
    "sku": "SKU123",
    "imagePath": "/var/www/html/pub/media/import/product-image.jpg"
}
```

| Field       | Type   | Required | Description                                   |
|-------------|--------|----------|-----------------------------------------------|
| `sku`       | string | ✅       | The product SKU.                              |
| `imagePath` | string | ✅       | Absolute path to the image on the server.     |

### Response Body

```json
{
    "success": true,
    "message": "Image successfully set for product SKU \"SKU123\". Roles assigned: image, small_image, thumbnail.",
    "sku": "SKU123",
    "image_url": "https://your-store.com/pub/media/catalog/product/p/r/product-image.jpg"
}
```

| Field       | Type        | Description                                      |
|-------------|-------------|--------------------------------------------------|
| `success`   | boolean     | `true` if the operation succeeded.               |
| `message`   | string      | Human-readable result or error description.      |
| `sku`       | string      | The SKU that was processed.                      |
| `image_url` | string/null | Public URL of the image now assigned to product. |

---

## Example cURL Call

```bash
TOKEN="your_admin_token_here"

curl -X POST "https://your-store.com/rest/V1/artm/product-image/set" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "sku": "MH01-XS-Black",
    "imagePath": "/var/www/html/pub/media/import/mh01-black.jpg"
  }'
```

---

## How It Works

1. **Validation** — The module confirms the `imagePath` is absolute, the file exists on disk, and has an allowed extension (`jpg`, `jpeg`, `png`, `gif`, `webp`).

2. **Copy** — The source file is copied into `pub/media/catalog/product/<first_char>/<second_char>/` using Magento's standard two-level sub-directory naming. Filename collisions are resolved automatically by appending a counter.

3. **Clear existing gallery** — All existing gallery images for the product are removed so the new image is the single canonical image.

4. **Assign roles** — The image is added to the product gallery and assigned as `image` (base image), `small_image`, and `thumbnail`.

5. **Save** — The product is saved through `ProductRepository`, which triggers standard Magento indexing and cache invalidation.

6. **Response** — A structured JSON response is returned with the public URL of the newly assigned image.

---

## File Structure

```
ArtM/ProductImageApi/
├── Api/
│   ├── Data/
│   │   └── ProductImageResponseInterface.php   # Response DTO interface
│   └── ProductImageManagementInterface.php     # Service contract
├── Model/
│   ├── Data/
│   │   └── ProductImageResponse.php            # Response DTO implementation
│   └── ProductImageManagement.php              # Core business logic
├── etc/
│   ├── di.xml                                  # Interface → implementation bindings
│   ├── module.xml                              # Module declaration
│   └── webapi.xml                             # REST route definition
├── composer.json
├── registration.php
└── README.md
```

---

## Allowed Image Extensions

`jpg`, `jpeg`, `png`, `gif`, `webp`

---

## Error Responses

| Scenario                            | `success` | `message`                                        |
|-------------------------------------|-----------|--------------------------------------------------|
| Product SKU not found               | `false`   | `Product with SKU "X" was not found.`            |
| `imagePath` is not absolute         | `false`   | `imagePath must be an absolute path. Received: …`|
| File does not exist                 | `false`   | `Source image file does not exist: "…"`          |
| File not readable                   | `false`   | `Source image file is not readable: "…"`         |
| Unsupported file extension          | `false`   | `File extension ".X" is not allowed. …`          |
| Copy failure                        | `false`   | `Failed to copy image from "…" to "…".`          |

---

## Compatibility

| Component   | Version    |
|-------------|------------|
| Magento     | 2.4.x      |
| PHP         | 8.1+       |
