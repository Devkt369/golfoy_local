<?php

namespace ArtM\PrintPriceTracking\Plugin;

use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Api\Data\OrderItemInterface;
use Psr\Log\LoggerInterface;

class QuoteItemToOrderItemPlugin
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    public function afterConvert(
        ToOrderItem $subject,
        OrderItemInterface $orderItem,
        $item,
        array $data = []
    ) {
        $customData = $item->getData('custom_design_userdata_ids');
        if ($customData) {
            $this->logger->info('ArtM Print Tracker: copying custom_design_userdata_ids to order item', ['quote_item_id' => $item->getId(), 'order_item_id' => $orderItem->getItemId() ?? null]);
            $options = $orderItem->getProductOptions() ?: [];
            $options['custom_design_userdata_ids'] = $customData;
            $orderItem->setProductOptions($options);
        }

        return $orderItem;
    }
}
