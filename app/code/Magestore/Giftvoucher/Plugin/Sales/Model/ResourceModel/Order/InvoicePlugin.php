<?php

/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Giftvoucher\Plugin\Sales\Model\ResourceModel\Order;

/**
 * Class Order
 *
 * Used to active gift card after save
 */
class InvoicePlugin
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magestore\Giftvoucher\Model\GiftvoucherFactory
     */
    protected $giftvoucherFactory;

    /**
     * @var \Magestore\Giftvoucher\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    protected $smsHelperData;

    /**
     * SalesOrderInvoiceSaveAfterObserver constructor.
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magestore\Giftvoucher\Model\GiftvoucherFactory $giftvoucher
     * @param \Magestore\Giftvoucher\Helper\Data $helperData
     * @param \Magento\Framework\Message\ManagerInterface $message
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magestore\Giftvoucher\Model\GiftvoucherFactory $giftvoucher,
        \Magestore\Giftvoucher\Helper\Data $helperData,
        \Meetanshi\Sms\Helper\Data $smsHelperData,
        \Magento\Framework\Message\ManagerInterface $message
    ) {
        $this->orderFactory = $orderFactory;
        $this->giftvoucherFactory = $giftvoucher;
        $this->helperData = $helperData;
        $this->smsHelperData = $smsHelperData;
        $this->messageManager = $message;
    }

    /**
     * After save invoice resource
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice $subject
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice $result
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return \Magento\Sales\Model\ResourceModel\Order\Invoice
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * phpcs:disable Generic.Metrics.NestingLevel
     */
    public function afterSave(
        \Magento\Sales\Model\ResourceModel\Order\Invoice $subject,
        \Magento\Sales\Model\ResourceModel\Order\Invoice $result,
        \Magento\Sales\Model\Order\Invoice $invoice
    ) {
        $order = $invoice->getOrder();
        $order = $this->orderFactory->create()->load($order->getId());
        $customerId = $order->getCustomerId();
        $phoneNumber= '';

        if ($customerId) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerFactory = $objectManager->get(\Magento\Customer\Model\CustomerFactory::class);
            $customer = $customerFactory->create()->load($customerId);
            $phoneNumber = $customer->getData('phone_number');
        }

        if(!$phoneNumber){
            $billingAddress = $order->getBillingAddress();
            if ($billingAddress) {
                $phoneNumber = $billingAddress->getTelephone();
            }
        }

        foreach ($invoice->getAllItems() as $itemCredit) {
            $item = $order->getItemById($itemCredit->getOrderItemId());
            if (isset($item) && $item != null) {
                if ($item->getProductType() != 'giftvoucher') {
                    continue;
                }

                if ($item->getQuoteItemId()) {
                    $giftVouchers = $this->giftvoucherFactory->create()->getCollection()
                        ->addItemFilter($item->getQuoteItemId());
                } else {
                    $giftVouchers = $this->giftvoucherFactory->create()->getCollection()
                        ->addItemFilter($item->getId(), true);
                }
                $itemQtyInvoice = $itemCredit->getQty();
                foreach ($giftVouchers as $giftVoucher) {
                    if ($giftVoucher->getStatus() == \Magestore\Giftvoucher\Model\Status::STATUS_PENDING) {
                        $giftVoucher->addData(
                            [
                                'status' => \Magestore\Giftvoucher\Model\Status::STATUS_ACTIVE,
                                'comments' => __('Active when order is complete'),
                                'amount' => $giftVoucher->getBalance(),
                                'action' => \Magestore\Giftvoucher\Model\Actions::ACTIONS_UPDATE,
                                'order_increment_id'    => $order->getIncrementId()
                            ]
                        )->setIncludeHistory(true);
                        try {
                            if ($giftVoucher->getDayToSend() && strtotime($giftVoucher->getDayToSend()) > time()
                            ) {
                                $giftVoucher->setData('dont_send_email_to_recipient', 1);
                            }
                            $giftVoucher->save();
                            if ($this->helperData->getEmailConfig('enable', $order->getStoreId())) {
                                $giftVoucher->setIncludeHistory(false);
                                if ($giftVoucher->getRecipientEmail()) {
                                    if ($giftVoucher->sendEmailToRecipient() && $giftVoucher->getNotifySuccess()) {
                                        $giftVoucher->sendEmailSuccess();
                                    }
                                } else {
                                    $giftVoucher->sendEmail();
                                }
                            }
                            try{
                                $giftVoucherCode = $giftVoucher->getGiftCode();

                                $segment1 = substr($giftVoucherCode, 0, 4);
                                $separator1 = substr($giftVoucherCode, 4, 1);
                                $segment2 = substr($giftVoucherCode, 5, 4);
                                $separator2 = substr($giftVoucherCode, 9, 1);
                                $segment3 = substr($giftVoucherCode, 10, 4);

                                $maskedCode = 'XXXX' . $separator1 . 'XX' . substr($segment2, 2) . $separator2 . $segment3;
                                $data = ["var1" => (int)$giftVoucher->getBalance(),"var2" => $maskedCode];
                                // print_r($data);die;
                                $this->smsHelperData->sendSmsGiftCardPurchased($data, $phoneNumber, "gift-voucher-purchase");
                            }catch(\Exception $e){
                                $this->messageManager->addErrorMessage($e->getMessage());
                            }
                        } catch (\Exception $e) {
                            $this->messageManager->addErrorMessage($e->getMessage());
                        }
                        $itemQtyInvoice--;
                        if (!$itemQtyInvoice) {
                            break;
                        }
                    }
                }
            }
        }
        return $result;
    }
}
