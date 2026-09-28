<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementApi\Api\Data;

/**
 * Represents Inventory Movement
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface InventoryMovementInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const INVENTORY_MOVEMENT_ID = 'inventory_movement_id';
    const SOURCE_CODE = 'source_code';
    const SOURCE_NAME = 'source_name';
    const CREATED_AT = 'created_at';
    const IS_IN_STOCK = 'is_in_stock';
    const PRODUCT_ID = 'product_id';
    const PRODUCT_NAME = 'product_name';
    const PRODUCT_SKU = 'product_sku';
    const ACTION_TYPE = 'action_type';
    const OLD_QTY = 'old_qty';
    const NEW_QTY = 'new_qty';
    const CHANGE_QTY = 'change_qty';
    const METADATA = 'metadata';
    const NOTE = 'note';
    const USER_TYPE = 'user_type';
    const USER_NAME = 'user_name';
    const USER_ID = 'user_id';
    const STATUS = 'status';
    /**#@-*/

    /**
     * Constants for keys of metadata
     */
    const ACTION_LABEL = 'action_label';
    const OBJECT_TYPE = 'object_type';
    const OBJECT_ID = 'object_id';
    const OBJECT_INCREMENT_ID = 'object_increment_id';
    /**#@-*/

    /**
     * Constants for status
     */
    const STATUS_FAIL = 0;
    const STATUS_SUCCESS = 1;
    /**#@-*/

    /**
     * Get inventory movement id
     *
     * @return string|null
     */
    public function getInventoryMovementId(): ?string;

    /**
     * Set inventory movement id
     *
     * @param string|null $inventoryMovementId
     * @return void
     */
    public function setInventoryMovementId(?string $inventoryMovementId): void;

    /**
     * Get source code
     *
     * @return string|null
     */
    public function getSourceCode(): ?string;

    /**
     * Set source code
     *
     * @param string|null $sourceCode
     * @return void
     */
    public function setSourceCode(?string $sourceCode): void;

    /**
     * Get source name
     *
     * @return string|null
     */
    public function getSourceName(): ?string;

    /**
     * Set source name
     *
     * @param string|null $sourceName
     * @return void
     */
    public function setSourceName(?string $sourceName): void;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string|null $createdAt
     * @return void
     */
    public function setCreatedAt(?string $createdAt): void;

    /**
     * Get is in stock
     *
     * @return int|null
     */
    public function getIsInStock(): ?int;

    /**
     * Set is in stock
     *
     * @param int|null $isInStock
     * @return void
     */
    public function setIsInStock(?int $isInStock): void;

    /**
     * Get product id
     *
     * @return int|null
     */
    public function getProductId(): ?int;

    /**
     * Set product id
     *
     * @param int|null $productId
     * @return void
     */
    public function setProductId(?int $productId): void;

    /**
     * Get product name
     *
     * @return string|null
     */
    public function getProductName(): ?string;

    /**
     * Set product name
     *
     * @param string|null $productName
     * @return void
     */
    public function setProductName(?string $productName): void;

    /**
     * Get product sku
     *
     * @return string|null
     */
    public function getProductSku(): ?string;

    /**
     * Set product sku
     *
     * @param string|null $productSku
     * @return void
     */
    public function setProductSku(?string $productSku): void;

    /**
     * Get action type
     *
     * @return string|null
     */
    public function getActionType(): ?string;

    /**
     * Set action type
     *
     * @param string|null $actionType
     * @return void
     */
    public function setActionType(?string $actionType): void;

    /**
     * Get old qty
     *
     * @return float|null
     */
    public function getOldQty(): ?float;

    /**
     * Set old qty
     *
     * @param float|null $oldQty
     * @return void
     */
    public function setOldQty(?float $oldQty): void;

    /**
     * Get new qty
     *
     * @return float|null
     */
    public function getNewQty(): ?float;

    /**
     * Set new qty
     *
     * @param float|null $newQty
     * @return void
     */
    public function setNewQty(?float $newQty): void;

    /**
     * Get change qty
     *
     * @return float|null
     */
    public function getChangeQty(): ?float;

    /**
     * Set change qty
     *
     * @param float|null $changeQty
     * @return void
     */
    public function setChangeQty(?float $changeQty): void;

    /**
     * Get metadata
     *
     * @return string|null
     */
    public function getMetadata(): ?string;

    /**
     * Set metadata
     *
     * @param string|null $metadata
     * @return void
     */
    public function setMetadata(?string $metadata): void;

    /**
     * Get note
     *
     * @return string|null
     */
    public function getNote(): ?string;

    /**
     * Set note
     *
     * @param string|null $note
     * @return void
     */
    public function setNote(?string $note): void;

    /**
     * Get user type
     *
     * @return string|null
     */
    public function getUserType(): ?string;

    /**
     * Set user type
     *
     * @param string|null $userType
     * @return void
     */
    public function setUserType(?string $userType): void;

    /**
     * Get user name
     *
     * @return string|null
     */
    public function getUserName(): ?string;

    /**
     * Set user name
     *
     * @param string|null $userName
     * @return void
     */
    public function setUserName(?string $userName): void;

    /**
     * Get user id
     *
     * @return int|null
     */
    public function getUserId(): ?int;

    /**
     * Set user id
     *
     * @param int|null $userId
     * @return void
     */
    public function setUserId(?int $userId): void;

    /**
     * Get status
     *
     * @return int|null
     */
    public function getStatus(): ?int;

    /**
     * Set status
     *
     * @param int|null $status
     * @return void
     */
    public function setStatus(?int $status): void;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magestore\InventoryMovementApi\Api\Data\InventoryMovementExtensionInterface|null
     */
    public function getExtensionAttributes()
        : ?\Magestore\InventoryMovementApi\Api\Data\InventoryMovementExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magestore\InventoryMovementApi\Api\Data\InventoryMovementExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magestore\InventoryMovementApi\Api\Data\InventoryMovementExtensionInterface $extensionAttributes
    ): void;
}
