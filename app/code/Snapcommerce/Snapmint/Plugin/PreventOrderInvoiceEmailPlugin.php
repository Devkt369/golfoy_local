<?php

namespace Snapcommerce\Snapmint\Plugin;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Snapcommerce\Snapmint\Logger\Logger;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

class PreventOrderInvoiceEmailPlugin
{
    protected Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

//    /**
//     * Prevent order email sending for Snapmint pending payments
//     *
//     * @param OrderSender $subject
//     * @param Invoice $invoice
//     * @param bool $forceSyncMode
//     * @return array
//     */
//    public function beforeSend(OrderSender $subject, Invoice $invoice, $forceSyncMode = false)
//    {
//        $order = $invoice->getOrder();
//
//        if (!$order) {
//            $this->logger->error('Order null');
//            return [null, false];
//        }
//
//
//        // Check if this is a Snapmint payment with pending status
//        if ($this->shouldPreventEmail($order)) {
//            $this->logger->info('Preventing order email for Snapmint order: ' . $order->getIncrementId());
//
//            // Set flags to prevent email
//            $invoice->setEmailSent(true);
//            $invoice->setIsCustomerNotified(false);
//
//            // Return false to prevent email sending
//            return [null, false]; // This will prevent the email from being sent
//        }
//
//        return [$order, $forceSyncMode];
//    }

    /**
     * Intercept invoice email sending for Snapmint orders
     *
     * @param InvoiceSender $subject
     * @param \Closure $proceed
     * @param Invoice $invoice
     * @param bool $forceSyncMode
     * @return bool
     */
    public function aroundSend(InvoiceSender $subject, \Closure $proceed, Invoice $invoice, $forceSyncMode = false)
    {
        try {
            if (!$invoice) {
                $this->logger->error('Invoice is null in aroundSend');
                return false;
            }

            $order = $invoice->getOrder();

            if (!$order) {
                $this->logger->error('Order is null for invoice: ' . $invoice->getIncrementId());
                return false;
            }

            $this->logger->info('Processing invoice email for order: ' . $order->getIncrementId());
            $this->logger->info('Invoice ID: ' . $invoice->getIncrementId());
            $this->logger->info('Order status: ' . $order->getStatus());
            $this->logger->info('Order state: ' . $order->getState());

            // Check if email should be prevented
            if ($this->shouldPreventEmail($order)) {
                $this->logger->info('Preventing invoice email for Snapmint pending order: ' . $order->getIncrementId());

                // Set flags to indicate email was prevented
                $invoice->setEmailSent(true);
                $invoice->setIsCustomerNotified(false);

                // Don't proceed with email sending
                return false;
            }

            $this->logger->info('Allowing invoice email for order: ' . $order->getIncrementId());

            // Proceed with normal email sending
            return $proceed($invoice, $forceSyncMode);

        } catch (\Exception $e) {
            $this->logger->error('Error in PreventOrderInvoiceEmailPlugin: ' . $e->getMessage());
            $this->logger->error('Invoice ID: ' . ($invoice ? $invoice->getIncrementId() : 'null'));
            $this->logger->error('Order ID: ' . (isset($order) && $order ? $order->getIncrementId() : 'null'));
            return false;
        }
    }

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

    /**
     * After send - log if email was sent despite prevention attempts
     *
     * @param InvoiceSender $subject
     * @param bool $result
     * @param Invoice $invoice
     * @return bool
     */
    public function afterSend(InvoiceSender $subject, $result, Invoice $invoice)
    {
        if ($result && $this->shouldPreventEmail($order)) {
            $this->logger->warning('Order email was sent despite prevention attempts for Snapmint order: ' . $order->getIncrementId());
        }

        return $result;
    }
}
