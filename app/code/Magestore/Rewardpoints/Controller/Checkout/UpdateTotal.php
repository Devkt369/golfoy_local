<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Giftvoucher
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
namespace Magestore\Rewardpoints\Controller\Checkout;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magestore\Rewardpoints\Controller\AbstractAction;
use Zend_Json;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Checkout - Update total controller
 */
class UpdateTotal extends AbstractAction implements HttpPostActionInterface
{
    /**
     * @inheritDoc
     */
    public function execute()
    {
        /* @var SerializerInterface $serialize */
        $serialize = ObjectManager::getInstance()->get(SerializerInterface::class);
        $quote = $this->_checkoutSessionFactory->create()->getQuote();
        $quote->setData('use_point', 1);
        $data = $this->getRequest()->getPostValue();
        $rulesData = $this->_helperSpend->getRulesData();
        if (isset($data['reward_sales_rule']) && isset($data['reward_sales_point'])) {
            $usePoint = $data['reward_sales_point'];
            if (isset($data['use_max_point'])) {
                if (isset($rulesData) && ($data['use_max_point'] == 1)) {
                    $usePoint = $rulesData['rate']['sliderOption']['maxPoints'];
                }
                $quote->setUseMaxPoint($data['use_max_point']);
            }
            $quote->setRewardSalesRules(
                $serialize->serialize(
                    [
                        'rule_id' => $data['reward_sales_rule'],
                        'use_point' => $usePoint,
                    ]
                )
            );
        }
        if ($quote->getItemsCount()) {
            $this->checkUseDefault();
        }
        $quote->collectTotals();
        $quote->save();
        $amount = $this->_checkoutCartFactory->create()->getQuote()->getRewardpointsBaseDiscount();
        $result = [
            'earning' => $this->_helperPoint->format($this->_checkoutForm->getEarningPoint()),
            'spending' => $this->_helperPoint->format($this->_checkoutForm->getSpendingPoint()),
            'usePoint' =>  strip_tags($this->_helperData->convertAndFormat(-$amount)),
            'rate' => $rulesData['rate']
        ];
        return $this->getResponse()->setBody(Zend_Json::encode($result));
    }

    /**
     * Check Use Default
     */
    public function checkUseDefault()
    {
        /* @var SerializerInterface $serialize */
        $serialize = ObjectManager::getInstance()->get(SerializerInterface::class);
        $quote = $this->_checkoutSessionFactory->create()->getQuote();
        $quote->setData('use_max_point', 0);
        $rewardSalesRules = $serialize->unserialize($quote->getRewardSalesRules());
        $arrayRules = $this->_helperSpend->getRulesArray();
        if ($this->_calculationSpending->isUseMaxPointsDefault()) {
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
    }
}
