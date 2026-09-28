<?php

namespace Magestore\Webpos\Model\Checkout;

use Exception;
use Magento\Catalog\Model\Product\Type;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CouponManagementInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote\Item\OptionFactory;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\Validator;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magestore\Webpos\Api\Data\Checkout\QuoteInterface;
use Magestore\Webpos\Helper\Data;

/**
 * Model CouponManagement
 * @SuppressWarnings(PHPMD)
 */
class CouponManagement implements \Magestore\Webpos\Api\Checkout\CouponManagementInterface
{
    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRespositoryInterface;

    /**
     * @var \Magento\Quote\Api\CouponManagementInterface
     */
    protected $couponManagement;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    protected $addressFactory;
    /**
     * @var \Magento\Customer\Model\Address
     */
    protected $customerAddress;
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $customer;
    /**
     * @var \Magento\Quote\Model\Quote\ItemFactory
     */
    protected $quoteItemFactory;
    /**
     * @var \Magento\Store\Model\StoreFactory
     */
    protected $storeFactory;
    /**
     * @var \Magento\Quote\Api\Data\PaymentInterface
     */
    protected $orderPaymentInterface;
    /**
     * @var \Magento\SalesRule\Model\Validator
     */
    protected $validator;
    /**
     * @var \Magestore\Webpos\Helper\Data
     */
    protected $helper;

    /**
     * Serializer interface instance.
     *
     * @var \Magento\Framework\Serialize\Serializer\Json
     * @since 102.0.0
     */
    protected $serializer;

    /**
     * @var OptionFactory
     */
    protected $itemOptionFactory;

    /**
     * CouponManagement constructor.
     * @param \Magento\Quote\Api\CouponManagementInterface $couponManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Quote\Model\Quote\AddressFactory $addressFactory
     * @param \Magento\Customer\Model\Address $customerAddress
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
     * @param \Magento\Store\Model\StoreFactory $storeFactory
     * @param \Magento\Quote\Api\Data\PaymentInterface $orderPaymentInterface
     * @param \Magento\SalesRule\Model\Validator $validator
     * @param \Magestore\Webpos\Helper\Data $helper
     * @param OptionFactory $itemOptionFactory
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     */
    public function __construct(
        CouponManagementInterface $couponManagement,
        CartRepositoryInterface $cartRepository,
        CouponFactory $couponFactory,
        CollectionFactory $ruleCollectionFactory,
        QuoteFactory $quoteFactory,
        StoreManagerInterface $storeManager,
        AddressFactory $addressFactory,
        \Magento\Customer\Model\Address $customerAddress,
        Customer $customer,
        ItemFactory $quoteItemFactory,
        StoreFactory $storeFactory,
        PaymentInterface $orderPaymentInterface,
        Validator $validator,
        Data $helper,
        OptionFactory $itemOptionFactory,
        Json $serializer = null
    ) {
        $this->couponManagement = $couponManagement;
        $this->cartRespositoryInterface = $cartRepository;
        $this->couponFactory = $couponFactory;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->quoteFactory = $quoteFactory;
        $this->storeManager = $storeManager;
        $this->addressFactory = $addressFactory;
        $this->customerAddress = $customerAddress;
        $this->customer = $customer;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->storeFactory = $storeFactory;
        $this->orderPaymentInterface = $orderPaymentInterface;
        $this->validator = $validator;
        $this->helper = $helper;
        $this->itemOptionFactory = $itemOptionFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()
            ->get(Json::class);
    }

    /**
     * @inheritdoc
     */
    public function checkCoupon(QuoteInterface $quote, $coupon_code = null)
    {
        if ($coupon_code) {
            $couponModel = $this->couponFactory->create()->loadByCode($coupon_code);
            if (!$couponModel->getId()) {
                throw new LocalizedException(__('Invalid Coupon Code'));
            }
        }
        // create quote
        $newQuote = $this->createQuote($quote);
        $newQuote->getShippingAddress()->setCollectShippingRates(true);
        $newQuote->setCouponCode($coupon_code);
        $newQuote->save();

        // set applied rule for quote
        // $validator = $this->validator->init(
        // $this->storeManager->getStore()->getWebsiteId(), $newQuote->getCustomerGroupId(), $coupon_code);
        // $validator->processShippingAmount($newQuote->getShippingAddress());

        // collect total
        $newQuote->collectTotals();
        // save list item rules
        $listItemRules = [];

        foreach ($newQuote->getAllItems() as $item) {
            $listItemRules[$item->getTmpItemId() . ''] = explode(',', $item->getAppliedRuleIds() ?? "");
        }

        $appliedRuleIds = explode(',', $newQuote->getAppliedRuleIds());

        $this->deleteQuote($newQuote);

        $rule = $this->ruleCollectionFactory->create()
            ->addFieldToFilter('rule_id', ['in' => $appliedRuleIds]);

        return $this->verifyOutputData($rule->getItems(), $listItemRules);
        // end - create quote
    }

    /**
     * Function verifyOutputData
     *
     * @param \Magestore\Webpos\Api\Data\Checkout\RuleInterface[] $items
     * @param array $listItemRules
     * @return \Magestore\Webpos\Api\Data\Checkout\RuleInterface[]
     */
    public function verifyOutputData($items, $listItemRules)
    {
        $data = [];
        foreach ($items as $item) {
            $validItemIds = [];
            $ruleId = $item->getRuleId();
            foreach ($listItemRules as $tmpItemId => $listRules) {
                if (in_array($ruleId, $listRules)) {
                    $validItemIds[] = $tmpItemId;
                }
            }
            $item->setValidItemIds($validItemIds);

            $data[] = $item;
        }
        return $data;
    }

    /**
     * Function createQuote
     *
     * @param \Magestore\Webpos\Api\Data\Checkout\QuoteInterface $quote
     * @return \Magento\Quote\Model\Quote
     * @throws CouldNotSaveException
     */
    public function createQuote(QuoteInterface $quote)
    {
        $addresses = $quote->getAddresses();
        $items = $quote->getItems();

        // $quote->setEntityId('');
        // $quote->setQuoteAddressId('');
        // $quote->setBillingAddressId('');
        // $quote->setShippingAddressId('');
        // $quote->setQuoteId('');

        /** @var \Magento\Quote\Model\Quote $newQuote */
        $newQuote = $this->quoteFactory->create();

        try {
            $data = $quote->getData();
            $this->unsetQuoteData($data);

            $newQuote->setData($data);

            // add address to order
            // $this->addAddressToQuote($newQuote, $addresses);
            // save order for automatic generate order status
            $newQuote->save();

            // add item to order
            $this->addItemToQuote($newQuote, $items);

            $storeId = $this->checkStoreId($newQuote->getStoreId());
            $newQuote->setStoreId($storeId);

            $newQuote->save();
            return $newQuote;
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
    }

    /**
     * Function addAddressToQuote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magestore\Webpos\Api\Data\Checkout\Quote\AddressInterface[] $addresses
     */
    public function addAddressToQuote(&$quote, $addresses)
    {
        foreach ($addresses as $address) {
            /** @var \Magento\Quote\Model\Quote\Address $add */
            $add = $this->addressFactory->create();
            $data = $address->getData();

            unset($data['entity_id']);
            $customerAddress = $this->customerAddress->load($data['customer_address_id']);
            if (!$customerAddress->getId()) {
                unset($data['customer_address_id']);
            }
            $customer = $this->customer->load($data['customer_id']);
            if (!$customer->getId()) {
                unset($data['customer_id']);
            }

            if ($address->getAddressType() == Address::TYPE_BILLING) {
                $add->setData($data);
                $quote->setBillingAddress($add);
            } else {
                $add->setData($data);
                $add->setAddressType(Address::TYPE_SHIPPING);
                $quote->setShippingAddress($add);
            }
        }
    }

    /**
     * Function addItemToQuote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magestore\Webpos\Api\Data\Checkout\Quote\ItemInterface[] $items
     * @throws LocalizedException
     * @throws \Exception
     */
    public function addItemToQuote(&$quote, $items)
    {
        $dataItems = [];
        foreach ($items as $item) {
            $id = $item->getItemId();

            // check store id
            $storeId = $this->checkStoreId($item->getStoreId());
            $item->setStoreId($storeId);

            $data = $item->getData();
            $data['tmp_item_id'] = $data['item_id'];
            if (isset($data['item_id'])) {
                unset($data['item_id']);
            }
            if (isset($data['quote_id'])) {
                unset($data['quote_id']);
            }

            $dataItems[$id] = $data;
        }
        $tmpData = $dataItems;
        $removedData = [];
        foreach ($tmpData as $key => $datum) {
            if ($datum['parent_item_id'] && in_array($datum['parent_item_id'], array_keys($tmpData))) {
                $dataItems[$key]['parent_item'] = $dataItems[$datum['parent_item_id']];
                $removedData[] = $datum['parent_item_id'];
            }
        }
        // remove data parent item
        foreach ($removedData as $remove) {
            unset($dataItems[$remove]);
        }

        foreach ($dataItems as $key => $dataItem) {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            $quoteItem = $this->quoteItemFactory->create();

            // check store id
            $storeId = $this->checkStoreId($dataItem['store_id']);
            $dataItem['store_id'] = $storeId;

            // check parent item
            /** @var \Magento\Quote\Model\Quote\Item $parentItem */
            $parentItem = $this->quoteItemFactory->create();
            if (isset($dataItem['parent_item'])) {
                $parentItemData = $dataItem['parent_item'];
                unset($dataItem['parent_item']);

                // check store id
                $storeId = $this->checkStoreId($parentItemData['store_id']);
                $parentItemData['store_id'] = $storeId;
                $this->addOptionsToItem($parentItem, $parentItemData);

                $parentItem->setData($parentItemData);
            }

            if (isset($data['parent_item_id'])) {
                unset($data['parent_item_id']);
            }
            $quoteItem->setData($dataItem);

            if ($parentItem->getData()) {
                $parentItem->setQuote($quote);
                $quote->addItem($parentItem);
                $parentItem->save();
                $quoteItem->setParentItem($parentItem);
            }
            $quoteItem->setQuote($quote);
            $quote->addItem($quoteItem);
            $quoteItem->save();
        }
    }

    /**
     * Function checkStoreId
     *
     * @param int $storeId
     * @return int
     */
    public function checkStoreId($storeId)
    {
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeFactory->create();
        if (!$store->load($storeId)->getId()) {
            return $this->helper->getCurrentStoreView()->getId();
        } else {
            return $storeId;
        }
    }

    /**
     * Function unsetQuoteData
     *
     * @param array $data
     */
    public function unsetQuoteData(&$data)
    {
        unset($data['items']);
        unset($data['addresses']);
        unset($data['entity_id']);
    }

    /**
     * Function deleteQuote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     * @throws LocalizedException
     */
    public function deleteQuote($quote)
    {
        try {
            $this->cartRespositoryInterface->delete($quote);
        } catch (Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
        return true;
    }

    /**
     * Function addOptionsToItem
     *
     * Used for add custom option to bundle product
     *
     * @param Item $parentItem
     * @param array $parentItemData
     */
    public function addOptionsToItem(&$parentItem, $parentItemData)
    {
        if (isset($parentItemData['product_type']) &&
            Type::TYPE_BUNDLE === $parentItemData['product_type']
        ) {
            $optionBundleSelectionIds = $this->itemOptionFactory->create(
                [
                    'data' => [
                        'code' => 'bundle_selection_ids',
                        'value' => $this->serializer->serialize(
                            $parentItemData['custom_options']['bundle_selection_ids']
                        )
                    ]
                ]
            );
            $optionInfoBuyRequest = $this->itemOptionFactory->create(
                [
                    'data' => [
                        'code' => 'info_buyRequest',
                        'value' => $this->serializer->serialize(
                            $parentItemData['product_options']['info_buyRequest']
                        )
                    ]
                ]
            );
            $parentItem->addOption($optionBundleSelectionIds);
            $parentItem->addOption($optionInfoBuyRequest);
        }
    }
}
