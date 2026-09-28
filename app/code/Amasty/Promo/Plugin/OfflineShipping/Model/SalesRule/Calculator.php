<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Plugin\OfflineShipping\Model\SalesRule;

use Magento\OfflineShipping\Model\SalesRule\Calculator as ShippingCalculator;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * Free Shipping for Full Discounted Promo Items
 */
class Calculator
{
    /**
     * @var \Magento\Quote\Model\Quote\Item\AbstractItem
     */
    private $item;

    /**
     * @var \Amasty\Promo\Helper\Item
     */
    private $helperItem;

    /**
     * @var \Amasty\Promo\Model\ResourceModel\Rule
     */
    private $ruleResource;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var array
     */
    protected $appliedShipping = [];

    /**
     * @var array
     * format: [itemId] => bool (true, if item is free gift)
     */
    private $processedItems = [];

    public function __construct(
        \Amasty\Promo\Helper\Item $helperItem,
        \Amasty\Promo\Model\ResourceModel\Rule $ruleResource,
        \Magento\Checkout\Model\Session $resourceSession
    ) {
        $this->checkoutSession = $resourceSession;
        $this->helperItem = $helperItem;
        $this->ruleResource = $ruleResource;
    }

    /**
     * @param ShippingCalculator $subject
     * @param ShippingCalculator $result
     * @param AbstractItem $item
     *
     * @return ShippingCalculator
     */
    public function afterProcessFreeShipping(
        ShippingCalculator $subject,
        $result,
        $item
    ) {
        $itemId = $item->getId();
        if (!$itemId) {
            $this->checkFreeShipping($item);

            return $result;
        }

        if (array_key_exists($itemId, $this->processedItems)) {
            $itemSku = $this->helperItem->getItemSku($item);
            if (($this->processedItems[$itemId] === true)
                && isset($this->appliedShipping[$itemSku])
                && ($this->appliedShipping[$itemSku] === false)
            ) {
                $item->setFreeShipping(true);
            }

            return $result;
        }

        foreach ($item->getQuote()->getAllItems() as $promoItem) {
            if (in_array($promoItem->getId(), $this->processedItems, true)) {
                continue;
            }

            $this->processedItems[$promoItem->getId()] = false;
            if ($promoItem->getParentItemId()) {
                continue;
            }

            $this->checkFreeShipping($promoItem);
        }

        return $result;
    }

    private function checkFreeShipping(AbstractItem $item): void
    {
        $fullDiscountItems = $this->checkoutSession->getAmpromoFullDiscountItems();
        $promoItemSku = $this->helperItem->getItemSku($item);

        if (isset($fullDiscountItems[$promoItemSku])
            && $this->helperItem->isPromoItem($item)
            && $this->helperItem->getRuleId($item)
        ) {
            if (!isset($this->appliedShipping[$promoItemSku])) {
                $this->appliedShipping[$promoItemSku] = $this->ruleResource
                    ->isApplyShipping($fullDiscountItems[$promoItemSku]['rule_ids']);
            }

            if ($this->appliedShipping[$promoItemSku] === false) {
                $item->setFreeShipping(true);
                $this->processedItems[$item->getId()] = true;
            }
        }
    }

    public function _resetState(): void
    {
        $this->processedItems = [];
        $this->appliedShipping = [];
    }
}
