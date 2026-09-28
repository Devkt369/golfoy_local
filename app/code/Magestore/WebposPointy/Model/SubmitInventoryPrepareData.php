<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Model;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Magestore\WebposPointy\Api\AttributeDataProviderInterface;
use Magestore\WebposPointy\Api\PointyConfigManagementInterface;
use Magestore\WebposPointy\Api\Request\PointyInventoryInterface;
use Magestore\WebposPointy\Api\SubmitInventoryPrepareDataInterface;
use Magestore\WebposPointy\Log\Logger;
use Magestore\WebposPointy\Model\ResourceModel\Inventory\Inventory as InventoryResource;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

/**
 * Prepare data for submitting inventory
 */
class SubmitInventoryPrepareData implements SubmitInventoryPrepareDataInterface
{
    /**
     * @var PointyConfigManagementInterface
     */
    protected $pointyConfigManagement;

    /**
     * @var AttributeDataProviderInterface
     */
    protected $attributeDataProvider;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var InventoryResource
     */
    protected $inventoryResource;

    /**
     * SubmitInventoryPrepareData constructor.
     * @param PointyConfigManagementInterface $pointyConfigManagement
     * @param AttributeDataProviderInterface $attributeDataProvider
     * @param Logger $logger
     * @param InventoryResource $inventoryResource
     */
    public function __construct(
        PointyConfigManagementInterface $pointyConfigManagement,
        AttributeDataProviderInterface $attributeDataProvider,
        Logger $logger,
        InventoryResource $inventoryResource
    ) {
        $this->pointyConfigManagement = $pointyConfigManagement;
        $this->attributeDataProvider = $attributeDataProvider;
        $this->logger = $logger;
        $this->inventoryResource = $inventoryResource;
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(LocationInterface $location): array
    {
        $result = [
            self::PRODUCTS => [],
            self::DELETED_PRODUCTS => []
        ];
        $codeAttribute = $this->pointyConfigManagement->getManufacturerBarcodeAttribute();
        if (!$codeAttribute) {
            return $result;
        }
        $attributeData = $this->attributeDataProvider->execute($this->getListAttributes());
        $result[self::PRODUCTS] = $this->getProductsData($location, $attributeData);
        $result[self::DELETED_PRODUCTS] = $this->getDeletedProductsData($location, $attributeData);
        return $result;
    }

    /**
     * Get Products Data
     *
     * @param LocationInterface $location
     * @param array $attributeData
     * @return array
     */
    public function getProductsData(LocationInterface $location, array $attributeData): array
    {
        $codeAttribute = $this->pointyConfigManagement->getManufacturerBarcodeAttribute();
        $products = $this->inventoryResource->getProducts($location);

        $data = [];
        $packItems = 0;
        $i = 0;

        foreach ($products as $product) {
            $productId = $product['entity_id'];
            if (!isset($attributeData[$productId])
                || (int)$attributeData[$productId][ProductInterface::STATUS] == ProductStatus::STATUS_DISABLED
                || ($codeAttribute != ProductInterface::SKU && empty($attributeData[$productId][$codeAttribute]))
            ) {
                continue;
            }

            $item = [
                PointyInventoryInterface::CODE => $codeAttribute == ProductInterface::SKU ?
                    $product[ProductInterface::SKU] : $attributeData[$productId][$codeAttribute],
                PointyInventoryInterface::PRICE => $attributeData[$productId][ProductInterface::PRICE] ?? 0,
                PointyInventoryInterface::QUANTITY_ON_HAND => 10,
                PointyInventoryInterface::ITEM_ID => $product[ProductInterface::SKU]
            ];
            if (isset($attributeData[$productId][ProductAttributeInterface::CODE_COST])) {
                $item[PointyInventoryInterface::PURCHASE_COST] =
                    $attributeData[$productId][ProductAttributeInterface::CODE_COST];
            }
            $data[$packItems][] = $item;

            $i++;
            if ($i == 200) {
                $i = 0;
                $packItems++;
            }
        }
        return $data;
    }

    /**
     * Get Deleted Products Data
     *
     * @param LocationInterface $location
     * @param array $attributeData
     * @return array
     */
    public function getDeletedProductsData(LocationInterface $location, array $attributeData): array
    {
        $codeAttribute = $this->pointyConfigManagement->getManufacturerBarcodeAttribute();
        $products = $this->inventoryResource->getDeletedProducts($location);

        $data = [];
        $packItems = 0;
        $i = 0;

        foreach ($products as $product) {
            $productId = $product['entity_id'];
            if (!isset($attributeData[$productId])
                || ($codeAttribute != ProductInterface::SKU && empty($attributeData[$productId][$codeAttribute]))
            ) {
                continue;
            }

            $item = [
                PointyInventoryInterface::CODE => $codeAttribute == ProductInterface::SKU ?
                    $product[ProductInterface::SKU] : $attributeData[$productId][$codeAttribute],
                PointyInventoryInterface::PRICE => $attributeData[$productId][ProductInterface::PRICE],
                PointyInventoryInterface::QUANTITY_ON_HAND => 0,
                PointyInventoryInterface::ITEM_ID => $product[ProductInterface::SKU]
            ];
            if (isset($attributeData[$productId][ProductAttributeInterface::CODE_COST])) {
                $item[PointyInventoryInterface::PURCHASE_COST] =
                    $attributeData[$productId][ProductAttributeInterface::CODE_COST];
            }
            $data[$packItems][] = $item;

            $i++;
            if ($i == 200) {
                $i = 0;
                $packItems++;
            }
        }
        return $data;
    }

    /**
     * Get List Attributes
     *
     * @return array
     */
    public function getListAttributes()
    {
        $attributes = [
            ProductInterface::STATUS,
            ProductInterface::PRICE,
            ProductAttributeInterface::CODE_COST
        ];
        $codeAttribute = $this->pointyConfigManagement->getManufacturerBarcodeAttribute();
        if ($codeAttribute != ProductInterface::SKU) {
            $attributes[] = $codeAttribute;
        }
        return $attributes;
    }
}
