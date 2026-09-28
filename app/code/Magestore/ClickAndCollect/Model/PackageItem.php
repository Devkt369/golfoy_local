<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magestore\ClickAndCollectApi\Api\Data\PackageItemInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageItemExtensionInterface;
use Magestore\ClickAndCollect\Model\ResourceModel\PackageItem as PackageItemResourceModel;

/**
 * Class PackageItem
 *
 * Used for Package Item model
 */
class PackageItem extends AbstractExtensibleModel implements PackageItemInterface
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(PackageItemResourceModel::class);
    }

    /**
     * @inheritdoc
     */
    public function getPackageItemId(): ?int
    {
        return (int)$this->getData(self::PACKAGE_ITEM_ID);
    }

    /**
     * @inheritdoc
     */
    public function setPackageItemId(?int $packageItemId): void
    {
        $this->setData(self::PACKAGE_ITEM_ID, $packageItemId);
    }

    /**
     * @inheritdoc
     */
    public function getPackageId(): ?int
    {
        return (int)$this->getData(self::PACKAGE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setPackageId(?int $packageId): void
    {
        $this->setData(self::PACKAGE_ID, $packageId);
    }

    /**
     * @inheritdoc
     */
    public function getItemId(): ?int
    {
        return (int)$this->getData(self::ITEM_ID);
    }

    /**
     * @inheritdoc
     */
    public function setItemId(?int $itemId): void
    {
        $this->setData(self::ITEM_ID, $itemId);
    }

    /**
     * @inheritdoc
     */
    public function getParentItemId(): ?int
    {
        return (int)$this->getData(self::PARENT_ITEM_ID);
    }

    /**
     * @inheritdoc
     */
    public function setParentItemId(?int $parentItemId): void
    {
        $this->setData(self::PARENT_ITEM_ID, $parentItemId);
    }

    /**
     * @inheritdoc
     */
    public function getProductId(): ?int
    {
        return (int)$this->getData(self::PRODUCT_ID);
    }

    /**
     * @inheritdoc
     */
    public function setProductId(?int $productId): void
    {
        $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritdoc
     */
    public function getProductSku(): ?string
    {
        return $this->getData(self::PRODUCT_SKU);
    }

    /**
     * @inheritdoc
     */
    public function setProductSku(?string $productSku): void
    {
        $this->setData(self::PRODUCT_SKU, $productSku);
    }

    /**
     * @inheritdoc
     */
    public function getProductName(): ?string
    {
        return $this->getData(self::PRODUCT_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setProductName(?string $productName): void
    {
        $this->setData(self::PRODUCT_NAME, $productName);
    }

    /**
     * @inheritdoc
     */
    public function getPackedQty(): ?float
    {
        return (float)$this->getData(self::PACKED_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setPackedQty(?float $packedQty): void
    {
        $this->setData(self::PACKED_QTY, $packedQty);
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): ?PackageItemExtensionInterface
    {
        return $this->getData(self::EXTENSION_ATTRIBUTES_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(PackageItemExtensionInterface $extensionAttributes): void
    {
        $this->setData(self::EXTENSION_ATTRIBUTES_KEY, $extensionAttributes);
    }
}
