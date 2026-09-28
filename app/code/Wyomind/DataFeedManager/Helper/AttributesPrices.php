<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Helper;

use Wyomind\DataFeedManager\Model\ResourceModel\PriceIndex;
/**
 * Attributes management
 */
class AttributesPrices extends \Magento\Framework\App\Helper\AbstractHelper implements \Wyomind\DataFeedManager\Helper\AttributesInterface
{
    public $_coreDate = null;
    public $_localeDate = null;
    public $_customerSession = null;
    /**
     * @var \Magento\CatalogRule\Model\ResourceModel\RuleFactory|null
     */
    protected $_ruleFactory = null;
    public $_framework = null;
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory|null
     */
    protected $_salesRuleCollectionFactory = null;
    public $priceIndex;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        \Magento\Framework\App\Helper\Context $context,
        /** @delegation off */
        \Magento\CatalogRule\Model\ResourceModel\RuleFactory $ruleFactory,
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $salesRuleCollectionFactory
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        $this->_ruleFactory = $ruleFactory;
        $this->_salesRuleCollectionFactory = $salesRuleCollectionFactory;
        parent::__construct($context);
    }
    /**
     * @param $conditions
     * @param $item
     * @return bool
     */
    public function validateCond($conditions, $item)
    {
        if ($item->getProductId() == '') {
            $item->setProductId($item->getId());
        }
        $all = $conditions->getAggregator() === 'all';
        $true = (bool) $conditions->getValue();
        $found = $all;
        foreach ($conditions->getConditions() as $cond) {
            $validated = $cond->validate($item);
            if ($all && !$validated || !$all && $validated) {
                $found = $validated;
                break;
            }
        }
        if ($found && $true) {
            return true;
        } elseif (!$found && !$true) {
            return true;
        }
        return false;
    }
    /**
     * {g_sale_price} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return string g:sale_price + g:sale_price_effective_date xml tags
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function price($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        if (isset($options['from_index'])) {
            return $this->fromIndex('price', $model, $options, $item, $reference);
        }
        $timestamp = $this->_localeDate->scopeDate($model->params['store_id']);
        $websiteId = $model->storeManager->getStore()->getWebsiteId();
        $customerGrpId = $this->_customerSession->getCustomerGroupId();
        $rulePrice = $this->_ruleFactory->create()->getRulePrice($timestamp, $websiteId, $customerGrpId, $item->getId());
        if ($rulePrice !== false) {
            $priceRules = sprintf('%.2f', round($rulePrice, 2));
        } else {
            $priceRules = $item->getPrice();
        }
        // From date defined but To date not defined
        if ($item->getSpecialFromDate() && !$item->getSpecialToDate()) {
            // If valid promo date
            if ($item->getSpecialFromDate() <= $this->_coreDate->date('Y-m-d H:i:s')) {
                if ($item->getTypeID() == 'bundle') {
                    $bundlePrice = $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                    if ($item->getSpecialPrice() > 0) {
                        $price = $bundlePrice * $item->getSpecialPrice() / 100;
                    } else {
                        $price = $item->getMinPrice();
                    }
                } else {
                    // If special price exists
                    $price = $item->getSpecialPrice() && $item->getSpecialPrice() < $item->getPrice() ? $item->getSpecialPrice() : $priceRules;
                }
            } else {
                // else normal price
                if ($item->getTypeID() == 'bundle') {
                    $price = $item->getMinPrice();
                } else {
                    $price = $priceRules;
                }
            }
        } elseif ($item->getSpecialFromDate() && $item->getSpecialToDate()) {
            // From date and To date defined
            $specialToDate = $this->_coreDate->date('Y-m-d 00:00:00', strtotime($item->getSpecialToDate()) + 24 * 60 * 60);
            // If valid promo date
            if ($item->getSpecialFromDate() <= $this->_coreDate->date('Y-m-d H:i:s') && $this->_coreDate->date('Y-m-d H:i:s') < $specialToDate) {
                if ($item->getTypeID() == 'bundle') {
                    $bundlePrice = $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                    if ($item->getSpecialPrice() > 0) {
                        $price = $bundlePrice * $item->getSpecialPrice() / 100;
                    } else {
                        $price = $item->getMinPrice();
                    }
                } else {
                    // If special price exists
                    $price = $item->getSpecialPrice() && $item->getSpecialPrice() < $item->getPrice() ? $item->getSpecialPrice() : $priceRules;
                }
            } else {
                // else normal price
                if ($item->getTypeID() == 'bundle') {
                    $price = $item->getMinPrice();
                } else {
                    $price = $priceRules;
                }
            }
        } else {
            if ($item->getTypeID() == 'bundle') {
                $bundlePrice = $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                if ($item->getSpecialPrice() > 0) {
                    $price = $bundlePrice * $item->getSpecialPrice() / 100;
                } else {
                    $price = $item->getMinPrice();
                }
            } else {
                // If special price exists
                $price = $item->getSpecialPrice() && $item->getSpecialPrice() < $item->getPrice() ? $item->getSpecialPrice() : $priceRules;
            }
        }
        if ($priceRules !== false) {
            if ($priceRules < $price) {
                $value = $priceRules;
            } else {
                $value = $price;
            }
        } else {
            $value = $price;
        }
        $value = $this->applyTaxThenCurrency($model, $item->getTaxClassId(), number_format((float) $value, 2, '.', ''), $options, $reference);
        if ($value <= 0) {
            return null;
        }
        return $value;
    }
    /**
     * {tier_price} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return float the tier price of the product
     */
    public function tierPrice($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        if (isset($options['from_index'])) {
            return $this->fromIndex('tier_price', $model, $options, $item, $reference);
        }
        $groupId = !isset($options['customer_group_id']) ? 32000 : $options['customer_group_id'];
        if ($groupId == '*') {
            $groupId = 32000;
        }
        $index = !isset($options['index']) ? 0 : $options['index'];
        if (!isset($model->tierPrices[$item->getId()])) {
            return '';
        }
        $tierPrices = $model->tierPrices[$item->getId()];
        if (!isset($tierPrices[$groupId])) {
            return '';
        }
        if ($index < 0) {
            $index = abs($index) - 1;
            $tierPrices[$groupId] = array_reverse($tierPrices[$groupId]);
        }
        if (isset($tierPrices[$groupId])) {
            if (isset($tierPrices[$groupId][$index])) {
                $price = $tierPrices[$groupId][$index]['value'];
                if ($price > 0) {
                    $value = $this->applyTaxThenCurrency($model, $item->getTaxClassId(), $price, $options, $reference);
                } elseif ($tierPrices[$groupId][$index]['percent'] != null) {
                    $price = $this->finalPrice($model, $options, $product, $reference) * ((100 - $tierPrices[$groupId][$index]['percent']) / 100);
                    $value = $this->applyTaxThenCurrency($model, 0, $price, $options, $reference);
                } else {
                    $value = 0;
                }
            } else {
                $value = 0;
            }
        } else {
            $value = 0;
        }
        if ($value <= 0) {
            return null;
        }
        return $value;
    }
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return string
     */
    public function tierPriceQty($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $groupId = !isset($options['customer_group_id']) ? 32000 : $options['customer_group_id'];
        if ($groupId == '*') {
            $groupId = 32000;
        }
        $index = !isset($options['index']) ? 0 : $options['index'];
        if (!isset($model->tierPrices[$item->getId()])) {
            return '';
        }
        $tierPrices = $model->tierPrices[$item->getId()];
        if (!isset($tierPrices[$groupId])) {
            return '';
        }
        if ($index < 0) {
            $index = abs($index) - 1;
            $tierPrices[$groupId] = array_reverse($tierPrices[$groupId]);
        }
        $qty = null;
        if (isset($tierPrices[$groupId][$index])) {
            $qty = $tierPrices[$groupId][$index]['qty'];
        }
        return $qty;
    }
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return string
     */
    public function salePriceEffectiveDate($model, $options, $product, $reference)
    {
        unset($options);
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $offsetHours = $this->_coreDate->getGmtOffset('hours');
        if ($offsetHours > 0) {
            $sign = '+';
            $offset = str_pad(abs(floor($offsetHours)), 2, 0, STR_PAD_LEFT) . '' . str_pad((abs($offsetHours) - floor(abs($offsetHours))) * 60, 2, 0, STR_PAD_LEFT);
        } else {
            $sign = '-';
            $offset = str_pad(abs(floor($offsetHours)), 2, 0, STR_PAD_LEFT) . '' . str_pad((abs($offsetHours) - floor(abs($offsetHours))) * 60, 2, 0, STR_PAD_LEFT);
        }
        $from = substr(str_replace(' ', 'T', (string) $item->getSpecialFromDate()), 0, -3);
        if (!$item->getSpecialToDate()) {
            $specialToDate = $this->_coreDate->date('Y-m-d 00:00:00', time() + 365 * 24 * 60 * 60);
        } else {
            $specialToDate = $this->_coreDate->date('Y-m-d 00:00:00', strtotime((string) $item->getSpecialToDate()) + 24 * 60 * 60);
        }
        $to = substr(str_replace(' ', 'T', $specialToDate), 0, -3);
        $value = '';
        if ($from && $to) {
            $value .= $from . $sign . $offset . '/' . $to . $sign . $offset;
        }
        return $value;
    }
    /**
     * {min_price} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return float the min price for bundle / configurable products
     */
    public function minPrice($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        if (isset($options['from_index'])) {
            return $this->fromIndex('min_price', $model, $options, $item, $reference);
        }
        $price = $item->getMinPrice();
        $value = $this->applyTaxThenCurrency($model, $item->getTaxClassId(), $price, $options, $reference);
        if ($value <= 0) {
            return null;
        }
        return $value;
    }
    /**
     * {max_price} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return float the max price for bundle / configurable products
     */
    public function maxPrice($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        if (isset($options['from_index'])) {
            return $this->fromIndex('max_price', $model, $options, $item, $reference);
        }
        $price = $item->getMaxPrice();
        $value = $this->applyTaxThenCurrency($model, $item->getTaxClassId(), $price, $options, $reference);
        if ($value <= 0) {
            return null;
        }
        return $value;
    }
    /**
     * {special_price} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return float the special of a product if it exists, the normal price else
     */
    public function specialPrice($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $price = null;
        if ($item->getSpecialFromDate() && !$item->getSpecialToDate()) {
            if ($item->getSpecialFromDate() <= $this->_coreDate->date('Y-m-d H:i:s')) {
                if ($item->getTypeId() == 'bundle') {
                    $bundlePrice = $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                    if ($item->getSpecialPrice() > 0) {
                        $price = $bundlePrice * $item->getSpecialPrice() / 100;
                    } else {
                        $price = $item->getMinPrice();
                    }
                    $price = number_format((float) $price, 2, ".", "");
                } else {
                    $price = $item->getSpecial_price();
                }
            }
        } elseif ($item->getSpecialFromDate() && $item->getSpecialToDate()) {
            $specialToDate = $this->_coreDate->date('Y-m-d 00:00:00', strtotime($item->getSpecialToDate()) + 24 * 60 * 60);
            if ($item->getSpecialFromDate() <= $this->_coreDate->date('Y-m-d H:i:s') && $this->_coreDate->date('Y-m-d H:i:s') < $specialToDate) {
                if ($item->getTypeId() == 'bundle') {
                    $bundlePrice = $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                    if ($item->getSpecialPrice() > 0) {
                        $price = $bundlePrice * $item->getSpecialPrice() / 100;
                    } else {
                        $price = $item->getMinPrice();
                    }
                    $price = number_format((float) $price, 2, ".", "");
                } else {
                    $price = $item->getSpecial_price();
                }
            }
        } else {
            if ($item->getTypeId() == 'bundle') {
                $bundlePrice = $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
                if ($item->getSpecialPrice() > 0) {
                    $price = $bundlePrice * $item->getSpecialPrice() / 100;
                } else {
                    $price = $item->getMinPrice();
                }
                $price = number_format((float) $price, 2, ".", "");
            } else {
                $price = $item->getSpecial_price();
            }
        }
        if ($price > 0) {
            $value = $this->applyTaxThenCurrency($model, $item->getTaxClassId(), $price, $options, $reference);
        } else {
            $value = '';
        }
        if ($value <= 0) {
            return null;
        }
        return $value;
    }
    /**
     * {price_rules} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return float|string the price defined by catalog price rules if existing, special price else, normal price else
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function priceRules($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $timestamp = $this->_localeDate->scopeDate($model->params['store_id']);
        $websiteId = $model->storeManager->getStore()->getWebsiteId();
        $customerGrpId = $this->_customerSession->getCustomerGroupId();
        /** @var \Magento\CatalogRule\Model\ResourceModel\Rule $rulePrice */
        $rulePrice = $this->_ruleFactory->create()->getRulePrice($timestamp, $websiteId, $customerGrpId, $item->getId());
        if ($rulePrice !== false) {
            $priceRules = sprintf('%.2f', round($rulePrice, 2));
        } else {
            $priceRules = '';
        }
        if ($priceRules != '') {
            $value = $this->applyTaxThenCurrency($model, $item->getTaxClassId(), $priceRules, $options, $reference);
        } else {
            $value = '';
        }
        if ($value <= 0) {
            return null;
        }
        return $value;
    }
    public function catalogRuleId($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $timestamp = $this->_localeDate->scopeDate($model->params['store_id']);
        $websiteId = $model->storeManager->getStore()->getWebsiteId();
        $customerGrpId = $this->_customerSession->getCustomerGroupId();
        /** @var \Magento\CatalogRule\Model\ResourceModel\Rule $rulePrice */
        $ruleId = $this->_ruleFactory->create()->getRulesFromProduct($timestamp->format('Y-m-d H:i:s'), $websiteId, $customerGrpId, $item->getId());
        if ($ruleId !== false && count($ruleId) > 0) {
            return $ruleId[0]['rule_id'];
        } else {
            return;
        }
    }
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function hasSalePrice($model, $options, $product, $reference)
    {
        return $this->salePrice($model, $options, $product, $reference) != '';
    }
    /**
     * {is_special_price} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return int|string 0 if there is a special price, 0 else
     */
    public function hasSpecialPrice($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $true = !isset($options['yes']) ? 1 : $options['yes'];
        $false = !isset($options['no']) ? 0 : $options['no'];
        if ($item->getSpecialFromDate() && !$item->getSpecialToDate()) {
            if ($item->getSpecialFromDate() <= $this->_coreDate->date('Y-m-d H:i:s')) {
                if ($item->getTypeID() == 'bundle') {
                    $value = $item->getSpecialPrice() ? $true : $false;
                } else {
                    $value = $item->getSpecialPrice() && $item->getSpecialPrice() < $item->getPrice() ? $true : $false;
                }
            } else {
                if ($item->getTypeID() == 'bundle') {
                    $value = $false;
                } else {
                    $value = $false;
                }
            }
        } elseif ($item->getSpecialFromDate() && $item->getSpecialToDate()) {
            $specialToDate = $this->_coreDate->date('Y-m-d 00:00:00', strtotime($item->getSpecialToDate()) + 24 * 60 * 60);
            if ($item->getSpecialFromDate() <= $this->_coreDate->date('Y-m-d H:i:s') && $this->_coreDate->date('Y-m-d H:i:s') < $specialToDate) {
                if ($item->getTypeID() == 'bundle') {
                    $value = $item->getSpecialPrice() ? $true : $false;
                } else {
                    $value = $item->getSpecialPrice() && $item->getSpecialPrice() < $item->getPrice() ? $true : $false;
                }
            } else {
                if ($item->getTypeID() == 'bundle') {
                    $value = $false;
                } else {
                    $value = $false;
                }
            }
        } else {
            if ($item->getTypeID() == 'bundle') {
                $value = $item->getSpecialPrice() ? $true : $false;
            } else {
                $value = $item->getSpecialPrice() && $item->getSpecialPrice() < $item->getPrice() ? $true : $false;
            }
        }
        return $value;
    }
    /**
     * {price} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return float the price of the product
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function salePrice($model, $options, $product, $reference)
    {
        $priceRules = $this->priceRules($model, $options, $product, $reference);
        $specialPrice = $this->specialPrice($model, $options, $product, $reference);
        if ($priceRules != '' && $specialPrice != '') {
            if ($priceRules < $specialPrice) {
                return $priceRules;
            } else {
                return $specialPrice;
            }
        } elseif ($priceRules != '') {
            return $priceRules;
        } elseif ($specialPrice != '') {
            return $specialPrice;
        } else {
            return '';
        }
    }
    /**
     * {normal_price} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return float the normal price of the product
     */
    public function normalPrice($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $price = $item->getPrice();
        if ($item->getTypeId() == "bundle") {
            return $item->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
        }
        $value = $this->applyTaxThenCurrency($model, $item->getTaxClassId(), $price, $options, $reference);
        if ($value <= 0) {
            return null;
        }
        return $value;
    }
    /**
     * {final_price} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return string formatted version of the final price
     */
    public function finalPrice($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        if (isset($options['from_index'])) {
            return $this->fromIndex('final_price', $model, $options, $item, $reference);
        }
        $price = $item->getFinalPrice();
        $value = $this->applyTaxThenCurrency($model, $item->getTaxClassId(), $price, $options, $reference);
        if ($value <= 0) {
            return null;
        }
        return $value;
    }
    public $priceIndexCache = [];
    public function fromIndex($type, $model, $options, $item, $reference)
    {
        if (!isset($this->priceIndexCache[$item->getId()])) {
            $this->priceIndexCache = [];
            // reset array to avoid memory stack overflow
            $this->priceIndexCache[$item->getId()] = $this->priceIndex->getPrices(1, $item->getId());
        }
        $customerGroupId = $options['customer_group_id'] ?? 0;
        if (isset($this->priceIndexCache[$item->getId()][$customerGroupId][$type])) {
            $price = $this->priceIndexCache[$item->getId()][$customerGroupId][$type];
            return $this->applyTaxThenCurrency($model, $item->getTaxClassId(), $price, $options, $reference);
        }
        return "";
    }
    /**
     * Apply vat rate and currency to a price
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param int $taxClassId the tax class id
     * @param float $price original price
     * @param array $options attribute options
     * @param string $reference parent reference
     * @return float the final price
     */
    public function applyTaxThenCurrency($model, $taxClassId, $price, $options, $reference)
    {
        unset($reference);
        $vat = !isset($options['vat_rate']) ? false : $options['vat_rate'];
        $currency = !isset($options['currency']) ? $model->defaultCurrency : $options['currency'];
        $valueTax = $this->applyTax($model, $price, $model->priceIncludesTax, $taxClassId, $vat);
        $valueCur = $this->applyCurrencyRate($model, $valueTax, $currency);
        $value = number_format((float) $valueCur, 2, '.', '');
        return $value;
    }
    /**
     * Apply a currency rate
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param float $price
     * @param string $currency
     * @return float
     */
    public function applyCurrencyRate($model, $price, $currency)
    {
        $currencies = $model->listOfCurrencies;
        if (isset($currencies[$currency])) {
            return $price * $currencies[$currency];
        } else {
            return $price;
        }
    }
    /**
     * Apply a vat tax rate
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param float $priceOrig the original price
     * @param boolean $priceIncludeTax
     * @param int $taxClassId
     * @param bool|float $vat apply VAT ?
     * @return float
     */
    public function applyTax($model, $priceOrig, $priceIncludeTax, $taxClassId, $vat = false)
    {
        $rates = $model->taxRates;
        $price = number_format((float) $priceOrig, 2, '.', '');
        if ($vat === false) {
            // $vat=false -> automatic
            if (!$priceIncludeTax && isset($rates[$taxClassId])) {
                // If multiple VAT return VAT exclusive price
                if (count($rates[$taxClassId]) > 1) {
                    return $price;
                } else {
                    // If unique VAT > TTC price calculated
                    return $price * ($rates[$taxClassId][0]['rate'] / 100 + 1);
                }
            } else {
                return $price;
            }
        } elseif ($vat === '0') {
            // $vat=='0' -> exclude VAT
            if ($priceIncludeTax && isset($rates[$taxClassId])) {
                // case 1 : VAT inclusive price > extract
                if (count($rates[$taxClassId]) > 1) {
                    // If multiple VAT return VAT exclusive price
                    return $price;
                } else {
                    // If unique VAT > remove the VAT to price
                    return 100 * $price / (100 + $rates[$taxClassId][0]['rate']);
                }
            } else {
                // case 2 : VAT exclusive price
                return $price;
            }
        } else {
            // $vat==true -> force VAT
            if (is_numeric($vat)) {
                // $vat is_numeric
                if ($taxClassId != 0) {
                    // If VAT calculation is forced on taxed product
                    return $price * ($vat / 100 + 1);
                } elseif ($taxClassId == 0) {
                    //If VAT calculation is forced on not taxed product
                    return $price;
                }
            } else {
                // $vat is_string
                $vat = explode('/', (string) $vat);
                $rateToApply = 0;
                $rateToRemove = false;
                if (substr($vat[0], 0, 1) == '-') {
                    $vat[0] = substr($vat[0], 1);
                    $rateToRemove = true;
                }
                if (isset($rates[$taxClassId])) {
                    foreach ($rates[$taxClassId] as $rate) {
                        if ($rate['country'] == $vat[0]) {
                            if (!isset($vat[1]) || $rate['code'] == $vat[1]) {
                                $rateToApply = $rate['rate'];
                                break;
                            }
                        }
                    }
                    if (!$rateToRemove) {
                        return $price * ($rateToApply / 100 + 1);
                    } else {
                        return 100 * $price / (100 + $rateToApply);
                    }
                } else {
                    return $price;
                }
            }
        }
    }
    /**
     * {base_price} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return float the base price for bundle / configurable products
     */
    public function basePrice($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $price = $item->getBasePrice();
        $value = $this->applyTaxThenCurrency($model, $item->getTaxClassId(), $price, $options, $reference);
        if ($value <= 0) {
            return null;
        }
        return $value;
    }
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return string
     */
    public function promotionId($model, $options, $product, $reference)
    {
        if (!$this->_framework->moduleIsEnabled('Wyomind_GoogleMerchantPromotions')) {
            return '';
        }
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $notProceeded = ['Magento\\SalesRule\\Model\\Rule\\Condition\\Product\\Subselect', 'Magento\\SalesRule\\Model\\Rule\\Condition\\Address'];
        $rules = $this->_salesRuleCollectionFactory->create();
        $rules->addFieldToFilter('transferable_to_google_merchant', 1);
        foreach ($rules as $rule) {
            if ($rule->getIsActive()) {
                $conditions = $rule->getConditions();
                $all = $conditions->getAggregator() === 'all';
                $true = (bool) $conditions->getValue();
                $rtnCond = $all ? true : false;
                $rtnCond = !count($conditions->getConditions()) ? true : $rtnCond;
                foreach ($conditions->getConditions() as $cond) {
                    if (!in_array($cond->getType(), $notProceeded)) {
                        if ($cond->getType() == 'Magento\\SalesRule\\Model\\Rule\\Condition\\Product\\Found') {
                            $validated = $this->validateCond($cond, $item);
                        } else {
                            $validated = $cond->validate($item);
                        }
                        if ($all && $validated !== $true) {
                            $rtnCond = false;
                        } elseif (!$all && $validated === $true) {
                            $rtnCond = true;
                            break;
                        }
                    } else {
                        $rtnCond = false;
                    }
                }
                $actions = $rule->getActions();
                $all = $actions->getAggregator() === 'all';
                $true = (bool) $actions->getValue();
                $rtnAct = $all ? true : false;
                $rtnAct = !count($actions->getConditions()) ? true : $rtnAct;
                foreach ($actions->getConditions() as $act) {
                    $validated = $act->validate($item);
                    if ($all && $validated !== $true) {
                        $rtnAct = false;
                    } elseif (!$all && $validated === $true) {
                        $rtnAct = true;
                        break;
                    }
                }
                if ($rtnAct && $rtnCond) {
                    return $rule->getData('rule_id');
                }
            }
        }
        return '';
    }
    /**
     * @param $attributeCall
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return float|mixed|string
     */
    public function proceedGeneric($attributeCall, $model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $value = $this->applyTaxThenCurrency($model, $item->getTaxClassId(), number_format((float) $item->getData($attributeCall['property']), 2, '.', ''), $options, $reference);
        return $value;
    }
}