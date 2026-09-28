<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Plugin\Shipping\Model\Shipping;

use Amasty\Promo\Helper\Item;
use Magento\Quote\Model\Quote\Address\Item as AddressItem;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Shipping;

class FixFreeShipping
{
    /**
     * @var Item
     */
    private Item $helperItem;

    public function __construct(
        Item $helperItem
    ) {
        $this->helperItem = $helperItem;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param Shipping $subject
     * @param RateRequest $request
     * @return array
     */
    public function beforeCollectRates(Shipping $subject, RateRequest $request): array
    {
        //fix free shipping for promo items during multishipping checkout
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item instanceof AddressItem
                    && $this->helperItem->isPromoItem($item)
                    && $item->getQuoteItem()->getFreeShipping()
                ) {
                    $item->setFreeShipping(1);
                }
            }
        }

        return [$request];
    }
}
