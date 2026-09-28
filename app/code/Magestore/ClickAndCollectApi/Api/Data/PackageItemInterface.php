<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Data;

/**
 * Represents Package Item
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface PackageItemInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const PACKAGE_ITEM_ID = 'package_item_id';
    const PACKAGE_ID = 'package_id';
    const ITEM_ID = 'item_id';
    const PARENT_ITEM_ID = 'parent_item_id';
    const PRODUCT_ID = 'product_id';
    const PRODUCT_SKU = 'product_sku';
    const PRODUCT_NAME = 'product_name';
    const PACKED_QTY = 'packed_qty';
    /**#@-*/

    /**
     * Get Package Item Id
     *
     * @return int|null
     */
    public function getPackageItemId(): ?int;

    /**
     * Set Package Item Id
     *
     * @param int|null $packageItemId
     * @return void
     */
    public function setPackageItemId(?int $packageItemId): void;

    /**
     * Get Package Id
     *
     * @return int|null
     */
    public function getPackageId(): ?int;

    /**
     * Set Package Id
     *
     * @param int|null $packageId
     * @return void
     */
    public function setPackageId(?int $packageId): void;

    /**
     * Get Item Id
     *
     * @return int|null
     */
    public function getItemId(): ?int;

    /**
     * Set Item Id
     *
     * @param int|null $itemId
     * @return void
     */
    public function setItemId(?int $itemId): void;

    /**
     * Get Parent Item Id
     *
     * @return int|null
     */
    public function getParentItemId(): ?int;

    /**
     * Set Parent Item Id
     *
     * @param int|null $parentItemId
     * @return void
     */
    public function setParentItemId(?int $parentItemId): void;

    /**
     * Get Product Id
     *
     * @return int|null
     */
    public function getProductId(): ?int;

    /**
     * Set Product Id
     *
     * @param int|null $productId
     * @return void
     */
    public function setProductId(?int $productId): void;

    /**
     * Get Product Sku
     *
     * @return string|null
     */
    public function getProductSku(): ?string;

    /**
     * Set Product Sku
     *
     * @param string|null $productSku
     * @return void
     */
    public function setProductSku(?string $productSku): void;

    /**
     * Get Product Name
     *
     * @return string|null
     */
    public function getProductName(): ?string;

    /**
     * Set Product Name
     *
     * @param string|null $productName
     * @return void
     */
    public function setProductName(?string $productName): void;

    /**
     * Get Packed Qty
     *
     * @return float|null
     */
    public function getPackedQty(): ?float;

    /**
     * Set Packed Qty
     *
     * @param float|null $packedQty
     * @return void
     */
    public function setPackedQty(?float $packedQty): void;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magestore\ClickAndCollectApi\Api\Data\PackageItemExtensionInterface|null
     */
    public function getExtensionAttributes()
    : ?\Magestore\ClickAndCollectApi\Api\Data\PackageItemExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\PackageItemExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magestore\ClickAndCollectApi\Api\Data\PackageItemExtensionInterface $extensionAttributes
    ): void;
}
