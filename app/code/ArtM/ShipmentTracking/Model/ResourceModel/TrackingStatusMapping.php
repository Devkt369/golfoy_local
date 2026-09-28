<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TrackingStatusMapping extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('artm_tracking_status_mapping', 'mapping_id');
    }
}
