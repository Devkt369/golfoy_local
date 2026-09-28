<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Partner extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('artm_shipment_tracking_partner', 'partner_id');
    }
}
