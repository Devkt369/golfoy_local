<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Rewardpoints\Block\Adminhtml\Earningrates\Checkout;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Block\Adminhtml\Order\Create\AbstractCreate;
use Magestore\Rewardpoints\Helper\Block\Spend;
use Magestore\Rewardpoints\Helper\Calculation\Spending;
use Magento\Backend\Model\Session\Quote;

/**
 * Adminhtml reward points - checkout - earning point
 */
class Point extends AbstractCreate
{
    /**
     * @var
     */
    protected $_objectManager;

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $objectManager = ObjectManager::getInstance();
        $this->_objectManager = $objectManager;
        $quote = $this->_objectManager->create(Quote::class)->getQuote();
        $serialize = $this->_objectManager->get(SerializerInterface::class);
        $quote->setData('use_point', 1);
        $quote->setRewardSalesRules(
            $serialize->serialize(
                [
                    'rule_id' => $this->getRequest()->getPostValue()['reward_sales_rule'],
                    'use_point' => $this->getRequest()->getPostValue()['reward_sales_point'],
                ]
            )
        );
        $quote->save();
        parent::_construct();
        $this->setId('sales_order_create_rewardpoint');
    }

    /**
     * Check Use Default
     */
    public function checkUseDefault()
    {
        $quote = $this->_objectManager->create(Quote::class)->getQuote();
        $quote->setData('use_max_point', 0);
        $rewardSalesRules = $quote->getRewardSalesRules();
        $arrayRules = $this->_objectManager->create(Spend::class)->getRulesArray();
        if ($this->_objectManager->create(Spending::class)
            ->isUseMaxPointsDefault()
        ) {
            if (isset($rewardSalesRules['use_point']) &&
                isset($rewardSalesRules['rule_id']) &&
                isset($arrayRules[$rewardSalesRules['rule_id']]) &&
                isset($arrayRules[$rewardSalesRules['rule_id']]['sliderOption']) &&
                isset($arrayRules[$rewardSalesRules['rule_id']]['sliderOption']['maxPoints']) &&
                $rewardSalesRules['use_point'] < $arrayRules[$rewardSalesRules['rule_id']]['sliderOption']['maxPoints']
            ) {
                $quote->setData('use_max_point', 0);
            } else {
                $quote->setData('use_max_point', 1);
            }
        }
        $quote->save();
    }
}
