<?php

namespace ArtM\CustomerSurvey\Model\ResourceModel\Location;

use ArtM\CustomerSurvey\Model\Location as LocationModel;
use ArtM\CustomerSurvey\Model\ResourceModel\Location as LocationResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(LocationModel::class, LocationResource::class);
    }
}
