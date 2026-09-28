<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magestore\ClickAndCollect\Model\ResourceModel\Package as PackageResourceModel;
use Magestore\ClickAndCollectApi\Api\Data\PackageExtensionInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\Package\AddItemsToNewPackageInterface;
use Magestore\ClickAndCollectApi\Api\Package\ProcessSourceInventoryFromPackageInterface;

/**
 * Class Package
 *
 * Used for Package model
 */
class Package extends AbstractExtensibleModel implements PackageInterface
{
    /**
     * @var AddItemsToNewPackageInterface
     */
    private $addItemsToNewPackage;

    /**
     * @var ProcessSourceInventoryFromPackageInterface
     */
    private $processSourceInventoryFromPackage;

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(PackageResourceModel::class);
        $this->addItemsToNewPackage = ObjectManager::getInstance()
            ->create(AddItemsToNewPackageInterface::class);
        $this->processSourceInventoryFromPackage = ObjectManager::getInstance()
            ->create(ProcessSourceInventoryFromPackageInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): ?PackageExtensionInterface
    {
        return $this->getData(self::EXTENSION_ATTRIBUTES_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(PackageExtensionInterface $extensionAttributes): void
    {
        $this->setData(self::EXTENSION_ATTRIBUTES_KEY, $extensionAttributes);
    }

    /**
     * @inheritdoc
     */
    public function getPackageId(): ?int
    {
        return $this->getData(self::PACKAGE_ID) ?
            (int) $this->getData(self::PACKAGE_ID)
            : null;
    }

    /**
     * @inheritdoc
     */
    public function setPackageId(?int $packageId)
    {
        return $this->setData(self::PACKAGE_ID, $packageId);
    }

    /**
     * @inheritdoc
     */
    public function getOrderId(): ?int
    {
        return $this->getData(self::ORDER_ID) ?
            (int) $this->getData(self::ORDER_ID)
            : null;
    }

    /**
     * @inheritdoc
     */
    public function setOrderId(?int $orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritdoc
     */
    public function getLocationId(): ?int
    {
        return $this->getData(self::LOCATION_ID) ?
            (int) $this->getData(self::LOCATION_ID)
            : null;
    }

    /**
     * @inheritdoc
     */
    public function setLocationId(?int $locationId)
    {
        return $this->setData(self::LOCATION_ID, $locationId);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus(?string $status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSave()
    {
        if ($this->isObjectNew()) {
            $this->addItemsToNewPackage->execute($this);
        }
        return parent::afterSave();
    }
}
