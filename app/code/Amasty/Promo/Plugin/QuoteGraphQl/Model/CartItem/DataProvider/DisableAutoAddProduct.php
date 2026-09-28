<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Plugin\QuoteGraphQl\Model\CartItem\DataProvider;

use Amasty\Promo\Helper\Item;
use Amasty\Promo\Model\PromoItemRepository;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\UpdateCartItems;

class DisableAutoAddProduct
{
    /**
     * @var Item
     */
    private $helperItem;

    /**
     * @var PromoItemRepository
     */
    private $promoItemRepository;

    public function __construct(
        Item $helperItem,
        PromoItemRepository $promoItemRepository
    ) {
        $this->helperItem = $helperItem;
        $this->promoItemRepository = $promoItemRepository;
    }

    public function beforeProcessCartItems(UpdateCartItems $subject, Quote $cart, array $items): void
    {
        $promoItemsGroup = $this->promoItemRepository->getItemsByQuoteId((int)$cart->getId());
        foreach ($items as $item) {
            $cartItem = $cart->getItemById($item['cart_item_id']);

            if ($cartItem && $this->helperItem->isPromoItem($cartItem) && $item['quantity'] != $cartItem->getQty()) {
                $promoItemData = $promoItemsGroup->getItemBySkuAndRuleId(
                    $cartItem->getProduct()->getData('sku'),
                    $this->helperItem->getRuleId($cartItem)
                );
                if ($promoItemData && $promoItemData->isAutoAdd()) {
                    //disable auto add functionality if customer changing qty manually
                    $promoItemData->isItemDeleted(true);
                }
            }
        }
    }
}
