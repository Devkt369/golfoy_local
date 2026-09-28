<?php

namespace ArtM\MsiPriority\Model;

use Magento\Framework\Model\AbstractModel;

class ArchiveOrders extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\ArtM\MsiPriority\Model\ResourceModel\ArchiveOrders::class);
    }

    public function saveOrder($data)
    {
        $this->setData($data);
        return $this->save();
    }

    public function deleteById($orderId)
    {
        $order = $this->load($orderId);
        if ($order->getId()) {
            $order->delete();
        }
    }
}
