<?php

namespace ArtM\RecommendedSort\Observer\Frontend\Order;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use ArtM\RecommendedSort\Helper\Data as RecommendedSortHelper;
use ArtM\RecommendedSort\Logger\Logger;

class CreateAfter implements ObserverInterface
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * RecommendedSortHelper
     *
     * @var RecommendedSortHelper
     */
    protected $recommendedSortHelper;

    /**
     * when order place then products points will be recalculated
     *
     * @param Logger $logger
     * @param RecommendedSortHelper $recommendedSortHelper
     */
    public function __construct(
        RecommendedSortHelper $recommendedSortHelper,
        Logger $logger
    ) {
        $this->recommendedSortHelper = $recommendedSortHelper;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getOrder();
            $this->getOrderedItems($order);
        } catch (\Exception $e) {
            $this->logger->error('Error in update proints {recommend sort module }Observer: ' . $e->getMessage());
        }
    }

    public function getOrderedItems($order) {
        $orderItems = $order->getItems();
        foreach ($orderItems as $item) {
            $productId = $item->getProductId();
            $this->recommendedSortHelper->addProductToQueue($productId, 'website-new-order');
        }
    }
}
