<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use ArtM\ShipmentTracking\Model\TrackingStatusLog as Model;
use ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusLog as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'log_id';

    protected function _construct(): void
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
