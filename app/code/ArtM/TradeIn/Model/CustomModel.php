<?php
namespace ArtM\TradeIn\Model;

use Magento\Framework\Model\AbstractModel;

class CustomModel extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\ArtM\TradeIn\Model\ResourceModel\TradeIn::class);
    }

    public function setTargetRowClass($targetRowClass)
    {
        return $this->setData('target_row_class', $targetRowClass);
    }

    public function setStatusValue($statusValue)
    {
        return $this->setData('status_value', $statusValue);
    }

    public function getTargetRowClass()
    {
        return $this->_getData('target_row_class');
    }

    public function getStatusValue()
    {
        return $this->_getData('status_value');
    }
}
