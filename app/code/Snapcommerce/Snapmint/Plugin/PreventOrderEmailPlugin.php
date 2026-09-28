<?php

namespace Snapcommerce\Snapmint\Plugin;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Snapcommerce\Snapmint\Logger\Logger;

class PreventOrderEmailPlugin
{
    protected Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Prevent order email sending for Snapmint pending payments
     *
     * @param OrderSender $subject
     * @param Order $order
     * @param bool $forceSyncMode
     * @return array
     */
//    public function beforeSend(OrderSender $subject, Order $order, $forceSyncMode = false)
//    {
//
//        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/snapmint-email.log');
//        $logger = new \Zend_Log();
//        $logger->addWriter($writer);
//        $logger->info('--------------------------------------------------------------------');
//        $logger->info('order id ' . $order->getIncrementId());
//
//
//        if (!$order) {
//            $logger->info('line 34');
//            $this->logger->error('Order null');
//            return [null, false];
//        }
//
//        $logger->info('function ' . $this->shouldPreventEmail($order));
//
//        // Check if this is a Snapmint payment with pending status
//        if ($this->shouldPreventEmail($order)) {
//            $logger->info('line 43');
//
//            $this->logger->info('Preventing order email for Snapmint order: ' . $order->getIncrementId());
//
//            // Set flags to prevent email
//            $order->setCanSendNewEmailFlag(false);
//            $order->setEmailSent(true);
//            $order->setIsCustomerNotified(false);
//            $logger->info('line 51');
//
//            // Return false to prevent email sending
//            return [null, false]; // This will prevent the email from being sent
//        }
//        $logger->info('line 56');
//
//        return [$order, $forceSyncMode];
//    }


    /**
     * Check if email should be prevented for this order
     *
     * @param Order $order
     * @return bool
     */
    private function shouldPreventEmail(Order $order)
    {

        if ($order->getPayment() && $order->getPayment()->getMethod() === 'snapmint') {
            // Check if order is still in pending payment state
            if ($order->getState() === Order::STATE_PENDING_PAYMENT) {
                return true;
            }

            if ($order->getStatus() == "pending") {
                return true;
            }
        }

        return false;
    }

    public function orderEmailLogger()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/order-email.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        return $logger;
    }

    /**
     * Intercept order email sending
     *
     * @param OrderSender $subject
     * @param \Closure $proceed
     * @param Order $order
     * @param bool $forceSyncMode
     * @return bool
     */
    public function aroundSend(OrderSender $subject, \Closure $proceed, Order $order, $forceSyncMode = false)
    {
        $logger = $this->orderEmailLogger();
        $logger->info('--------------------------------------------------------------------');
        $logger->info('Around Send');

        try {
            if (!$order) {
                $logger->info('Order is null in aroundSend');
                return false;
            }

            $logger->info('Processing email for order: ' . $order->getIncrementId());
            $logger->info('Order status: ' . $order->getStatus());
            $logger->info('Order state: ' . $order->getState());

            // Check if email should be prevented
            if ($this->shouldPreventEmail($order)) {
                $logger->info('Preventing order email for Snapmint pending order: ' . $order->getIncrementId());

                // Set flags to indicate email was prevented
                $order->setCanSendNewEmailFlag(false);
                $order->setEmailSent(true);
                $order->setIsCustomerNotified(false);
                $logger->info('--------------------------------------------------------------------');

                // Don't proceed with email sending
                return false;
            }

            $logger->info('Allowing order email for order: ' . $order->getIncrementId());
            $logger->info('--------------------------------------------------------------------');

            // Proceed with normal email sending
            return $proceed($order, $forceSyncMode);

        } catch (\Exception $e) {
            $this->logger->error('Error in PreventOrderEmailPlugin: ' . $e->getMessage());
            $this->logger->error('Order ID: ' . ($order ? $order->getIncrementId() : 'null'));

            $logger->info('Error in PreventOrderEmailPlugin: ' . $e->getMessage());
            $logger->info('Order ID: ' . ($order ? $order->getIncrementId() : 'null'));
            $logger->info('--------------------------------------------------------------------');
            return false;
        }
    }
}
