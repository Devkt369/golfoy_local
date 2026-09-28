<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Giftvoucher\Service\Redeem;

use Exception;
use Magestore\Giftvoucher\Model\Session;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magestore\Giftvoucher\Api\Data\GiftcodeDiscountInterface;
use Magestore\Giftvoucher\Api\Data\GiftcodeInterface;
use Magestore\Giftvoucher\Api\Data\Redeem\ResponseInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magestore\Giftvoucher\Api\GiftvoucherRepositoryInterface;
use Magestore\Giftvoucher\Api\Redeem\CheckoutServiceInterface;
use Magestore\Giftvoucher\Model\Actions;
use Magestore\Giftvoucher\Model\CustomerVoucherFactory;
use Magestore\Giftvoucher\Model\Giftvoucher;
use Magestore\Giftvoucher\Model\GiftvoucherFactory;
use Magestore\Giftvoucher\Model\HistoryFactory;
use Magestore\Giftvoucher\Model\ResourceModel\CustomerVoucher\CollectionFactory;
use Magestore\Giftvoucher\Model\Status;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Meetanshi\Sms\Helper\Data as SMSHelper;
use Magento\Customer\Model\CustomerFactory;
/**
 * Class CheckoutService
 *
 * Redeem checkout service
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CheckoutService implements CheckoutServiceInterface
{
    private $timezone;
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;
    protected $resourceConnection;
    /**
     * @var CurrencyFactory
     */
    protected $currencyFactory;

    /**
     * @var CustomerFactory
     */
    protected $customerModel;

    /**
     * @var SMSHelper
     */
    protected $smsHelper;

    /**
     * @var CollectionFactory
     */
    protected $voucherCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var GiftvoucherFactory
     */
    protected $giftvoucherFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magestore\Giftvoucher\Helper\Data
     */
    protected $helper;

    /**
     * @var Data
     */
    protected $directoryHelper;

    /**
     * @var HistoryFactory
     */
    protected $historyFactory;

    /**
     * @var CustomerVoucherFactory
     */
    protected $customerVoucherFactory;

    /**
     * @var CollectionFactory
     */
    protected $giftVoucherCollectionFactory;

    /**
     * @var GiftvoucherRepositoryInterface
     */
    protected $giftVoucherRepository;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var GiftvoucherRepositoryInterface
     */
    protected $giftvoucherRepository;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * CheckoutService constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param CurrencyFactory $currencyFactory
     * @param CollectionFactory $voucherCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param PriceCurrencyInterface $priceCurrency
     * @param GiftvoucherFactory $giftvoucherFactory
     * @param \Magestore\Giftvoucher\Helper\Data $helper
     * @param Data $directoryHelper
     * @param HistoryFactory $historyFactory
     * @param CustomerVoucherFactory $customerVoucherFactory
     * @param CollectionFactory $giftVoucherCollectionFactory
     * @param GiftvoucherRepositoryInterface $giftvoucherRepository
     * @param ObjectManagerInterface $objectmanager
     * @param SerializerInterface $serializer
     * @param GetCartForUser $getCartForUser
     * @param SMSHelper $smsHelper
     * @param Customer $customerModel
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        CurrencyFactory $currencyFactory,
        CollectionFactory $voucherCollectionFactory,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency,
        GiftvoucherFactory $giftvoucherFactory,
        \Magestore\Giftvoucher\Helper\Data $helper,
        Data $directoryHelper,
        HistoryFactory $historyFactory,
        CustomerVoucherFactory $customerVoucherFactory,
        CollectionFactory $giftVoucherCollectionFactory,
        GiftvoucherRepositoryInterface $giftvoucherRepository,
        ObjectManagerInterface $objectmanager,
        SerializerInterface $serializer,
        ResourceConnection $resourceConnection,
        TimezoneInterface $timezone,
        SMSHelper $smsHelper,
        CustomerFactory $customerModel,
        GetCartForUser $getCartForUser
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->currencyFactory = $currencyFactory;
        $this->voucherCollectionFactory = $voucherCollectionFactory;
        $this->storeManager = $storeManager;
        $this->giftvoucherFactory = $giftvoucherFactory;
        $this->priceCurrency = $priceCurrency;
        $this->helper = $helper;
        $this->directoryHelper = $directoryHelper;
        $this->historyFactory = $historyFactory;
        $this->customerVoucherFactory = $customerVoucherFactory;
        $this->giftVoucherCollectionFactory = $giftVoucherCollectionFactory;
        $this->giftvoucherRepository = $giftvoucherRepository;
        $this->_objectManager = $objectmanager;
        $this->serializer = $serializer;
        $this->getCartForUser = $getCartForUser;
        $this->resourceConnection = $resourceConnection;
        $this->smsHelper = $smsHelper;
        $this->customerModel = $customerModel;
        $this->timezone = $timezone;
    }

    /**
     * Get Existed Gift Card
     *
     * @param int $cartId
     * @return GiftcodeDiscountInterface[]
     * @throws LocalizedException
     */
    public function getUsingGiftCodes($cartId)
    {
        $quote = $this->getQuote($cartId);
        $giftCards = [];
        if ($codes = $quote->getGiftVoucherGiftCodes()) {
            $codesArray = explode(',', (string)$codes);
            $codesDiscountArray = explode(',', (string)$quote->getGiftVoucherGiftCodesDiscount());
            foreach ($codesArray as $key => $code) {
                $usedBalance = (isset($codesDiscountArray[$key])) ? $codesDiscountArray[$key] : 0;
              
                $expiryDate = $this->getGiftCardExpiryDate($code);
                $actualBalance = $expiryDate['balance'];
                $remainedBalance = max(0, $actualBalance - $usedBalance);

                $giftCards[] = [
                    GiftcodeDiscountInterface::CODE => $code,
                    'EXPIRY' => $expiryDate['expiry_date'],
                    'DAYS' => $expiryDate['days_left'],
                    'REMAINED' => $remainedBalance,
                    GiftcodeDiscountInterface::DISCOUNT => $usedBalance
                ];
            }
        }
        return $giftCards;
    }


    private function getGiftCardExpiryDate($giftCardCode=null)

    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('giftvoucher');

        $select = $connection->select()
            ->from($tableName, ['expired_at','balance'])
            ->where('gift_code = ?', $giftCardCode)->limit(1);

        $giftCardData = $connection->fetchRow($select);
        if ($giftCardData) {
            $expiryDate = $giftCardData['expired_at'];
            $balance = $giftCardData['balance'];
    
            $currentDate = $this->timezone->date()->format('Y-m-d');
            $daysLeft = $this->calculateDaysLeft($expiryDate, $currentDate);
    
            $giftCardDetails = [
                'expiry_date' => $expiryDate,
                'balance' => $balance,
                'days_left' => $daysLeft
            ];
    
            return $giftCardDetails;
        } else {
            return null; // Or handle the case where the gift card is not found
        }
    }

    private function calculateDaysLeft($expiryDate, $currentDate)
    {

        if(!empty($expiryDate) || $expiryDate !=null){
            $expiryDateTime = strtotime($expiryDate);
            $currentDateTime = strtotime($currentDate);
    
            // Calculate difference between expiry date and current date
            $difference = $expiryDateTime - $currentDateTime;
    
            // Convert difference to number of days
            $daysLeft = floor($difference / (60 * 60 * 24));
    
            return $daysLeft;
        }
      
    }
    /**
     * Get Existed Gift Card
     *
     * @param int $cartId
     * @return GiftcodeInterface[]
     * @throws LocalizedException
     */
    public function getExistedGiftCodes($cartId)
    {
        $quote = $this->getQuote($cartId);
        $store = $this->storeManager->getStore($this->helper->getStoreId($quote));
        $customerId = $quote->getCustomerId();
        $customerEmail = $quote->getCustomerEmail();

        $collection = $this->voucherCollectionFactory->create()
            ->addFieldToFilter('main_table.customer_id', $customerId);
        $collection->getExistedGiftcodes($customerId, $customerEmail);

        $giftCards = [];
        $addedCodes = [];
        if ($codes = $quote->getGiftVoucherGiftCodes()) {
            $addedCodes = explode(',', (string)$codes);
        }
        $ruleModel = $this->giftvoucherFactory->create();

        foreach ($collection as $item) {
            if (in_array($item->getGiftCode(), $addedCodes)) {
                continue;
            }
            if ($item->getConditionsSerialized()) {
                $conditionsArr = $this->serializer->unserialize($item->getConditionsSerialized());

                if (!empty($conditionsArr) && is_array($conditionsArr)) {
                    $ruleModel->getConditions()->setConditions([])->loadArray($conditionsArr);
                    if ($quote->isVirtual()) {
                        $address = $quote->getBillingAddress();
                    } else {
                        $address = $quote->getShippingAddress();
                    }
                    if (!$ruleModel->validate($address)) {
                        continue;
                    }
                }
            }
            $giftCards[] = [
                GiftcodeInterface::GIFT_CODE => $item->getGiftCode(),
                GiftcodeInterface::BALANCE => $this->_getGiftCardBalance($item, $store)
            ];
        }
        return $giftCards;
    }

    /**
     * Get Gift Card Balance
     *
     * @param GiftcodeInterface $item
     * @param StoreInterface $store
     * @return mixed
     */
    public function _getGiftCardBalance($item, $store)
    {
        $cardCurrency = $this->currencyFactory->create()->load($item->getCurrency());
        $baseCurrency = $store->getBaseCurrency();
        $currentCurrency = $store->getCurrentCurrency();
        if ($cardCurrency->getCode() == $currentCurrency->getCode()) {
            return $this->formatPrice($store, $item->getBalance());
        }
        if ($cardCurrency->getCode() == $baseCurrency->getCode()) {
            return $this->convertPrice($store, $item->getBalance(), true);
        }
        if ($baseCurrency->convert(100, $cardCurrency)) {
            $amount = $item->getBalance() * $baseCurrency->convert(100, $currentCurrency)
                / $baseCurrency->convert(100, $cardCurrency);
            return $this->formatPrice($store, $amount);
        }
        return $cardCurrency->format($store, $item->getBalance(), [], true);
    }

    /**
     * Retrieve formated price
     *
     * @param StoreInterface $store
     * @param float $value
     * @return string
     */
    public function formatPrice($store, $value)
    {
        return $this->priceCurrency->format(
            $value,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $store
        );
    }

    /**
     * Convert price
     *
     * @param StoreInterface $store
     * @param float $value
     * @param bool $format
     * @return float
     */
    public function convertPrice($store, $value, $format = true)
    {
        return $format
            ? $this->priceCurrency->convertAndFormat(
                $value,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $store
            )
            : $this->priceCurrency->convert($value, $store);
    }

    /**
     * @inheritDoc
     */
    public function getQuote($cartId)
    {
        $quote = $this->quoteRepository->get($cartId);
        return $quote;
    }

    /**
     * Clear Data
     *
     * @param int $cartId
     * @return $this
     */
    public function clearData($cartId)
    {
        $quote = $this->getQuote($cartId);
        $quote->setGiftVoucherGiftCodes('');
        $quote->setGiftVoucherGiftCodesDiscount('');
        $quote->setGiftVoucherGiftCodesMaxDiscount('');
        $quote->setGiftvoucherBaseHiddenTaxAmount(0);
        $quote->setGiftvoucherHiddenTaxAmount(0);
        $quote->setBaseGiftVoucherDiscount(0);
        $quote->setGiftVoucherDiscount(0);
        $quote->setCodesBaseDiscount('');
        $quote->setCodesDiscount('');
        $this->quoteRepository->save($quote);
        return $this;
    }

    /**
     * Validate Customer
     *
     * @param Giftvoucher $giftvoucher
     * @param int $customerId
     * @return bool
     */
    public function validateCustomer(Giftvoucher $giftvoucher, $customerId)
    {
        if (!($giftvoucher instanceof Giftvoucher)) {
            return false;
        }
        if (!$giftvoucher->getId()) {
            return false;
        }
        $shareCard = (int) $this->helper->getGeneralConfig('share_card');
        if ($shareCard < 1) {
            return true;
        }
        $customersUsed = $giftvoucher->getCustomerIdsUsed();
        if ($shareCard > count($customersUsed) || in_array($customerId, $customersUsed)
        ) {
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function applyCodes($cartId, $addedCodes = [], $existedCode = '', $newCode = '')
    {
        $result = [
            ResponseInterface::ERRORS => [],
            ResponseInterface::SUCCESS => [],
            ResponseInterface::NOTICES => []
        ];
        $quote = $this->getQuote($cartId);
        if ($quote->getCouponCode() && !$this->helper->getGeneralConfig('use_with_coupon')) {
            $this->clearData($cartId);
            $result[ResponseInterface::NOTICES][] = __(
                'A coupon code has been used. You cannot apply gift codes with the coupon to get discount.'
            );
        } else {
            if (count($addedCodes)) {
                if ($quote->getGiftVoucherGiftCodesMaxDiscount()) {
                    $giftMaxUseAmount = $this->serializer->unserialize($quote->getGiftVoucherGiftCodesMaxDiscount());
                }
                if (!isset($giftMaxUseAmount) || !is_array($giftMaxUseAmount)) {
                    $giftMaxUseAmount = [];
                }
                foreach ($addedCodes as $addedCode) {
                    // Assuming $addedCode is an object, use object syntax
                    $giftMaxUseAmount[$addedCode->{GiftcodeDiscountInterface::CODE}] =
                        $addedCode->{GiftcodeDiscountInterface::DISCOUNT};
                }
                $quote->setGiftVoucherGiftCodesMaxDiscount($this->serializer->serialize($giftMaxUseAmount));
                $quote->collectTotals();
                $this->quoteRepository->save($quote);
            }
            $giftCodes = [];
            if ($existedCode) {
                $giftCodes[] = $existedCode;
            }
            if ($newCode) {
                $giftCodes[] = $newCode;
            }
            if (count($giftCodes)) {
                /** @var State $state */
                $state = $this->_objectManager->get(State::class);
                if (Area::AREA_FRONTEND == $state->getAreaCode()) {
                    $max = $this->helper->getGeneralConfig('maximum');
                    if (!$this->helper->isAvailableToAddCode()) {
                        $result[ResponseInterface::ERRORS][] = __(
                            'The maximum number of times to enter gift codes is %1!',
                            $max
                        );
                        return $result;
                    }
                }
                foreach ($giftCodes as $code) {
                    $giftVoucher = $this->giftvoucherFactory->create()->loadByCode($code);
                    if (!$giftVoucher->getGiftCode()) {
                        // Max times to enter gift code incorrectly
                        if (Area::AREA_FRONTEND == $state->getAreaCode()) {
                            /* @var Session $session*/
                            $session = $this->_objectManager->get(Session::class);
                            $codes = $session->getCodes();
                            $codes[] = $code;
                            $codes = array_unique($codes);
                            $session->setCodes($codes);
                        }
                        if (Area::AREA_FRONTEND == $state->getAreaCode() && $max) {
                            $result[ResponseInterface::ERRORS][] = __('Gift Code "%1" does not exist.', $code) . ' '
                                . __(
                                    'You have %1 time(s) remaining to re-enter Gift Card code.',
                                    $max - count($codes)
                                );
                        } else {
                            $result[ResponseInterface::ERRORS][] = __('Gift Code "%1" does not exist.', $code);
                        }
                        continue;
                    }
                    if (!$this->validateCustomer($giftVoucher, $quote->getCustomerId())) {
                        $result[ResponseInterface::ERRORS][] = __('This gift code limits the number of users');
                        continue;
                    }
                    if ($giftVoucher->getBaseBalance() > 0
                        && $giftVoucher->getStatus() == Status::STATUS_ACTIVE
                        && $giftVoucher->validate($quote->setQuote($quote))
                        && $giftVoucher->isValidWebsite($this->helper->getStoreId($quote))
                    ) {
                        $this->addVoucherToQuote($cartId, $giftVoucher);
                        if ($giftVoucher->getCustomerId() == $quote->getCustomerId()
                            && $giftVoucher->getRecipientName() && $giftVoucher->getRecipientEmail()
                            && $giftVoucher->getCustomerId()
                        ) {
                            $result[ResponseInterface::NOTICES][] = __(
                                'Gift Code "%1" has been sent to the customer\'s friend.',
                                $code
                            );
                        }
                        $result[ResponseInterface::SUCCESS][] = __(
                            'Gift Code "%1" has been applied successfully.',
                            $code
                        );
                    } elseif ($giftVoucher->getStatus() == Status::STATUS_ACTIVE
                        && $giftVoucher->isValidWebsite($this->helper->getStoreId($quote))
                    ) {
                        $this->addVoucherToQuote($cartId, $giftVoucher);
                        $result[ResponseInterface::NOTICES][] = __(
                            'You can’t use this gift code since its conditions haven’t been met.'
                        );
                    } else {
                        $result[ResponseInterface::ERRORS][] = __(
                            'Gift Code "%1" is no longer available to use.',
                            $code
                        );
                    }
                }
            } else {
                $result[ResponseInterface::SUCCESS][] = __('Gift Code has been updated successfully.');
            }
        }
        return $result;
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function removeCode($cartId, $giftCode = '')
    {
        $result = [
            ResponseInterface::ERRORS => [],
            ResponseInterface::SUCCESS => [],
            ResponseInterface::NOTICES => []
        ];
        $quote = $this->getQuote($cartId);
        $codes = $quote->getGiftVoucherGiftCodes();

        $success = false;
        if ($giftCode && $codes) {
            $codesArray = explode(',', (string)$codes);
            foreach ($codesArray as $key => $value) {
                if ($value == $giftCode) {
                    unset($codesArray[$key]);
                    $success = true;
                    if ($quote->getGiftVoucherGiftCodesMaxDiscount()) {
                        $giftMaxUseAmount = $this->serializer->unserialize(
                            $quote->getGiftVoucherGiftCodesMaxDiscount()
                        );
                    }
                    if (isset($giftMaxUseAmount)
                        && is_array($giftMaxUseAmount)
                        && array_key_exists($giftCode, $giftMaxUseAmount)
                    ) {
                        unset($giftMaxUseAmount[$giftCode]);
                        $quote->setGiftVoucherGiftCodesMaxDiscount($this->serializer->serialize($giftMaxUseAmount));
                    }
                    break;
                }
            }
        }
        if ($success) {
            $codes = implode(',', $codesArray);
            $quote->setGiftVoucherGiftCodes($codes);
            $this->quoteRepository->save($quote);
            if (empty($codesArray)) {
                $this->clearData($cartId);
            }
            $result[ResponseInterface::SUCCESS][] = __('Gift card "%1" has been removed successfully.', $giftCode);
        } else {
            $result[ResponseInterface::ERRORS][] = __('Gift card "%1" not found!', $giftCode);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function removeCodes($cartId)
    {
        $result = [
            ResponseInterface::ERRORS => [],
            ResponseInterface::SUCCESS => [],
            ResponseInterface::NOTICES => []
        ];
        $this->clearData($cartId);
        $result[ResponseInterface::SUCCESS][] = __('Your Gift Code has been removed successfully.');
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function addVoucherToQuote($cartId, Giftvoucher $giftVoucher)
    {
        $quote = $this->getQuote($cartId);
        if ($codes = $quote->getGiftVoucherGiftCodes()) {
            $codesArray = explode(',', (string)$codes);
            $codesArray[] = $giftVoucher->getGiftCode();
            $codes = implode(',', array_unique($codesArray));
        } else {
            $codes = $giftVoucher->getGiftCode();
        }
        $quote->setGiftVoucherGiftCodes($codes);
        $this->quoteRepository->save($quote);
        return $this;
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processOrderPlaceAfter($order)
    {
        if (!($order->getGiftVoucherDiscount())) {
            return $this;
        }
        $codes = $order->getGiftVoucherGiftCodes();
        if ($codes) {
            $store = $order->getStore();
            $order->setGiftvoucherForOrderCodes($codes)
                ->setGiftvoucherForOrderAmount($order->getGiftVoucherDiscount());
            $codesArray = explode(',', (string)$codes);
            $codesBaseDiscount = explode(',', (string)$order->getCodesBaseDiscount());
            $codesDiscount = explode(',', (string)$order->getCodesDiscount());
            $baseDiscount = array_combine($codesArray, $codesBaseDiscount);
            $discount = array_combine($codesArray, $codesDiscount);
            foreach ($codesArray as $code) {
                if (!$baseDiscount[$code] || $this->priceCurrency->round($baseDiscount[$code]) == 0) {
                    continue;
                }
                $giftVoucher = $this->giftvoucherFactory->create()->loadByCode($code);

                if ($order->getOrderCurrencyCode() != $giftVoucher->getData('currency')) {
                    /* Convert if the currency gift code is different from order */
                    $baseCurrencyCode = $order->getBaseCurrencyCode();
                    $baseCurrency = $this->currencyFactory->create()->load($baseCurrencyCode);
                    $currentCurrency = $this->currencyFactory->create()->load($giftVoucher->getData('currency'));

                    $codeDiscount = $this->directoryHelper
                        ->currencyConvert(
                            $baseDiscount[$code],
                            $baseCurrencyCode,
                            $giftVoucher->getData('currency')
                        );
                    $codeCurrentDiscount = $this->directoryHelper
                        ->currencyConvert($baseDiscount[$code], $baseCurrencyCode, $store->getCurrentCurrencyCode());
                    $balance = $giftVoucher->getBalance() - $codeDiscount;
                    if ($balance > 0) {
                        $baseBalance = $balance * $balance / $baseCurrency->convert($balance, $currentCurrency);
                    } else {
                        $baseBalance = 0;
                    }
                    $currentBalance = $this->directoryHelper
                        ->currencyConvert($baseBalance, $baseCurrencyCode, $store->getCurrentCurrencyCode());
                } else {
                    /* No convert if gift code has the same currency with order */
                    $balance = $currentBalance = $giftVoucher->getBalance() - $discount[$code];
                    $codeCurrentDiscount = $discount[$code];
                }

                $giftVoucher->setData('balance', $balance);
                $this->giftvoucherRepository->save($giftVoucher);
                if ($order->getData('customer_id') == null) {
                    $customerName = __('Used by Guest');
                } else {
                    $customerName = __(
                        'Used by %1 %1',
                        $order->getData('customer_firstname'),
                        $order->getData('customer_lastname')
                    );
                }
                $this->historyFactory->create()->setData(
                    [
                        'order_increment_id' => $order->getIncrementId(),
                        'giftvoucher_id' => $giftVoucher->getId(),
                        'created_at' => date("Y-m-d H:i:s"),
                        'action' => Actions::ACTIONS_SPEND_ORDER,
                        'amount' => $codeCurrentDiscount,
                        'balance' => $currentBalance,
                        'currency' => $store->getCurrentCurrencyCode(),
                        'status' => $giftVoucher->getStatus(),
                        'order_amount' => $discount[$code],
                        'comments' => __('Spent on order %1', $order->getIncrementId()),
                        'extra_content' => $customerName,
                        'customer_id' => $order->getData('customer_id'),
                        'customer_email' => $order->getData('customer_email')
                    ]
                )->save();

                $account = $this->customerModel->create()->load($order->getData('customer_id'));
                $phoneNumber = $account->getData("phone_number");
                if($phoneNumber){
                    $this->smsHelper->sendGiftCardUpdateSms($phoneNumber, $discount[$code], $giftVoucher->getData("gift_code"), date("Y-m-d H:i:s"), $currentBalance );
                }
                // add gift code to customer list
                if ($order->getCustomerId() && ($balance > 0)) {
                    $collection = $this->giftVoucherCollectionFactory->create()
                        ->addFieldToFilter('customer_id', $order->getCustomerId())
                        ->addFieldToFilter('voucher_id', $giftVoucher->getId());
                    if (!$collection->getSize()) {
                        try {
                            $timeSite = date(
                                "Y-m-d",
                                $this->_objectManager
                                    ->get(DateTime::class)->timestamp(time())
                            );
                            $this->customerVoucherFactory->create()
                                ->setCustomerId($order->getCustomerId())
                                ->setVoucherId($giftVoucher->getId())
                                ->setAddedDate($timeSite)
                                ->save();
                        } catch (Exception $e) {
                            continue;
                        }
                    }
                }
            }
        }

        // Create invoice for Order payed by Giftvoucher
        if ($this->priceCurrency->round($order->getGrandTotal()) == 0
            && $order->getPayment()->getMethod() == 'free'
            && $order->canInvoice()
        ) {
            try {
                // $invoice = $order->prepareInvoice()->register();
                // $order->addRelatedObject($invoice);
                // if ($order->getState() == 'new') {
                //     $order->setState('processing');
                // }

                // if ($order->getStatus() == 'pending') {
                //     $order->setStatus('processing');
                // }
            } catch (Exception $e) {
                return $this;
            }
        }
        return $this;
    }

    /**
     * Function Handle Output Graphql GiftCode
     *
     * @param array $result
     * @param string $maskedCartId
     * @param int|null $currentUserId
     * @param int $storeId
     *
     * @return array[]
     */
    public function handleOutputGiftCode($result, $maskedCartId, $currentUserId, $storeId)
    {
        if (!empty($result[ResponseInterface::ERRORS])) {
            throw new GraphQlInputException(__($result[ResponseInterface::ERRORS][0]->render()));
        }

        if (!empty($result[ResponseInterface::NOTICES])) {
            return [
                'cart' => [
                    'model' => $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId)
                ],
                'notice_messages' => $result[ResponseInterface::NOTICES]
            ];
        }

        if (!empty($result[ResponseInterface::SUCCESS])) {
            return [
                'cart' => [
                    'model' => $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId)
                ]
            ];
        }
        return [];
    }
}
