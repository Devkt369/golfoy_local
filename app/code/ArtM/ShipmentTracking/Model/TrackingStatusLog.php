<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Model;

use Magento\Framework\Model\AbstractModel;
use ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusLog as ResourceModel;

/**
 * @method int    getLogId()
 * @method string getRawStatus()
 * @method string getCarrierName()
 * @method string getTrackingNumber()
 * @method int    getIsMapped()
 * @method int    getSeenCount()
 * @method string getFirstSeenAt()
 * @method string getLastSeenAt()
 * @method self   setRawStatus(string $v)
 * @method self   setCarrierName(string $v)
 * @method self   setTrackingNumber(string $v)
 * @method self   setIsMapped(int $v)
 * @method self   setSeenCount(int $v)
 */
class TrackingStatusLog extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }
}
