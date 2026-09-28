<?php
namespace ArtM\MassProducts\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class CronLog extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_massproducts_cron_log', 'log_id');
    }
}
