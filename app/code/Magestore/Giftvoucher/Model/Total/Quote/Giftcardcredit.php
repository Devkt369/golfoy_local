<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Giftvoucher\Model\Total\Quote;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Helper\Data;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\Config;
use Magestore\Giftvoucher\Model\Credit;

/**
 * Giftvoucher Total Quote Giftcardcredit Model
 *
 * @author      Magestore Developer
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Giftcardcredit extends AbstractTotal
{
    
    protected $_hiddentBaseDiscount = 0;
    protected $_hiddentDiscount = 0;

    /**
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magestore\Giftvoucher\Helper\Data
     */
    protected $_giftvoucherHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var State
     */
    protected $_appState;

    /**
     * @var Credit
     */
    protected $_creditModel;

    /**
     * @var Data
     */
    protected $_helperTax;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Giftcardcredit constructor.
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param \Magestore\Giftvoucher\Helper\Data $giftvoucherHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param Session $customerSession
     * @param State $appState
     * @param Credit $creditModel
     * @param Data $helperTax
     * @param PriceCurrencyInterface $priceCurrency
     * @param ObjectManagerInterface $objectManager
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        \Magestore\Giftvoucher\Helper\Data $giftvoucherHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        Session $customerSession,
        State $appState,
        Credit $creditModel,
        Data $helperTax,
        PriceCurrencyInterface $priceCurrency,
        ObjectManagerInterface $objectManager
    ) {
        $this->_eventManager = $eventManager;
        $this->_storeManager = $storeManager;
        $this->_giftvoucherHelper = $giftvoucherHelper;
        $this->_checkoutSession = $checkoutSession;
        $this->_sessionQuote = $sessionQuote;
        $this->_customerSession = $customerSession;
        $this->_appState = $appState;
        $this->_creditModel = $creditModel;
        $this->_helperTax = $helperTax;
        $this->priceCurrency = $priceCurrency;
        $this->_objectManager = $objectManager;
        $this->setCode('giftcardcredit');
    }

    /**
     * Collect totals process.
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     * @internal param \Magento\Quote\Model\Quote\Address $address
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);
        $address = $shippingAssignment->getShipping()->getAddress();
        $applyGiftAfterTax =
            (bool) $this->_giftvoucherHelper->getGeneralConfig('apply_after_tax', $quote->getStoreId());
        if ($applyGiftAfterTax) {
            return $this;
        }
        
        if ($quote->isVirtual() && $address->getAddressType() == 'shipping') {
            return $this;
        }
        if (!$quote->isVirtual() && $address->getAddressType() == 'billing') {
            return $this;
        }

        if (!$this->_giftvoucherHelper->getGeneralConfig('enablecredit', $quote->getStoreId())) {
            $quote->setBaseUseGiftCreditAmount(0);
            $quote->setUseGiftCreditAmount(0);
            $quote->save();

            return $this;
        }
        if ($this->_appState->getAreaCode() != Area::AREA_FRONTEND) {
            $customerId = $quote->getCustomerId();
            $credit = $this->_creditModel->load(
                $customerId,
                'customer_id'
            );
            $baseBalance =  $credit->getBalance();
        } else {
            $customerId = $quote->getCustomerId();
        }
        
        if (!$quote->getUseGiftCardCredit()) {
            return $this;
        }

        $store = $quote->getStore();
        
        if (!isset($baseBalance)) {
            $baseBalance = $quote->getGiftcreditBalance();
        }
        if ($baseBalance < 0.0001) {
            $quote->setBaseUseGiftCreditAmount(0);
            $quote->setUseGiftCreditAmount(0);
            $quote->save();

            return $this;
        }

        if ($quote->getMaxCreditUsed() > 0) {
            $baseBalance = min($baseBalance, $quote->getMaxCreditUsed());
        }
        $baseTotalDiscount = 0;
        foreach ($address->getAllItems() as $item) {
            if ($item->getParentItemId()) {
                continue;
            }
            if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                foreach ($item->getChildren() as $child) {
                    if (!$child->isDeleted() && $child->getProduct()->getTypeId() != 'giftvoucher') {
                        if ($this->_helperTax->priceIncludesTax()) {
                            $itemDiscount = $child->getRowTotalInclTax() - $child->getDiscountAmount();
                        } else {
                            $itemDiscount = $child->getBaseRowTotal() - $child->getBaseDiscountAmount();
                        }
                        $baseTotalDiscount += $itemDiscount;
                    }
                }
            } elseif ($item->getProduct()) {
                if (!$item->isDeleted() && $item->getProduct()->getTypeId() != 'giftvoucher') {
                    if ($this->_helperTax->priceIncludesTax()) {
                        $itemDiscount = $item->getRowTotalInclTax() - $item->getDiscountAmount();
                    } else {
                        $itemDiscount = $item->getBaseRowTotal() - $item->getBaseDiscountAmount();
                    }
                    $baseTotalDiscount += $itemDiscount;
                }
            }
        }
        if ($this->_giftvoucherHelper->getGeneralConfig('use_for_ship')) {
            if ($this->_helperTax->shippingPriceIncludesTax()) {
                $shipDiscount = $address->getShippingInclTax() - $address->getMagestoreBaseDiscountForShipping()
                - $address->getShippingDiscountAmount();
            } else {
                $shipDiscount = $address->getBaseShippingAmount() - $address->getMagestoreBaseDiscountForShipping()
                - $address->getBaseShippingDiscountAmount();
            }
            $baseTotalDiscount += $shipDiscount;
        }
       
        $baseDiscount =  min($baseTotalDiscount, $baseBalance);
        $discount = $this->priceCurrency->convert($baseDiscount);
        if ($baseTotalDiscount != 0) {
            $this->prepareGiftDiscountForItem(
                $total,
                $address,
                $baseDiscount / $baseTotalDiscount,
                $store,
                $baseDiscount
            );
        }
        if ($baseDiscount && $discount) {
            $quote->setBaseUseGiftCreditAmount($baseDiscount);
        }
        if (!$this->_giftvoucherHelper->getCheckoutSession()->getUseGiftCard()) {
            foreach ($address->getAllItems() as $item) {
                if ($item->getParentItemId()) {
                    continue;
                }
                if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                    foreach ($item->getChildren() as $child) {
                        if (!$child->isDeleted() && $child->getProduct()->getTypeId() != 'giftvoucher') {
                            $child->setDiscountAmount($child->getDiscountAmount()+$child->getUseGiftCreditAmount());
                            $child->setBaseDiscountAmount($child->getBaseDiscountAmount()
                                +$child->getBaseUseGiftCreditAmount());
                        }
                    }
                } elseif ($item->getProduct()) {
                    if (!$item->isDeleted() && $item->getProduct()->getTypeId() != 'giftvoucher') {
                        $item->setDiscountAmount($item->getDiscountAmount()+$item->getUseGiftCreditAmount());
                        $item->setBaseDiscountAmount($item->getBaseDiscountAmount()
                            +$item->getBaseUseGiftCreditAmount());
                    }
                }
            }
        }
        if ($baseDiscount && $discount) {
            $quote->setBaseUseGiftCreditAmount($baseDiscount);
            $quote->setUseGiftCreditAmount($discount);
            $quote->setGiftcardCreditAmount($baseDiscount);

            $total->setGiftcardCreditAmount($baseDiscount);
            $total->setBaseUseGiftCreditAmount($baseDiscount);
            $total->setUseGiftCreditAmount($discount);
            $total->setMagestoreBaseDiscount($total->getMagestoreBaseDiscount() + $baseDiscount);
            $total->setBaseGrandTotal($total->getBaseGrandTotal() - $baseDiscount);
            $total->setGrandTotal($total->getGrandTotal() - $discount);
            $total->setTotalAmount('giftcardcredit', -$discount);
            $total->setBaseTotalAmount('giftcardcredit', -$baseDiscount);
        }
        $quote->save();

        return $this;
    }

    /**
     * Fetch
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     */
    public function fetch(Quote $quote, Total $total)
    {
        $result = null;
        $applyGiftAfterTax =
            (bool) $this->_giftvoucherHelper->getGeneralConfig('apply_after_tax', $quote->getStoreId());
        if ($applyGiftAfterTax) {
            return $result;
        }
        
        $amount = $total->getUseGiftCreditAmount();
        if ($amount > 0) {
            $result = [
                'code' => $this->getCode(),
                'title' => __('Gift Card credit'),
                'value' => -$amount
            ];
        }
        return $result;
    }

    /**
     * Prepare Gift Discount For Item
     *
     * @param Total $total
     * @param Address $address
     * @param float $rateDiscount
     * @param StoreInterface $store
     * @param float $baseDiscount
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function prepareGiftDiscountForItem(
        Total $total,
        Address $address,
        $rateDiscount,
        $store,
        $baseDiscount
    ) {
        $taxCalculation = $this->_objectManager->create(Calculation::class);
        foreach ($address->getAllItems() as $item) {
            if ($item->getParentItemId()) {
                continue;
            }
            if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                foreach ($item->getChildren() as $child) {
                    if (!$child->isDeleted() && $child->getProduct()->getTypeId() != 'giftvoucher') {
                        if ($this->_helperTax->priceIncludesTax()) {
                            $itemDiscount = $child->getRowTotalInclTax() - $child->getMagestoreBaseDiscount()
                                - $child->getDiscountAmount();
                        } else {
                            $itemDiscount = $child->getBaseRowTotal() - $child->getMagestoreBaseDiscount()
                                - $child->getBaseDiscountAmount();
                        }
                        $child->setMagestoreBaseDiscount($child->getMagestoreBaseDiscount()
                            + $itemDiscount * $rateDiscount);
                        $child->setBaseUseGiftCreditAmount($child->getBaseUseGiftCreditAmount()
                            + $itemDiscount * $rateDiscount);
                        $child->setUseGiftCreditAmount($child->getUseGiftCreditAmount()
                            + $this->priceCurrency->convert($itemDiscount * $rateDiscount));
                        $baseTaxableAmount = $child->getBaseTaxableAmount();
                        $taxableAmount = $child->getTaxableAmount();

                        $child->setBaseTaxableAmount($child->getBaseTaxableAmount()
                            - $child->getBaseUseGiftCreditAmount());
                        $child->setTaxableAmount($child->getTaxableAmount() - $child->getUseGiftCreditAmount());

                        if ($this->_helperTax->priceIncludesTax()) {
                            $rate = $this->_giftvoucherHelper->getItemRateOnQuote($item->getProduct(), $store);
                            $hiddenBaseTaxBeforeDiscount = $taxCalculation
                                ->calcTaxAmount($baseTaxableAmount, $rate, true, false);
                            $hiddenTaxBeforeDiscount = $taxCalculation
                                ->calcTaxAmount($taxableAmount, $rate, true, false);

                            $hiddenBaseTaxAfterDiscount = $taxCalculation
                                ->calcTaxAmount($child->getBaseTaxableAmount(), $rate, true, false);
                            $hiddenTaxAfterDiscount = $taxCalculation
                                ->calcTaxAmount($child->getTaxableAmount(), $rate, true, false);

                            $hiddentBaseDiscount = $taxCalculation->round($hiddenBaseTaxBeforeDiscount)
                                - $taxCalculation->round($hiddenBaseTaxAfterDiscount);
                            $hiddentDiscount = $taxCalculation->round($hiddenTaxBeforeDiscount)
                                - $taxCalculation->round($hiddenTaxAfterDiscount);

                            $this->_hiddentBaseDiscount += $hiddentBaseDiscount;
                            $this->_hiddentDiscount += $hiddentDiscount;
                        }
                    }
                }
            } elseif ($item->getProduct()) {
                if (!$item->isDeleted() && $item->getProduct()->getTypeId() != 'giftvoucher') {
                    if ($this->_helperTax->priceIncludesTax()) {
                        $itemDiscount = $item->getRowTotalInclTax() - $item->getMagestoreBaseDiscount()
                            - $item->getDiscountAmount();
                    } else {
                        $itemDiscount = $item->getBaseRowTotal() - $item->getMagestoreBaseDiscount()
                            - $item->getBaseDiscountAmount();
                    }
                    $item->setMagestoreBaseDiscount($item->getMagestoreBaseDiscount() + $itemDiscount * $rateDiscount);
                    $item->setBaseUseGiftCreditAmount($item->getBaseUseGiftCreditAmount()
                        + $itemDiscount * $rateDiscount);
                    $item->setUseGiftCreditAmount($item->getUseGiftCreditAmount()
                        + $this->priceCurrency->convert($itemDiscount * $rateDiscount));

                    $baseTaxableAmount = $item->getBaseTaxableAmount();
                    $taxableAmount = $item->getTaxableAmount();
                    $item->setBaseTaxBeforeDiscount($item->getBaseTaxBeforeDiscount()
                        - $item->getBaseUseGiftCreditAmount());
                    $item->setTaxBeforeDiscount($item->getTaxBeforeDiscount() - $item->getUseGiftCreditAmount());

                    if ($this->_helperTax->priceIncludesTax()) {
                        $rate = $this->_giftvoucherHelper->getItemRateOnQuote($item->getProduct(), $store);
                        $hiddenBaseTaxBeforeDiscount = $taxCalculation
                            ->calcTaxAmount($baseTaxableAmount, $rate, true, false);
                        $hiddenTaxBeforeDiscount = $taxCalculation->calcTaxAmount($taxableAmount, $rate, true, false);

                        $hiddenBaseTaxAfterDiscount = $taxCalculation
                            ->calcTaxAmount($item->getBaseTaxableAmount(), $rate, true, false);
                        $hiddenTaxAfterDiscount = $taxCalculation
                            ->calcTaxAmount($item->getTaxableAmount(), $rate, true, false);

                        $hiddentBaseDiscount = $taxCalculation->round($hiddenBaseTaxBeforeDiscount)
                            - $taxCalculation->round($hiddenBaseTaxAfterDiscount);
                        $hiddentDiscount = $taxCalculation->round($hiddenTaxBeforeDiscount)
                            - $taxCalculation->round($hiddenTaxAfterDiscount);

                        $this->_hiddentBaseDiscount += $hiddentBaseDiscount;
                        $this->_hiddentDiscount += $hiddentDiscount;
                    }
                }
            }
        }
        if ($this->_giftvoucherHelper->getGeneralConfig('use_for_ship', $address->getQuote()->getStoreId())) {
            if ($this->_helperTax->shippingPriceIncludesTax()) {
                $shipDiscount = $total->getShippingInclTax() - $total->getMagestoreBaseDiscountForShipping()
                    - $total->getShippingDiscountAmount();
            } else {
                $shipDiscount = $total->getBaseShippingAmount() - $total->getMagestoreBaseDiscountForShipping()
                    - $total->getBaseShippingDiscountAmount();
            }
            $total->setMagestoreBaseDiscountForShipping($total->getMagestoreBaseDiscountForShipping()
                + $shipDiscount * $rateDiscount);
            $total->setBaseGiftcreditDiscountForShipping($total->getBaseGiftcreditDiscountForShipping()
                + $shipDiscount * $rateDiscount);
            $total->setGiftcreditDiscountForShipping($total->getGiftcreditDiscountForShipping()
                + $this->priceCurrency->convert($shipDiscount * $rateDiscount));
            
            if (!$this->_checkoutSession->setBaseGiftcreditDiscountForShipping()) {
                $this->_checkoutSession
                    ->setBaseGiftcreditDiscountForShipping($total->getBaseGiftcreditDiscountForShipping());
                $this->_checkoutSession
                    ->setGiftcreditDiscountForShipping($total->getGiftcreditDiscountForShipping());
            }
        
            $baseTaxableAmount = $total->getBaseShippingTaxable();
            $taxableAmount = $total->getShippingTaxable();

            $total->setBaseShippingTaxable($total->getBaseShippingTaxable()
                - $total->getBaseGiftcreditDiscountForShipping());
            $total->setShippingTaxable($total->getShippingTaxable() - $total->getGiftcreditDiscountForShipping());

            if ($this->_helperTax->shippingPriceIncludesTax() && $shipDiscount) {
                $rate = $this->getShipingTaxRate($address, $store);
                $hiddenBaseTaxBeforeDiscount = $taxCalculation->calcTaxAmount($baseTaxableAmount, $rate, true, false);
                $hiddenTaxBeforeDiscount = $taxCalculation->calcTaxAmount($taxableAmount, $rate, true, false);

                $hiddenBaseTaxAfterDiscount = $taxCalculation
                    ->calcTaxAmount($total->getBaseShippingTaxable(), $rate, true, false);
                $hiddenTaxAfterDiscount = $taxCalculation
                    ->calcTaxAmount($total->getShippingTaxable(), $rate, true, false);

                $this->_hiddentBaseDiscount += $taxCalculation->round($hiddenBaseTaxBeforeDiscount)
                    - $taxCalculation->round($hiddenBaseTaxAfterDiscount);
                $this->_hiddentDiscount += $taxCalculation->round($hiddenTaxBeforeDiscount)
                    - $taxCalculation->round($hiddenTaxAfterDiscount);
            }
        }
        return $this;
    }

    /**
     * Get the tax rate of shipping
     *
     * @param Address $address
     * @param Store $store
     * @return float
     */
    public function getShipingTaxRate($address, $store)
    {
        $taxCalculation = $this->_objectManager->create(Calculation::class);
        $taxConfig = $this->_objectManager->create(Config::class);
        $request = $taxCalculation->getRateRequest(
            $address,
            $address->getQuote()->getBillingAddress(),
            $address->getQuote()->getCustomerTaxClassId(),
            $store
        );
        $request->setProductClassId($taxConfig->getShippingTaxClass($store));
        $rate = $taxCalculation->getRate($request);
        return $rate;
    }
}
