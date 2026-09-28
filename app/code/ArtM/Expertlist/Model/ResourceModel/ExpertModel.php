<?php
namespace ArtM\Expertlist\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ExpertModel extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_expert_review','id'); 
    }
}