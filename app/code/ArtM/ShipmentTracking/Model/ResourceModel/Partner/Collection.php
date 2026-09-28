<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Model\ResourceModel\Partner;

use ArtM\ShipmentTracking\Model\Partner as Model;
use ArtM\ShipmentTracking\Model\ResourceModel\Partner as ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'partner_id';

    protected function _construct(): void
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
