<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Model;

use Magento\Framework\Model\AbstractModel;
use ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusMapping as ResourceModel;

/**
 * @method int    getMappingId()
 * @method string getRawStatus()
 * @method string getDisplayLabel()
 * @method string getCarrierName()
 * @method int    getIsActive()
 * @method int    getIsMapped()
 * @method string getCreatedAt()
 * @method string getUpdatedAt()
 * @method self   setRawStatus(string $v)
 * @method self   setDisplayLabel(string $v)
 * @method self   setCarrierName(string $v)
 * @method self   setIsActive(int $v)
 * @method self   setIsMapped(int $v)
 */
class TrackingStatusMapping extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }
}
