<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Observer;

use Amasty\Promo\Observer\Salesrule\Discount;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\SalesRule\Model\Data\Rule as DataRule;
use Magento\SalesRule\Model\Rule;

/**
 * event name magento_salesrule_api_data_ruleinterface_save_before
 */
class SaveSimpleFreeShippingSetting implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $entity = $observer->getEntity();
        if ($entity instanceof Rule
            && in_array($entity->getSimpleAction(), Discount::PROMO_RULES)
        ) {
            //in some cases SaveHandler doesn't change $entity data
            // @see \Magento\Staging\Model\Operation\Update::processUpdate()
            $entity->setData(DataRule::KEY_SIMPLE_FREE_SHIPPING, 0);
        }
    }
}
