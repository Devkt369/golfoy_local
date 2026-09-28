<?php
declare(strict_types=1);

namespace Magestore\Rewardpoints\Plugin\Sales\Model;

use Magento\Sales\Model\Order as OrderCore;

/**
 * Class Order
 *
 * Plugin to set is cancel flag
 */
class Order
{
    /**
     * Before cancel
     *
     * @param OrderCore $subject
     * @return array
     */
    public function beforeCancel(OrderCore $subject)
    {
        $subject->setIsCancelFlag(true);
        return [];
    }
}
