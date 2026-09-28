<?php
namespace ArtM\RecommendedSort\Model\ResourceModel\RecommendQueue;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\RecommendedSort\Model\RecommendQueue', 'ArtM\RecommendedSort\Model\ResourceModel\RecommendQueue');
    }
}