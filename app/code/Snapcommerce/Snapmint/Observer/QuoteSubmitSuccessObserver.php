<?php
namespace Snapcommerce\Snapmint\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Snapcommerce\Snapmint\Logger\Logger;
use Magento\Quote\Api\CartRepositoryInterface;

class QuoteSubmitSuccessObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    protected Logger $logger;

       protected  CartRepositoryInterface $quoteRepository;

    /**
     * Constructor
     *
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,
        CartRepositoryInterface $quoteRepository
    )
    {
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;

    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // Get the quote and order
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        // Example: Log the quote and order information
        $this->logger->info('Quote submit success event triggered');
        $this->logger->info('Order ID: ' . $order->getId());
        $this->logger->info('Quote ID: ' . $quote->getId());


        $quote->setIsActive(true);
        $this->quoteRepository->save($quote);
        $this->logger->info('keep cart active: ' . $quote->getId());

        // Add your custom logic here, for example:
        // - Send a notification
        // - Update quote/order data
        // - Trigger custom processes
    }
}
