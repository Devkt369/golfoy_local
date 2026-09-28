<?php

namespace Snapcommerce\Snapmint\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Snapcommerce\Snapmint\Logger\Logger;

class OrderStatusChangeObserver implements ObserverInterface
{
    protected Logger $logger;
    protected OrderSender $orderSender;

    public function __construct(
        Logger $logger,
        OrderSender $orderSender
    ) {
        $this->logger = $logger;
        $this->orderSender = $orderSender;
    }

    public function orderEmailLogger()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/order-email.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        return $logger;
    }

    /**
     * Handle order status/state changes for Snapmint orders
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $logger = $this->orderEmailLogger();

        try {
            $order = $observer->getEvent()->getOrder();

            if (!$order || !$order->getPayment()) {
                return;
            }

            // Only handle Snapmint orders
            if ($order->getPayment()->getMethod() !== 'snapmint' && $order->getStatus() != "processing") {
                return;
            }

            $logger->info('--------------------------------------------------------------------');
            $logger->info('OBSERVER');
            $logger->info('Payment method '.$order->getPayment()->getMethod());
            $logger->info('Snapmint order status change detected:');
            $logger->info('Order ID: ' . $order->getIncrementId());
            $logger->info('State: ' . $order->getState());
            $logger->info('Status: ' . $order->getStatus());
            $logger->info('Email Sent Flag: ' . ($order->getEmailSent() ? 'true' : 'false'));

            // Check if order moved to a processing status and email hasn't been sent
            if ($this->shouldSendEmail($order)) {
                $logger->info('Attempting to send email for successful Snapmint order: ' . $order->getIncrementId());

                // Reset email flags
                $order->setEmailSent(false);
                $order->setCanSendNewEmailFlag(true);
                $order->setIsCustomerNotified(false);

                // Save the order to persist flag changes
                $order->save();

                // Send the email
                try {
                    $this->orderSender->send($order, true);
                    $logger->info('Email sent successfully for Snapmint order: ' . $order->getIncrementId());
                } catch (\Exception $e) {
                    $logger->info('Failed to send email for Snapmint order ' . $order->getIncrementId() . ': ' . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            $logger->info('Error in Snapmint OrderStatusChangeObserver: ' . $e->getMessage());
        }
        $logger->info('--------------------------------------------------------------------');
    }

    /**
     * Check if email should be sent for this order
     *
     * @param Order $order
     * @return bool
     */
    private function shouldSendEmail(Order $order): bool
    {
        // Only for Snapmint orders
        if (!$order->getPayment() || $order->getPayment()->getMethod() !== 'snapmint') {
            return false;
        }

        // Email already sent
        if ($order->getEmailSent()) {
            return false;
        }

        // Order should be in a successful state
        $successStates = [
            Order::STATE_PROCESSING
        ];

        $successStatuses = [
            'processing'
        ];

        return in_array($order->getState(), $successStates) ||
            in_array($order->getStatus(), $successStatuses);
    }
}
