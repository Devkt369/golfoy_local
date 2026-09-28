<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model;

use Magento\Framework\Model\AbstractModel;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementExtensionInterface;
use Magestore\InventoryMovement\Model\ResourceModel\InventoryMovement as InventoryMovementResource;

/**
 * Class InventoryMovement
 *
 * Used for Inventory Movement model
 */
class InventoryMovement extends AbstractModel implements InventoryMovementInterface
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(InventoryMovementResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getInventoryMovementId(): ?string
    {
        return $this->getData(self::INVENTORY_MOVEMENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setInventoryMovementId(?string $inventoryMovementId): void
    {
        $this->setData(self::INVENTORY_MOVEMENT_ID, $inventoryMovementId);
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): InventoryMovementExtensionInterface
    {
        return $this->getData(self::EXTENSION_ATTRIBUTES_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(InventoryMovementExtensionInterface $extensionAttributes): void
    {
        $this->setData(self::EXTENSION_ATTRIBUTES_KEY, $extensionAttributes);
    }

    /**
     * @inheritDoc
     */
    public function getSourceCode(): ?string
    {
        return $this->getData(self::SOURCE_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setSourceCode(?string $sourceCode): void
    {
        $this->setData(self::SOURCE_CODE, $sourceCode);
    }

    /**
     * @inheritDoc
     */
    public function getSourceName(): ?string
    {
        return $this->getData(self::SOURCE_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setSourceName(?string $sourceName): void
    {
        $this->setData(self::SOURCE_NAME, $sourceName);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(?string $createdAt): void
    {
        $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getIsInStock(): ?int
    {
        return $this->getData(self::IS_IN_STOCK);
    }

    /**
     * @inheritDoc
     */
    public function setIsInStock(?int $isInStock): void
    {
        $this->setData(self::IS_IN_STOCK, $isInStock);
    }

    /**
     * @inheritDoc
     */
    public function getProductId(): ?int
    {
        return $this->getData(self::PRODUCT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setProductId(?int $productId): void
    {
        $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * @inheritDoc
     */
    public function getProductName(): ?string
    {
        return $this->getData(self::PRODUCT_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setProductName(?string $productName): void
    {
        $this->setData(self::PRODUCT_NAME, $productName);
    }

    /**
     * @inheritDoc
     */
    public function getProductSku(): ?string
    {
        return $this->getData(self::PRODUCT_SKU);
    }

    /**
     * @inheritDoc
     */
    public function setProductSku(?string $productSku): void
    {
        $this->setData(self::PRODUCT_SKU, $productSku);
    }

    /**
     * @inheritDoc
     */
    public function getActionType(): ?string
    {
        return $this->getData(self::ACTION_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setActionType(?string $actionType): void
    {
        $this->setData(self::ACTION_TYPE, $actionType);
    }

    /**
     * @inheritDoc
     */
    public function getOldQty(): ?float
    {
        return $this->getData(self::OLD_QTY);
    }

    /**
     * @inheritDoc
     */
    public function setOldQty(?float $oldQty): void
    {
        $this->setData(self::OLD_QTY, $oldQty);
    }

    /**
     * @inheritDoc
     */
    public function getNewQty(): ?float
    {
        return $this->getData(self::NEW_QTY);
    }

    /**
     * @inheritDoc
     */
    public function setNewQty(?float $newQty): void
    {
        $this->setData(self::NEW_QTY, $newQty);
    }

    /**
     * @inheritDoc
     */
    public function getChangeQty(): ?float
    {
        return $this->getData(self::CHANGE_QTY);
    }

    /**
     * @inheritDoc
     */
    public function setChangeQty(?float $changeQty): void
    {
        $this->setData(self::CHANGE_QTY, $changeQty);
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(): ?string
    {
        return $this->getData(self::METADATA);
    }

    /**
     * @inheritDoc
     */
    public function setMetadata(?string $metadata): void
    {
        $this->setData(self::METADATA, $metadata);
    }

    /**
     * @inheritDoc
     */
    public function getNote(): ?string
    {
        return $this->getData(self::NOTE);
    }

    /**
     * @inheritDoc
     */
    public function setNote(?string $note): void
    {
        $this->setData(self::NOTE, $note);
    }

    /**
     * @inheritDoc
     */
    public function getUserType(): ?string
    {
        return $this->getData(self::USER_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setUserType(?string $userType): void
    {
        $this->setData(self::USER_TYPE, $userType);
    }

    /**
     * @inheritDoc
     */
    public function getUserName(): ?string
    {
        return $this->getData(self::USER_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setUserName(?string $userName): void
    {
        $this->setData(self::USER_NAME, $userName);
    }

    /**
     * @inheritDoc
     */
    public function getUserId(): ?int
    {
        return $this->getData(self::USER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setUserId(?int $userId): void
    {
        $this->setData(self::USER_ID, $userId);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): ?int
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(?int $status): void
    {
        $this->setData(self::STATUS, $status);
    }
}
