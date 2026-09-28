<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusMapping;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use ArtM\ShipmentTracking\Model\TrackingStatusMapping as Model;
use ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusMapping as ResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'mapping_id';

    protected function _construct(): void
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
