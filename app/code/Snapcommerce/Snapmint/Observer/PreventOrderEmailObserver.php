<?php

namespace Snapcommerce\Snapmint\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Snapcommerce\Snapmint\Logger\Logger;
class PreventOrderEmailObserver implements ObserverInterface {
	protected Logger $logger;

	public function __construct(
        Logger $logger,
        QuoteFactory $quoteFactory,
        CheckoutSession $checkoutSession,
        \Magento\Checkout\Model\Session\Proxy $checkoutSessionProxy
	 ) {
		$this->logger = $logger;
		$this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutSessionProxy = $checkoutSessionProxy;
	}

	/**
	 * Prevent order emails for Snapmint pending payments
	 *
	 * @param Observer $observer
	 * @return void
	 */
	public function execute( Observer $observer ) {
		try {
			$order = $observer->getEvent()->getOrder();

			if ( ! $order ) {
				$this->logger->error('Order null');
				return;
			}

			if ( $order === null) {
				$this->logger->error('Order Object null');
				return;
			}

			if ( $order->getPayment() === null) {
				$this->logger->error('Order payment null');
				return;
			}

			// Check if this is a snapmint payment pending order
			if ( $order->getPayment()->getMethod() === 'snapmint' ) {

				$this->logger->info( 'Preventing email for snapmint order observer: ' . $order->getIncrementId() );

				// Prevent the email from being sent
				$order->setCanSendNewEmailFlag( false );
				$order->setEmailSent( true );
				$order->setIsCustomerNotified( false );

				// Stop the event from proceeding
			}

		} catch (\Exception $e) {
			$this->logger->error( 'Error in PreventOrderEmailObserver: ' . $e->getMessage() );
		}
	}
}
