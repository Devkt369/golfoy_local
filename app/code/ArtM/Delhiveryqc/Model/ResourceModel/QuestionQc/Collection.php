<?php
namespace ArtM\Delhiveryqc\Model\ResourceModel\QuestionQc;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\Delhiveryqc\Model\QuestionQc', 'ArtM\Delhiveryqc\Model\ResourceModel\QuestionQc');
    }
}