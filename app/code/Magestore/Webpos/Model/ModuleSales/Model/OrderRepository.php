<?php

namespace Magestore\Webpos\Model\ModuleSales\Model;

use Magento\Sales\Model\OrderRepository as CoreOrderRepository;

/**
 * Add function to remove registry order
 */
class OrderRepository extends CoreOrderRepository
{
    /**
     * Remove Registry
     *
     * @param int $id
     */
    public function removeRegistry(int $id)
    {
        if ($id && isset($this->registry[$id])) {
            unset($this->registry[$id]);
        }
    }
}
