<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\ViewModel;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Helper\Product\Compare as CompareHelper;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Helper\Cart as CartHelper;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Escaper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Helper\Data as WishlistHelper;

class ProductCard implements ArgumentInterface
{
    private array $priceIndexCache = [];
    private array $effectivePriceProductCache = [];

    public function __construct(
        private readonly ImageHelper $imageHelper,
        private readonly PriceHelper $priceHelper,
        private readonly CartHelper $cartHelper,
        private readonly WishlistHelper $wishlistHelper,
        private readonly CompareHelper $compareHelper,
        private readonly ResourceConnection $resource,
        private readonly FormKey $formKey,
        private readonly Escaper $escaper,
        private readonly StoreManagerInterface $storeManager,
        private readonly HttpContext $httpContext,
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * `$role` is the slider's configured "Image Role" — a product
     * *attribute code* (small_image, thumbnail, image, ...), not a
     * Magento image type id. `ImageHelper::init()` requires the second
     * argument to be a defined image type from view.xml (for its
     * width/height/placeholder config) — passing an attribute code there
     * directly leaves that config unresolved and produces a broken
     * placeholder URL (".../placeholder/.jpg"). So a real image type
     * (`product_base_image`) is used for sizing/placeholder purposes, and
     * `setImageFile()` explicitly points it at the file from the
     * configured attribute.
     */
    public function getImageUrl(Product $product, string $role = 'small_image'): string
    {
        try {
            $image = $this->imageHelper->init($product, 'product_base_image');

            $imageFile = trim((string)$product->getData($role));
            if ($imageFile !== '' && $imageFile !== 'no_selection') {
                $image->setImageFile($imageFile);
            }

            return (string)$image->getUrl();
        } catch (\Throwable) {
            return '';
        }
    }

    public function getHoverImageUrl(Product $product): string
    {
        $thumbnail = (string)$product->getData('thumbnail');
        if ($thumbnail !== '' && $thumbnail !== 'no_selection') {
            try {
                return (string)$this->imageHelper->init($product, 'product_thumbnail_image')->getUrl();
            } catch (\Throwable) {
            }
        }

        return '';
    }

    public function getFinalPrice(Product $product): float
    {
        $product = $this->resolvePricingProduct($product);
        $regular = $this->getRegularPrice($product);

        $indexRow = $this->getPriceIndexRow($product);
        if ($indexRow !== null && $indexRow['final_price'] !== null) {
            $finalFromIndex = (float)$indexRow['final_price'];
            // A stale/broken index can report final_price as 0 (or above the
            // regular price) for products that have no real discount — never
            // treat that as a legitimate 100% off.
            if ($finalFromIndex > 0 && $finalFromIndex <= $regular) {
                return $finalFromIndex;
            }
        }

        $special = $this->getActiveSpecialPrice($product);
        if ($special !== null && $special > 0 && $special < $regular) {
            return $special;
        }

        return $regular;
    }

    public function getRegularPrice(Product $product): float
    {
        $product = $this->resolvePricingProduct($product);
        $indexRow = $this->getPriceIndexRow($product);
        if ($indexRow !== null && $indexRow['price'] !== null && (float)$indexRow['price'] > 0) {
            return (float)$indexRow['price'];
        }

        // Raw `price` attribute — same base-currency scale as special_price,
        // so the discount comparison in getFinalPrice() stays consistent.
        $price = (float)$product->getData('price');
        if ($price > 0) {
            return $price;
        }

        // Last resort — the type-computed price (e.g. min child price for
        // configurable/bundle, or already-discounted price if nothing else
        // is available).
        return (float)$product->getFinalPrice();
    }

    /**
     * Configurable products carry no meaningful price/special_price of their
     * own (their own `price` attribute is normally 0/unset — the real price
     * lives on the child simple products). For pricing purposes, resolve to
     * the first associated child product instead.
     */
    private function resolvePricingProduct(Product $product): Product
    {
        if ($product->getTypeId() !== 'configurable') {
            return $product;
        }

        $parentId = (int)$product->getId();
        if ($parentId <= 0) {
            return $product;
        }

        if (array_key_exists($parentId, $this->effectivePriceProductCache)) {
            return $this->effectivePriceProductCache[$parentId];
        }

        $effective = $product;
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('catalog_product_super_link');
            $childId = (int)$connection->fetchOne(
                $connection->select()
                    ->from($table, ['product_id'])
                    ->where('parent_id = ?', $parentId)
                    ->order('product_id ASC')
                    ->limit(1)
            );

            if ($childId > 0) {
                $child = $this->productRepository->getById($childId, false, $product->getStoreId());
                if ($child && $child->getId()) {
                    $effective = $child;
                }
            }
        } catch (\Throwable) {
        }

        $this->effectivePriceProductCache[$parentId] = $effective;

        return $effective;
    }

    /**
     * Reads regular/final price straight from the catalog price index
     * (catalog_product_index_price), scoped to the current website and
     * customer group. This is the same table Magento's own listing/grid
     * pages use, so it sidesteps EAV attribute-loading/scope quirks that
     * can leave `price`/`special_price` unset on a lazily-loaded collection.
     */
    private function getPriceIndexRow(Product $product): ?array
    {
        $productId = (int)$product->getId();
        if ($productId <= 0) {
            return null;
        }

        if (array_key_exists($productId, $this->priceIndexCache)) {
            return $this->priceIndexCache[$productId];
        }

        $result = null;
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('catalog_product_index_price');
            $websiteId = (int)$this->storeManager->getStore()->getWebsiteId();
            $customerGroupId = (int)$this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);

            $select = $connection->select()
                ->from($table, ['price', 'final_price'])
                ->where('entity_id = ?', $productId)
                ->where('website_id = ?', $websiteId)
                ->where('customer_group_id = ?', $customerGroupId);

            $row = $connection->fetchRow($select);
            if ($row) {
                $result = [
                    'price' => $row['price'] !== null ? (float)$row['price'] : null,
                    'final_price' => $row['final_price'] !== null ? (float)$row['final_price'] : null,
                ];
            }
        } catch (\Throwable) {
            $result = null;
        }

        $this->priceIndexCache[$productId] = $result;

        return $result;
    }

    /**
     * Returns the product's special_price only if it is currently within its
     * special_from_date/special_to_date window (or has no date restriction).
     */
    private function getActiveSpecialPrice(Product $product): ?float
    {
        $special = (float)$product->getData('special_price');
        if ($special <= 0) {
            return null;
        }

        $now = new \DateTime('now');

        $from = trim((string)$product->getData('special_from_date'));
        if ($from !== '') {
            try {
                if ($now < new \DateTime($from)) {
                    return null;
                }
            } catch (\Throwable) {
            }
        }

        $to = trim((string)$product->getData('special_to_date'));
        if ($to !== '') {
            try {
                $toDate = new \DateTime($to);
                $toDate->setTime(23, 59, 59);
                if ($now > $toDate) {
                    return null;
                }
            } catch (\Throwable) {
            }
        }

        return $special;
    }

    public function formatPrice(float $value): string
    {
        return (string)$this->priceHelper->currencyByStore($value, null, true, false);
    }

    public function getDiscountPercent(Product $product): ?int
    {
        $regular = $this->getRegularPrice($product);
        $final = $this->getFinalPrice($product);
        if ($regular <= 0 || $final >= $regular) {
            return null;
        }

        return (int)round((($regular - $final) / $regular) * 100);
    }

    public function getReviewSummary(Product $product): array
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('review_entity_summary');
        $select = $connection->select()
            ->from($table, ['rating_summary', 'reviews_count'])
            ->where('entity_pk_value = ?', (int)$product->getId())
            ->order('store_id DESC')
            ->limit(1);
        $row = (array)$connection->fetchRow($select);

        return [
            'rating_summary' => (int)($row['rating_summary'] ?? 0),
            'reviews_count' => (int)($row['reviews_count'] ?? 0),
        ];
    }

    public function getStockLabel(Product $product): string
    {
        return $product->isSaleable() ? 'In stock' : 'Out of stock';
    }

    public function getAddToCartUrl(Product $product): string
    {
        return (string)$this->cartHelper->getAddUrl($product);
    }

    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    public function getWishlistUrl(Product $product): string
    {
        return (string)$this->wishlistHelper->getAddParams($product);
    }

    public function getComparePostData(Product $product): string
    {
        return (string)$this->compareHelper->getPostDataParams($product);
    }

    public function getCustomAttributes(Product $product, array $attributeCodes): array
    {
        $attributes = [];
        foreach ($attributeCodes as $code) {
            $attribute = $product->getResource()->getAttribute($code);
            if (!$attribute) {
                continue;
            }

            $value = $product->getAttributeText($code);
            if (is_array($value)) {
                $value = implode(', ', array_filter(array_map('strval', $value)));
            }
            if ($value === null || $value === '') {
                $value = $product->getData($code);
            }
            if ($value === null || $value === '' || $value === 'No') {
                continue;
            }

            $attributes[] = [
                'label' => (string)$attribute->getStoreLabel(),
                'value' => (string)$value,
            ];
        }

        return $attributes;
    }

    /**
     * Returns the product name with its Brand attribute value stripped out
     * (case-insensitive, whole-word match), when $stripBrand is true.
     * e.g. "Cobra Aerojet One Length (5-S) Graphite Irons" + brand "Cobra"
     * -> "Aerojet One Length (5-S) Graphite Irons".
     */
    public function getDisplayName(Product $product, bool $stripBrand): string
    {
        $name = (string)$product->getName();
        if (!$stripBrand) {
            return $name;
        }

        $brandRow = $this->getCustomAttributes($product, ['brand']);
        $brand = trim((string)($brandRow[0]['value'] ?? ''));
        if ($brand === '') {
            return $name;
        }

        $pattern = '/\b' . preg_quote($brand, '/') . '\b\s*/iu';
        $stripped = trim((string)preg_replace($pattern, '', $name, 1));

        return $stripped !== '' ? $stripped : $name;
    }

    public function getShortDescriptionHtml(Product $product): string
    {
        $shortDescription = trim((string)$product->getShortDescription());
        if ($shortDescription === '') {
            return '';
        }

        $decoded = html_entity_decode($shortDescription, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Keep common formatting tags, but do not process template directives.
        return (string)$this->escaper->escapeHtml($decoded, ['a', 'b', 'br', 'em', 'i', 'li', 'ol', 'p', 'span', 'strong', 'u', 'ul']);
    }
}
