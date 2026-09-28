<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_GiftCard
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\GiftCard\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Mageplaza\GiftCard\Helper\Data as DataHelper;
use Mageplaza\GiftCard\Helper\Product as Helper;
use Mageplaza\GiftCard\Model\Product\Type\GiftCard;
use Mageplaza\GiftCard\Model\Transaction\Action;
use Mageplaza\GiftCard\Model\TransactionFactory;

/**
 * Class OrderSaveAfter
 * @package Mageplaza\GiftCard\Observer
 */
class OrderSaveAfter implements ObserverInterface
{
    /**
     * @var Helper
     */
    protected $_helper;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * OrderSaveAfter constructor.
     *
     * @param Helper $helper
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(Helper $helper, TransactionFactory $transactionFactory)
    {
        $this->_helper = $helper;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param Observer $observer
     *
     * @return $this
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (!$order->getGrandTotal() && ($order->getGiftCreditAmount() || $order->getMpGiftCards())) {
            $order->setStatus(Order::STATE_PROCESSING);
            $order->save();
        }

        if ($order->getState() === Order::STATE_COMPLETE) {
            foreach ($order->getAllItems() as $item) {
                if ($item->isDummy() || ($item->getProductType() !== GiftCard::TYPE_GIFTCARD)) {
                    continue;
                }

                $this->_helper->generateGiftCode($order, $item);
            }
        }
        if ($order->getState() === 'canceled') {
            $giftCredit = $order->getBaseGiftCreditAmount();
            if ($giftCredit && abs($giftCredit) > 0.0001) {
                $this->transactionFactory->create()
                    ->createTransaction(
                        Action::ACTION_REVERT,
                        $giftCredit * -1,
                        $order->getCustomerId(),
                        ['order_increment_id' => $order->getIncrementId()]
                    );
            }

        }

        return $this;
    }
}
