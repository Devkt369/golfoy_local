<?php

namespace ArtM\Expertlist\Model\ResourceModel\ExpertModel;

use Magento\Framework\DataObject;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\Expertlist\Model\ExpertModel','ArtM\Expertlist\Model\ResourceModel\ExpertModel'
         );
    }

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this as $item) {
            $options[] = [
                'value' => $item->getId(),
                'label' => $item->getExpertName()
            ];
        }
        return $options;
    }
}