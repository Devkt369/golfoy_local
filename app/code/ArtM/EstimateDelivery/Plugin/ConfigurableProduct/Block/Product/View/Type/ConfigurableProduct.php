<?php

declare(strict_types=1);

namespace ArtM\EstimateDelivery\Plugin\ConfigurableProduct\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Store\Model\ScopeInterface;

class ConfigurableProduct
{
    public const GENERAL_SKU_SELECTOR = '.product.attribute.sku .value'; //here you can change SKU Selector.
    public const PRODUCT_VIEW = 'catalog_product_view';

    /**
     * @var JsonSerializer
     */
    private JsonSerializer $jsonSerializer;

    /**
     * @var HttpRequest
     */
    private HttpRequest $httpRequest;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param JsonSerializer $jsonSerializer
     * @param HttpRequest $httpRequest
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        JsonSerializer $jsonSerializer,
        HttpRequest $httpRequest,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->httpRequest = $httpRequest;
        $this->scopeConfig = $scopeConfig;
    }

    /**
 * Add a config to switch SKU
     *
     * @param Configurable $subject
     * @param string $result
     * @return string
     */
    public function afterGetJsonConfig(Configurable $subject, string $result): string
    {
        $jsonConfig = (array)$this->jsonSerializer->unserialize($result);
        $moduleConfig = &$jsonConfig['ArtM_EstimateDelivery'];
        $moduleConfig['data']['full_action_name'] = $this->httpRequest->getFullActionName();
        $moduleConfig['general']['switch_sku_status'] = true; //here you can add enable/disable condition.

        if ($moduleConfig['data']['full_action_name'] === self::PRODUCT_VIEW
            && $moduleConfig['general']['switch_sku_status']) {
            $moduleConfig['general']['sku_selector'] = self::GENERAL_SKU_SELECTOR;
            $parentProduct = $subject->getProduct();
            $moduleConfig['data']['skus']['parent'][$parentProduct->getId()] = $parentProduct->getSku();
                
            foreach ($subject->getAllowProducts() as $child) {
                $moduleConfig['data']['skus'][$child->getId()] = $child->getSku();
            }
        }

        return $this->jsonSerializer->serialize($jsonConfig);
    }
}