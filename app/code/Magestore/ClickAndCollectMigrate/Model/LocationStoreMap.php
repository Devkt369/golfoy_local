<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Model;

use Magento\Framework\Model\AbstractModel;
use Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface;
use Magestore\ClickAndCollectMigrate\Model\ResourceModel\LocationStoreMap as ResourceModel;

/**
 * Pos Location And Store Pickup Map Model
 */
class LocationStoreMap extends AbstractModel implements LocationStoreMapInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'webpos_location_storepickup_store_map_model';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
    
    /**
     * @inheritdoc
     */
    public function getStorepickupId(): ?int
    {
        return (int) $this->getData(self::STOREPICKUP_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStorepickupId(?int $storepickupId)
    {
        return $this->setData(self::STOREPICKUP_ID, $storepickupId);
    }

    /**
     * @inheritdoc
     */
    public function getSourceCode(): ?string
    {
        return $this->getData(self::SOURCE_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setSourceCode(?string $sourceCode)
    {
        return $this->setData(self::SOURCE_CODE, $sourceCode);
    }

    /**
     * @inheritdoc
     */
    public function getStockId(): ?int
    {
        return $this->getData(self::STOCK_ID) ? (int) $this->getData(self::STOCK_ID) : null;
    }

    /**
     * @inheritdoc
     */
    public function setStockId(?int $stockId)
    {
        return $this->setData(self::STOCK_ID, $stockId);
    }

    /**
     * @inheritdoc
     */
    public function getLocationId(): ?int
    {
        return $this->getData(self::LOCATION_ID) ? (int) $this->getData(self::LOCATION_ID) : null;
    }

    /**
     * @inheritdoc
     */
    public function setLocationId(?int $locationId)
    {
        return $this->setData(self::LOCATION_ID, $locationId);
    }
}
