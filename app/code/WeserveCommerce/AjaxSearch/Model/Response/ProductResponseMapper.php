<?php
namespace WeserveCommerce\AjaxSearch\Model\Response;

use WeserveCommerce\AjaxSearch\Api\ProductResponseMapperInterface;
use Magento\Store\Model\StoreManagerInterface;
use WeserveCommerce\AjaxSearch\Model\Config;

class ProductResponseMapper implements ProductResponseMapperInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        StoreManagerInterface $storeManager,
        Config $config
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * @param array $hit
     * @param int $storeId
     * @return array
     */
    public function map(array $hit, int $storeId): array
    {
        $source = $hit['_source'] ?? [];
        $id = $this->getStringValue($hit['_id'] ?? $source['id'] ?? '');
        $sku = $this->getStringValue($source['sku'] ?? '');
        $name = $this->getStringValue($source['name'] ?? '');
        $urlKey = $this->getStringValue($source['url_key'] ?? '');

        // Determine price
        $price = 0.0;
        if (isset($source['price']) && is_numeric($source['price'])) {
            $price = (float)$source['price'];
        } elseif (isset($source['customprice']) && is_numeric($source['customprice'])) {
            $price = (float)$source['customprice'] / 100.0;
        } else {
            $storePriceKey = sprintf('price_0_%d', $storeId);
            if (isset($source[$storePriceKey]) && is_numeric($source[$storePriceKey])) {
                $price = (float)$source[$storePriceKey];
            } else {
                foreach ($source as $k => $v) {
                    if (strpos($k, 'price_') === 0 && is_numeric($v)) {
                        $price = (float)$v;
                        break;
                    }
                }
            }
        }

        $specialPrice = null;
        if (isset($source['special_price']) && is_numeric($source['special_price'])) {
            $candidateSpecialPrice = (float)$source['special_price'];
            if ($candidateSpecialPrice > 0 && $candidateSpecialPrice < $price) {
                $specialPrice = $candidateSpecialPrice;
            }
        }

        // Resolve Product URL
        try {
            $baseUrl = $this->storeManager->getStore($storeId)->getBaseUrl();
            if (!empty($urlKey)) {
                $productUrl = rtrim($baseUrl, '/') . '/' . ltrim($urlKey, '/') . '.html';
            } else {
                $productUrl = rtrim($baseUrl, '/') . '/catalog/product/view/id/' . $id;
            }
        } catch (\Exception $e) {
            $productUrl = '#' . $id;
        }

        // Resolve Image URL
        $imagePath = $source['small_image'] ?? $source['thumbnail'] ?? $source['image'] ?? null;
        $imageUrl = null;
        if (!empty($imagePath) && $imagePath !== 'no_selection') {
            try {
                $mediaUrl = $this->storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
                $imageUrl = rtrim($mediaUrl, '/') . '/catalog/product' . (strpos($imagePath, '/') === 0 ? '' : '/') . $imagePath;
            } catch (\Exception $e) {
                $imageUrl = null;
            }
        }

        $brand = $source['brand_value'] ?? $source['brand'] ?? '';
        if (is_array($brand)) {
            $brand = '';
        }
        $attributes = [];
        foreach ($this->config->getAutocompleteAttributes($storeId) as $attributeCode) {
            if (array_key_exists($attributeCode, $source)) {
                $attributes[$attributeCode] = $source[$attributeCode];
            }
        }

        return [
            'sku' => $sku,
            'name' => $name,
            'price' => $price,
            'special_price' => $specialPrice,
            'url' => $productUrl,
            'image' => $imageUrl,
            'brand' => (string)$brand,
            'attributes' => $attributes ? $attributes : new \stdClass()
        ];
    }

    private function getStringValue($value): string
    {
        if (is_array($value)) {
            return '';
        }

        return (string)($value ?? '');
    }
}
