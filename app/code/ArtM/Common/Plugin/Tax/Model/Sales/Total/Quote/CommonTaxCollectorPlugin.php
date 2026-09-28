<?php

namespace ArtM\Common\Plugin\Tax\Model\Sales\Total\Quote;

use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Framework\App\ObjectManager;

class CommonTaxCollectorPlugin
{
    /**
     * Modify the mapItem method to add reward points spent.
     *
     * @param CommonTaxCollector $subject
     * @param \Closure $proceed
     * @param QuoteDetailsItemInterfaceFactory $itemDataObjectFactory
     * @param AbstractItem $item
     * @param bool $priceIncludesTax
     * @param bool $useBaseCurrency
     * @param string|null $parentCode
     * @return \Magento\Tax\Api\Data\QuoteDetailsItemInterface
     */
    public function aroundMapItem(
        CommonTaxCollector $subject,
        \Closure $proceed,
        QuoteDetailsItemInterfaceFactory $itemDataObjectFactory,
        AbstractItem $item,
        $priceIncludesTax,
        $useBaseCurrency,
        $parentCode = null
    ) {
        // Call the original method
        $itemDataObject = $proceed($itemDataObjectFactory, $item, $priceIncludesTax, $useBaseCurrency, $parentCode);

        // Add the reward points spent data
        $itemDataObject->setRewardpointsSpent($item->getData('rewardpoints_spent'));
        // echo '<pre>';
        // print_r($item->getData());die;
        $objectManager = ObjectManager::getInstance();
        $quoteItem = $objectManager->get(\Magento\Quote\Model\Quote\Item::class)->load($item->getData('item_id'));
        if($quoteItem->getData('giftcodes_applied') != null){
            $giftVoucherDiscount = json_decode($quoteItem->getData('giftcodes_applied'));
            $itemDataObject->setMagestoreGiftCardDiscount($giftVoucherDiscount[0]->gift_card_discount_amount);
        }

        return $itemDataObject;
    }
}
