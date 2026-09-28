<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Model;

use InvalidArgumentException;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Store\Model\StoreManagerInterface;
use Magestore\WebposPointy\Api\AttributeDataProviderInterface;
use Magestore\WebposPointy\Model\ResourceModel\Location\Location as LocationResource;

/**
 * Get data provider for product attribute
 */
class AttributeDataProvider implements AttributeDataProviderInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var EavConfig
     */
    private $eavConfig;
    /**
     * @var LocationResource
     */
    private $locationResource;

    /**
     * AttributeDataProvider constructor.
     * @param StoreManagerInterface $storeManager
     * @param EavConfig $eavConfig
     * @param LocationResource $locationResource
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        EavConfig $eavConfig,
        LocationResource $locationResource
    ) {
        $this->storeManager = $storeManager;
        $this->eavConfig = $eavConfig;
        $this->locationResource = $locationResource;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $attributeCodes): array
    {
        $fields = [];
        $attributeTypes = [];
        $attributeCodesByIds = [];
        $storeId = $this->storeManager->getDefaultStoreView()->getId();
        foreach ($attributeCodes as $attributeCode) {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);

            if ($attribute->getBackendType() == 'static') {
                throw new InvalidArgumentException(
                    __(
                        'You only can get value for attribute '
                        . 'but not static attribute (column in table catalog_product_entity))'
                    )
                );
            } else {
                $attributeTypes[$attribute->getBackendType()][] = [$attribute->getAttributeId()];
                $attributeCodesByIds[$attribute->getAttributeId()] = $attribute->getAttributeCode();
            }
        }
        $products = $this->locationResource->getProductAttributes($storeId, $attributeTypes);
        foreach ($products as $productId => $productData) {
            $fields[$productId] = $this->modifyData($productData, $attributeCodesByIds);
        }

        return $fields;
    }

    /**
     * Modify data
     *
     * @param array $productData
     * @param array $attributes
     * @return array
     */
    public function modifyData(array $productData, array $attributes)
    {
        $result = [];
        foreach ($attributes as $id => $code) {
            if (isset($productData[$id])) {
                $result[$code] = $productData[$id];
            }
        }
        return $result;
    }
}
