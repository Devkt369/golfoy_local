<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Model\Checkout;

use Amasty\Rewards\Api\RuleRepositoryInterface;
use Amasty\Rewards\Model\Config\Source\Actions;
use Amasty\Rewards\Model\Quote\EarningChecker;
use Exception;
use Magento\Catalog\Model\Product\Type;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Quote\Model\Quote\Item\OptionFactory;
use Magento\Quote\Model\Quote\ItemFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreFactory;
use Magento\Quote\Model\Quote\Address;
use Magestore\Webpos\Api\Data\Checkout\Quote\AddressInterface;
use Magestore\Webpos\Api\Data\Checkout\Quote\ItemInterface;
use Magestore\Webpos\Api\Data\Checkout\QuoteInterface;
use Magestore\Webpos\Helper\Data as WebposHelper;
use Magestore\WebposAmastyRewards\Api\Checkout\EarningManagementInterface;
use Magestore\WebposAmastyRewards\Helper\Data;
use Magestore\WebposAmastyRewards\Api\Data\CheckEarningPointsResultInterface;
use Magestore\WebposAmastyRewards\Api\Data\CheckEarningPointsResultInterfaceFactory;

/**
 * WebposAmastyRewards - EarningManagement
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EarningManagement implements EarningManagementInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $cartRespositoryInterface;

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var AddressFactory
     */
    protected $addressFactory;
    /**
     * @var \Magento\Customer\Model\Address
     */
    protected $customerAddress;
    /**
     * @var CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var ItemFactory
     */
    protected $quoteItemFactory;
    /**
     * @var StoreFactory
     */
    protected $storeFactory;

    /**
     * @var WebposHelper
     */
    protected $webposHelper;

    /**
     * Serializer interface instance.
     *
     * @var Json
     * @since 102.0.0
     */
    protected $serializer;

    /**
     * @var OptionFactory
     */
    protected $itemOptionFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var RuleRepositoryInterface
     */
    protected $ruleRepository;

    /**
     * @var EarningChecker
     */
    protected $earningChecker;

    /**
     * @var CheckEarningPointsResultInterfaceFactory
     */
    protected $checkEarningPointsResultFactory;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteFactory $quoteFactory
     * @param AddressFactory $addressFactory
     * @param \Magento\Customer\Model\Address $customerAddress
     * @param CustomerFactory $customerFactory
     * @param ItemFactory $quoteItemFactory
     * @param StoreFactory $storeFactory
     * @param WebposHelper $webposHelper
     * @param OptionFactory $itemOptionFactory
     * @param Data $helper
     * @param CheckEarningPointsResultInterfaceFactory $checkEarningPointsResultFactory
     * @param Json|null $serializer
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        QuoteFactory $quoteFactory,
        AddressFactory $addressFactory,
        \Magento\Customer\Model\Address $customerAddress,
        CustomerFactory $customerFactory,
        ItemFactory $quoteItemFactory,
        StoreFactory $storeFactory,
        WebposHelper $webposHelper,
        OptionFactory $itemOptionFactory,
        Data $helper,
        CheckEarningPointsResultInterfaceFactory $checkEarningPointsResultFactory,
        Json $serializer = null
    ) {
        $this->cartRespositoryInterface = $cartRepository;
        $this->quoteFactory = $quoteFactory;
        $this->addressFactory = $addressFactory;
        $this->customerAddress = $customerAddress;
        $this->customerFactory = $customerFactory;
        $this->quoteItemFactory = $quoteItemFactory;
        $this->storeFactory = $storeFactory;
        $this->webposHelper = $webposHelper;
        $this->itemOptionFactory = $itemOptionFactory;
        $this->helper = $helper;
        $this->checkEarningPointsResultFactory = $checkEarningPointsResultFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
        if ($this->helper->isEnableAmastyRewards()) {
            $this->ruleRepository =
                ObjectManager::getInstance()->get(RuleRepositoryInterface::class); // phpstan:ignore
            $this->earningChecker =
                ObjectManager::getInstance()->get(EarningChecker::class); // phpstan:ignore
        }
    }

    /**
     * @inheritdoc
     */
    public function checkEarningPoint(QuoteInterface $quote)
    {
        /** @var CheckEarningPointsResultInterface $result */
        $result = $this->checkEarningPointsResultFactory->create();
        $result->setQuoteId(null);
        $result->setValidRules([]);
        if (!$quote->getCustomerId()) {
            return $result;
        }
        /** @var Customer $customer */
        $customer = $this->customerFactory->create()->load($quote->getCustomerId());
        if (!$customer->getId() || $customer->getAmrewardsForbidEarning()) {
            return $result;
        }
        // create quote
        $newQuote = $this->createQuote($quote);
        $newQuote->getShippingAddress()->setCollectShippingRates(true);
        $newQuote->save();

        // collect total
        $newQuote->collectTotals();
        $newQuote->save();

        $validRules = $this->getValidEarningRules($newQuote);

        $result->setQuoteId((int)$newQuote->getId());
        $result->setValidRules($validRules);
        return $result;
    }

    /**
     * Function createQuote
     *
     * @param QuoteInterface $quote
     * @return Quote
     * @throws CouldNotSaveException
     */
    public function createQuote(QuoteInterface $quote)
    {
        $addresses = $quote->getAddresses();
        $items = $quote->getItems();

        $quote->setEntityId('');
        $quote->setQuoteAddressId('');
        $quote->setBillingAddressId('');
        $quote->setShippingAddressId('');
        $quote->setQuoteId('');

        /** @var Quote $newQuote */
        $newQuote = $this->quoteFactory->create();

        try {
            $data = $quote->getData();
            $this->unsetQuoteData($data);

            $newQuote->setData($data);
            $newQuote->setIsActive(false);

            // add address to order
            $this->addAddressToQuote($newQuote, $addresses);
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
     * @param Quote $quote
     * @param AddressInterface[] $addresses
     */
    public function addAddressToQuote(&$quote, $addresses)
    {
        foreach ($addresses as $address) {
            /** @var Address $add */
            $add = $this->addressFactory->create();
            $data = $address->getData();

            unset($data['entity_id']);
            $customerAddress = $this->customerAddress->load($data['customer_address_id']);
            if (!$customerAddress->getId()) {
                unset($data['customer_address_id']);
            }
            /** @var Customer $customer */
            $customer = $this->customerFactory->create()->load($data['customer_id']);
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
     * @param Quote $quote
     * @param ItemInterface[] $items
     * @throws LocalizedException
     * @throws Exception
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
            /** @var Item $quoteItem */
            $quoteItem = $this->quoteItemFactory->create();

            // check store id
            $storeId = $this->checkStoreId($dataItem['store_id']);
            $dataItem['store_id'] = $storeId;

            // check parent item
            /** @var Item $parentItem */
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
        /** @var Store $store */
        $store = $this->storeFactory->create();
        if (!$store->load($storeId)->getId()) {
            return $this->webposHelper->getCurrentStoreView()->getId();
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
     * @param Quote $quote
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

    /**
     * Return address from quote to use in calculation.
     *
     * @param Quote $quote
     * @return Address
     */
    public function getAddress(Quote $quote)
    {
        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }

        return $address;
    }

    /**
     * Get Valid Earning Rules
     *
     * @param Quote $quote
     * @return array
     */
    public function getValidEarningRules(Quote $quote)
    {
        $validRules = [];
        $website = $quote->getStore()->getWebsiteId();
        $customerGroup = $quote->getCustomerGroupId();

        $address = $this->getAddress($quote);
        $rules = $this->ruleRepository->getRulesByAction(
            Actions::ORDER_COMPLETED_ACTION, // phpstan:ignore
            $website,
            $customerGroup
        );
        foreach ($rules as $rule) {
            if ($rule->validate($address)) {
                $validRules[] = $rule->getData();
            }
        }

        $rules = $this->ruleRepository->getRulesByAction(
            Actions::MONEY_SPENT_ACTION, // phpstan:ignore
            $website,
            $customerGroup
        );

        foreach ($rules as $rule) {
            if ($rule->validate($address)) {
                $data = $rule->getData();
                $validItemIds = [];
                /** @var Item $item */
                foreach ($quote->getAllItems() as $item) {
                    if ($this->earningChecker->isPossibleEarningOnItem($rule, $item)) {
                        $validItemIds[] = $item->getTmpItemId() ? $item->getTmpItemId() : $item->getItemId();
                    }
                }
                $data['valid_item_ids'] = $validItemIds;
                $validRules[] = $data;
            }
        }
        return $validRules;
    }
}
