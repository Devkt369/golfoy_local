<?php

/**
 * Magestore
 * NOTICE OF LICENSE
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_RewardPoints
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Rewardpoints\Helper\Calculation;

use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Config;
use Magestore\Rewardpoints\Model\Rate;
use Magestore\Rewardpoints\Model\RateFactory;
use Magestore\Rewardpoints\Helper\Data as HelperData;

/**
 * RewardPoints Spending Calculation Helper
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Spending extends AbstractCalculation
{

    const XML_PATH_MAX_POINTS_PER_ORDER = 'rewardpoints/spending/max_points_per_order';
    const XML_PATH_FREE_SHIPPING = 'rewardpoints/spending/free_shipping';
    const XML_PATH_SPEND_FOR_SHIPPING = 'rewardpoints/spending/spend_for_shipping';
    const XML_PATH_ORDER_REFUND_STATUS = 'rewardpoints/spending/order_refund_state';
    const XML_PATH_MAX_POINTS_DEFAULT = 'rewardpoints/spending/max_point_default';

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Checkout\Model\SessionFactory
     */
    protected $_checkoutSessionFactory;

    /**
     * @var RateFactory
     */
    protected $_rateModelFactory;

    /**
     * Event manager
     *
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var Config
     */
    protected $taxConfig;

    /**
     * Spending constructor.
     *
     * @param Context $context
     * @param \Magestore\Rewardpoints\Helper\Config $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory
     * @param ObjectManagerInterface $objectManager
     * @param SessionFactory $customerSessionFactory
     * @param RateFactory $rateModelFactory
     * @param Config|null $taxConfig
     */
    public function __construct(
        Context $context,
        \Magestore\Rewardpoints\Helper\Config $scopeConfig,
        StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\SessionFactory $checkoutSessionFactory,
        ObjectManagerInterface $objectManager,
        SessionFactory $customerSessionFactory,
        RateFactory $rateModelFactory,
        Config $taxConfig = null
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_checkoutSessionFactory = $checkoutSessionFactory;
        $this->_rateModelFactory = $rateModelFactory;
        $this->_eventManager = $context->getEventManager();
        $this->taxConfig = $taxConfig ?: ObjectManager::getInstance()->create(Config::class);
        parent::__construct($context, $storeManager, $customerSessionFactory, $checkoutSessionFactory, $objectManager);
    }

    /**
     * Get Max point that customer can used to spend for an order
     *
     * @param mixed $store
     * @return int
     */
    public function getMaxPointsPerOrder($store = null)
    {
        $maxPerOrder = (int)$this->_scopeConfig->getConfig(self::XML_PATH_MAX_POINTS_PER_ORDER, $store);
        if ($maxPerOrder > 0) {
            return $maxPerOrder;
        }
        return 0;
    }

    /**
     * Get Total Point that customer used to spent for the order
     *
     * @return int
     */
    public function getTotalPointSpent()
    {
        $container = new DataObject(
            [
                'total_point_spent' => 0
            ]
        );

        $this->_eventManager->dispatch(
            'rewardpoints_calculation_spending_get_total_point',
            [
                'container' => $container,
            ]
        );
        return $this->getPointItemSpent() + $this->getCheckedRulePoint()
            + $this->getSliderRulePoint() + $container->getTotalPointSpent();
    }

    /**
     * Get discount (Base Currency) by points of each product item on the shopping cart with $item is null,
     *
     * Result is the total discount of all items
     *
     * @param Item|null $item
     * @return float
     */
    public function getPointItemDiscount($item = null)
    {
        $container = new DataObject(
            [
                'point_item_discount' => 0
            ]
        );
        $this->_eventManager->dispatch(
            'rewardpoints_calculation_spending_point_item_discount',
            [
                'item' => $item,
                'container' => $container,
            ]
        );
        return $container->getPointItemDiscount();
    }

    /**
     * Get point that customer used to spend for each product item with $item is null,
     *
     * Result is the total points used for all items
     *
     * @param Item|null $item
     * @return int
     */
    public function getPointItemSpent($item = null)
    {
        $container = new DataObject(
            [
                'point_item_spent' => 0
            ]
        );
        $this->_eventManager->dispatch(
            'rewardpoints_calculation_spending_point_item_spent',
            [
                'item' => $item,
                'container' => $container,
            ]
        );
        return $container->getPointItemSpent();
    }

    /**
     * Pre collect total for quote/address and return quote total
     *
     * @param Quote $quote
     * @param null|Address $address
     * @param boolean $isApplyAfterTax
     * @return float
     */
    public function getQuoteBaseTotal($quote, $address = null, $isApplyAfterTax = true)
    {
        /**
         * $cacheKey = 'quote_base_total';
         * if ($this->hasCache($cacheKey)) {
         * return $this->getCache($cacheKey);
         * }
         */

        $baseTotal = $this->getQuoteBaseTotalWithoutShippingFee($quote, $address, $isApplyAfterTax);

        if ($this->_scopeConfig->getConfig(self::XML_PATH_SPEND_FOR_SHIPPING, $quote->getStoreId())) {
            $shippingAmount = $address->getShippingAmountForDiscount();
            if ($shippingAmount !== null) {
                $baseShippingAmount = $address->getBaseShippingAmountForDiscount();
            } else {
                $baseShippingAmount = $address->getBaseShippingAmount();
            }
            $baseTotal += $baseShippingAmount
                - $address->getBaseShippingDiscountAmount()
                + $address->getRewardpointsBaseDiscountForShipping();
            if ($isApplyAfterTax) {
                $baseTotal += $address->getBaseShippingTaxAmount();
            }
        }
        return $baseTotal;
    }

    /**
     * Pre collect total for quote/address and return quote total without shipping fee
     *
     * @param Quote $quote
     * @param null|Address $address
     * @param boolean $isApplyAfterTax
     * @return float
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getQuoteBaseTotalWithoutShippingFee($quote, &$address = null, $isApplyAfterTax = true)
    {
        $baseTotal = 0;
        if ($address === null) {
            if ($quote->isVirtual()) {
                $address = $quote->getBillingAddress();
            } else {
                $address = $quote->getShippingAddress();
            }
        }
        foreach ($address->getAllItems() as $item) {
            if (($item->getParentItemId() && !$item->isChildrenCalculated())
                || ($item->getHasChildren() && $item->isChildrenCalculated())) {
                continue;
            }
            $qty = $item->getTotalQty();
            $baseTotal += $this->getItemBasePrice($item) * $qty
                - $item->getBaseDiscountAmount()
                + $item->getRewardpointsBaseDiscount();

            if ($isApplyAfterTax && !$this->taxConfig->discountTax()) {
                $baseTotal += $item->getBaseTaxAmount();
            } elseif (!$isApplyAfterTax && $this->taxConfig->discountTax()) {
                $baseTotal -= $item->getBaseTaxAmount();
            }
        }
        return $baseTotal;
    }

    /**
     * Return item base price
     *
     * @param AbstractItem $item
     * @return float
     */
    public function getItemBasePrice($item)
    {
        $price = $item->getBaseDiscountCalculationPrice();
        if ($price === null) {
            return $item->getBaseCalculationPrice();
        } else {
            return $price;
        }
    }

    /**
     * Return item price
     *
     * @param AbstractItem $item
     * @return float
     */
    public function getItemPrice($item)
    {
        $price = $item->getDiscountCalculationPrice();
        if ($price === null) {
            return $item->getCalculationPrice();
        } else {
            return $price;
        }
    }

    /**
     * Get discount (Base Currency) by points that spent with check rule type
     *
     * @return float
     */
    public function getCheckedRuleDiscount()
    {
        $container = new DataObject(
            [
                'checked_rule_discount' => 0
            ]
        );
        $this->_eventManager->dispatch(
            'rewardpoints_calculation_spending_checked_rule_discount',
            [
                'container' => $container,
            ]
        );
        return $container->getCheckedRuleDiscount();
    }

    /**
     * Get points used to spend for checked rules
     *
     * @return int
     */
    public function getCheckedRulePoint()
    {
        $container = new DataObject(
            [
                'checked_rule_point' => 0
            ]
        );
        $this->_eventManager->dispatch(
            'rewardpoints_calculation_spending_checked_rule_point',
            [
                'container' => $container,
            ]
        );
        return $container->getCheckedRulePoint();
    }

    /**
     * Get discount (base currency) by points that spent with slider rule type
     *
     * @return float
     */
    public function getSliderRuleDiscount()
    {
        $quote = $this->getQuote();
        /* @var SerializerInterface $serialize */
        $serialize = ObjectManager::getInstance()->get(SerializerInterface::class);
        if ($quote) {
            $rewardSalesRules = $quote->getRewardSalesRules() ?
                $serialize->unserialize($quote->getRewardSalesRules()) : [];
            if (is_array($rewardSalesRules)
                && isset($rewardSalesRules['base_discount'])
                && $quote->getData('use_point')) {
                return $rewardSalesRules['base_discount'];
            }
        }
        return 0;
    }

    /**
     * Get points used to spend by slider rule
     *
     * @return int
     */
    public function getSliderRulePoint()
    {
        $quote = $this->getQuote();
        if ($quote) {
            /* @var SerializerInterface $serialize */
            $serialize = ObjectManager::getInstance()->get(SerializerInterface::class);
            $rewardSalesRules = $quote->getRewardSalesRules() ?
                $serialize->unserialize($quote->getRewardSalesRules()) : [];
            if (is_array($rewardSalesRules)
                && isset($rewardSalesRules['use_point'])
                && $this->getQuote()->getData('use_point')) {
                return $rewardSalesRules['use_point'];
            }
        }
        return 0;
    }

    /**
     * Get total point spent by rules on shopping cart
     *
     * @return int
     */
    public function getTotalRulePoint()
    {
        return $this->getCheckedRulePoint() + $this->getSliderRulePoint();
    }

    /**
     * Get quote spending rule by RuleID
     *
     * @param int|string $ruleId
     * @return DataObject
     */
    public function getQuoteRule($ruleId = 'rate')
    {
        $cacheKey = "quote_rule_model:$ruleId";

        if (!$this->hasCache($cacheKey)) {
            if ($ruleId == 'rate') {
                $this->saveCache($cacheKey, $this->getSpendingRateAsRule());
                return $this->getCache($cacheKey);
            }
            $container = new DataObject(
                [
                    'quote_rule_model' => null
                ]
            );
            $this->_eventManager->dispatch(
                'rewardpoints_calculation_spending_quote_rule_model',
                [
                    'container' => $container,
                    'rule_id' => $ruleId,
                ]
            );

            $this->saveCache($cacheKey, $container->getQuoteRuleModel());
        }
        return $this->getCache($cacheKey);
    }

    /**
     * Get Spend Rates as a special rule (with id = 'rate')
     *
     * @return DataObject|false
     */
    public function getSpendingRateAsRule()
    {
        $customerGroupId = $this->getCustomerGroupId();
        $websiteId = $this->getWebsiteId();
        $cacheKey = "rate_as_rule:$customerGroupId:$websiteId";
//        if ($this->hasCache($cacheKey)) {
//            return $this->getCache($cacheKey);
//        }
        $rate = $this->_rateModelFactory->create()->getRate(
            Rate::POINT_TO_MONEY,
            $customerGroupId,
            $websiteId
        );
        if ($rate && $rate->getId()) {
            /**
             * end update
             */
            $this->saveCache(
                $cacheKey,
                new DataObject(
                    [
                        'points_spended' => $rate->getPoints(),
                        'base_rate' => $rate->getMoney(),
                        'simple_action' => 'by_price',
                        'id' => 'rate',
                        'max_price_spended_type' => $rate->getMaxPriceSpendedType(), //Hai.Tran 13/11
                        'max_price_spended_value' => $rate->getMaxPriceSpendedValue()//Hai.Tran 13/11
                    ]
                )
            );
        } else {
            $this->saveCache($cacheKey, false);
        }
        return $this->getCache($cacheKey);
    }

    /**
     * Get max points can used to spend for a quote
     *
     * @param DataObject $rule
     * @param Quote $quote
     * @return int
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getRuleMaxPointsForQuote($rule, $quote)
    {
        $cacheKey = "rule_max_points_for_quote:{$rule->getId()}";
//        if ($this->hasCache($cacheKey)) {
//            return $this->getCache($cacheKey);
//        }
        if ($rule->getId() == 'rate') {
            if ($rule->getBaseRate() && $rule->getPointsSpended()) {
                $quoteTotal = $this->getQuoteBaseTotalForRewardSlider($quote);
                //Hai.Tran 13/11/2013 add limit spend theo quote total
                //Tinh max point cho max total
                $maxPrice = $rule->getMaxPriceSpendedValue() > 0 ? $rule->getMaxPriceSpendedValue() : 0;
                if ($rule->getMaxPriceSpendedType() == 'by_price') {
                    $maxPriceSpend = $maxPrice;
                } elseif ($rule->getMaxPriceSpendedType() == 'by_percent') {
                    $maxPriceSpend = $quoteTotal * $maxPrice / 100;
                } else {
                    $maxPriceSpend = 0;
                }
                if ($quoteTotal > $maxPriceSpend && $maxPriceSpend > 0) {
                    $quoteTotal = $maxPriceSpend;
                }
                //End Hai.Tran 13/11/2013 add limit spend theo quote total

                $maxPoints = ceil(($quoteTotal - $this->getCheckedRuleDiscount()) / $rule->getBaseRate())
                    * $rule->getPointsSpended();
                if ($maxPerOrder = $this->getMaxPointsPerOrder($quote->getStoreId())) {
                    $maxPerOrder -= $this->getPointItemSpent();
                    $maxPerOrder -= $this->getCheckedRulePoint();
                    if ($maxPerOrder > 0) {
                        $maxPoints = min($maxPoints, $maxPerOrder);
                        $maxPoints = floor($maxPoints / $rule->getPointsSpended()) * $rule->getPointsSpended();
                    } else {
                        $maxPoints = 0;
                    }
                }
                $this->saveCache($cacheKey, $maxPoints);
            }
        } else {
            $container = new DataObject(
                [
                    'rule_max_points' => 0
                ]
            );
            $this->_eventManager->dispatch(
                'rewardpoints_calculation_spending_rule_max_points',
                [
                    'rule' => $rule,
                    'quote' => $quote,
                    'container' => $container,
                ]
            );
            $this->saveCache($cacheKey, $container->getRuleMaxPoints());
        }
        if (!$this->hasCache($cacheKey)) {
            $this->saveCache($cacheKey, 0);
        }
        return $this->getCache($cacheKey);
    }

    /**
     * Get Quote Base Total For Reward Slider
     *
     * @param Quote $quote
     * @return float
     */
    public function getQuoteBaseTotalForRewardSlider($quote)
    {
        $quoteTotal = 0;
        /** @var Item $item */
        foreach ($quote->getAllItems() as $item) {
            if ($item->getParentItemId()) {
                continue;
            }
            $quoteTotal += $item->getBasePriceInclTax() * $item->getQty();
        }
        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }
        if ($this->_scopeConfig->getConfig(self::XML_PATH_SPEND_FOR_SHIPPING, $quote->getStoreId())) {
            $shippingAmount = $address->getShippingAmountForDiscount();
            if ($shippingAmount !== null) {
                $baseShippingAmount = $address->getBaseShippingAmountForDiscount();
            } else {
                $baseShippingAmount = $address->getBaseShippingAmount();
            }
            $quoteTotal += $baseShippingAmount;
        }

        return $quoteTotal;
    }

    /**
     * Get discount for quote when a rule is applied and recalculate real point used
     *
     * @param Quote $quote
     * @param DataObject $rule
     * @param int $points
     * @return float
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getQuoteRuleDiscount($quote, $rule, &$points)
    {
        $cacheKey = "quote_rule_discount:{$rule->getId()}:$points";

        if ($this->hasCache($cacheKey)) {
            return $this->getCache($cacheKey);
        }

        if ($rule->getId() == 'rate') {
            if ($rule->getBaseRate() && $rule->getPointsSpended()) {
                $baseTotal = $this->getQuoteBaseTotal($quote) - $this->getCheckedRuleDiscount();

                /* Brian 26/1/2015 */
                $maxDiscountSpended = 0;
                if ($maxPriceSpended = $rule->getMaxPriceSpendedValue()) {
                    if ($rule->getMaxPriceSpendedType() == 'by_price') {
                        $maxDiscountSpended = $maxPriceSpended;
                    } elseif ($rule->getMaxPriceSpendedType() == 'by_percent') {
                        $maxDiscountSpended = $this->getQuoteBaseTotal($quote) * $maxPriceSpended / 100;
                    }
                }
                if ($maxDiscountSpended > 0) {
                    $baseTotal = min($maxDiscountSpended, $baseTotal);
                }
                /* end */
                $maxPoints = ceil($baseTotal / $rule->getBaseRate()) * $rule->getPointsSpended();

                if ($maxPerOrder = $this->getMaxPointsPerOrder($quote->getStoreId())) {
                    $maxPerOrder -= $this->getPointItemSpent();
                    $maxPerOrder -= $this->getCheckedRulePoint();
                    if ($maxPerOrder > 0) {
                        $maxPoints = min($maxPoints, $maxPerOrder);
                    } else {
                        $maxPoints = 0;
                    }
                }

                $points = min($points, $maxPoints);

                $points = floor($points / $rule->getPointsSpended()) * $rule->getPointsSpended();
                $this->saveCache(
                    $cacheKey,
                    min($points * $rule->getBaseRate() / $rule->getPointsSpended(), $baseTotal)
                );
            } else {
                $points = 0;
                $this->saveCache($cacheKey, 0);
            }
        } else {
            $container = new DataObject(
                [
                    'quote_rule_discount' => 0,
                    'points' => $points
                ]
            );
            $this->_eventManager->dispatch(
                'rewardpoints_calculation_spending_quote_rule_discount',
                [
                    'rule' => $rule,
                    'quote' => $quote,
                    'container' => $container,
                ]
            );
            $points = $container->getPoints();
            $this->saveCache($cacheKey, $container->getQuoteRuleDiscount());
        }
        return $this->getCache($cacheKey);
    }

    /**
     * Is Use Max Points Default
     *
     * @param null|int|string $store
     * @return mixed
     */
    public function isUseMaxPointsDefault($store = null)
    {
        return $this->_scopeConfig->getConfig(self::XML_PATH_MAX_POINTS_DEFAULT, $store);
    }

    /**
     * Is Use Point
     *
     * @return mixed
     */
    public function isUsePoint()
    {
        $quote = $this->getQuote();
        if ($quote) {
            return $this->getQuote()->getData('use_point');
        } else {
            return 0;
        }
    }
}
