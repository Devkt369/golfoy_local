<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\Checkout;

use Exception;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\AdminOrder\EmailSender;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\AddressFactory;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Tax\ItemFactory;
use Magento\Sales\Model\Order\TaxFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\GrandTotalDetailsInterfaceFactory;
use Magestore\Appadmin\Api\Event\DispatchServiceInterface;
use Magestore\Webpos\Api\Checkout\CheckoutRepositoryInterface;
use Magestore\Webpos\Api\Data\Checkout\Order\AddressInterface;
use Magestore\Webpos\Api\Data\Checkout\Order\Item\TaxInterface;
use Magestore\Webpos\Api\Data\Checkout\Order\ItemInterface;
use Magestore\Webpos\Api\Data\Checkout\Order\PaymentInterface;
use Magestore\Webpos\Api\Data\Checkout\Order\PaymentInterfaceFactory;
use Magestore\Webpos\Api\MultiSourceInventory\StockManagementInterface;
use Magestore\Webpos\Api\Sales\Order\InvoiceRepositoryInterface;
use Magestore\Webpos\Api\Sales\Order\ShipmentRepositoryInterface;
use Magestore\Webpos\Helper\Data;
use Magestore\Webpos\Log\Logger;
use Magestore\Webpos\Model\Checkout\Order\Payment;
use Magestore\Webpos\Model\Checkout\Order\Payment\Error as OrderPaymentError;
use Magestore\Webpos\Model\Request\ActionLog;
use Magestore\Webpos\Model\Request\ActionLogFactory;
use Magestore\Webpos\Model\Request\Actions\TakePaymentAction;
use Magestore\Webpos\Api\Sales\OrderManagement\AppendReservationsAfterOrderPlacementInterface;
use Magestore\Webpos\Model\Sales\OrderRepository;
use Magestore\WebposIntegration\Controller\Rest\RequestProcessor;
use Magestore\Webpos\Api\DataProvider\Session\GetSessionByIdInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magestore\Webpos\Model\Checkout\PosOrder\Consumer;

/**
 * Class CheckoutRepository
 *
 * Used for checkout repository
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CheckoutRepository implements CheckoutRepositoryInterface
{
    /**
     * State Pending
     *
     * Used for pending checkout
     */
    const STATE_PENDING = 'pending';

    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    /**
     * @var \Magento\Customer\Model\Address
     */
    protected $customerAddress;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var OrderPaymentInterface
     */
    protected $orderPaymentInterface;

    /**
     * @var \Magento\Sales\Model\Order\ItemFactory
     */
    protected $orderItemFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var StoreFactory
     */
    protected $storeFactory;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepositoryInterface;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepositoryInterface;

    /**
     * @var EmailSender
     */
    protected $emailSender;

    /**
     * @var PaymentInterfaceFactory
     */
    protected $paymentOrderInterfaceFactory;

    /**
     *
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Application Event Dispatcher
     *
     * @var ManagerInterface
     */
    protected $_eventManager;

    /**
     * Application Event Dispatcher
     *
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\CatalogInventory\Api\StockManagementInterface
     */
    protected $stockManagement;

    /**
     * @var \Magento\CatalogInventory\Model\Indexer\Stock\Processor
     */
    protected $stockIndexerProcessor;

    /**
     * @var Processor
     */
    protected $priceIndexer;

    /**
     *
     * @var \Magestore\Webpos\Helper\Order
     */
    protected $orderHelper;

    /**
     * @var \Magento\CatalogInventory\Model\ResourceModel\Stock\Item
     */
    protected $resourceStock;

    /**
     * @var StockManagementInterface
     */
    protected $MSIStockManagement;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    protected $parentItemId = [];
    protected $itemProductId = [];

    /**
     * @var GrandTotalDetailsInterfaceFactory
     */
    protected $detailsFactory;
    /**
     * @var ItemFactory
     */
    protected $itemTaxFactory;
    /**
     * @var PosOrderRepository
     */
    protected $posOrderRepository;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var ServiceInputProcessor
     */
    protected $serviceInputProcessor;
    /**
     * @var ActionLogFactory
     */
    protected $actionLogFactory;

    /**
     * @var AppendReservationsAfterOrderPlacementInterface
     */
    protected $appendReservation;

    /**
     * @var GetSessionByIdInterface
     */
    protected $getSessionById;

    /**
     * CheckoutRepository constructor.
     *
     * @param QuoteFactory $quoteFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param AddressFactory $addressFactory
     * @param \Magento\Customer\Model\Address $customerAddress
     * @param Customer $customer
     * @param OrderPaymentInterface $orderPaymentInterface
     * @param \Magento\Sales\Model\Order\ItemFactory $orderItemFactory
     * @param StoreManagerInterface $storeManager
     * @param StoreFactory $storeFactory
     * @param OrderRepository $orderRepository
     * @param InvoiceRepositoryInterface $invoiceRepositoryInterface
     * @param ShipmentRepositoryInterface $shipmentRepositoryInterface
     * @param EmailSender $emailSender
     * @param PaymentInterfaceFactory $paymentOrderInterfaceFactory
     * @param Registry $coreRegistry
     * @param RequestInterface $request
     * @param ManagerInterface $_eventManager
     * @param Data $helper
     * @param \Magento\CatalogInventory\Api\StockManagementInterface $stockManagement
     * @param \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor
     * @param Processor $priceIndexer
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\Item $resourceStock
     * @param \Magestore\Webpos\Helper\Order $orderHelper
     * @param StockManagementInterface $MSIStockManagement
     * @param OrderManagementInterface $orderManagement
     * @param TaxFactory $detailsFactory
     * @param ItemFactory $itemTaxFactory
     * @param ObjectManagerInterface $objectManager
     * @param PosOrderRepository $posOrderRepository
     * @param Logger $logger
     * @param ServiceInputProcessor $serviceInputProcessor
     * @param ActionLogFactory $actionLogFactory
     * @param AppendReservationsAfterOrderPlacementInterface $appendReservation
     * @param GetSessionByIdInterface $getSessionById
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        QuoteFactory $quoteFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        AddressFactory $addressFactory,
        \Magento\Customer\Model\Address $customerAddress,
        Customer $customer,
        OrderPaymentInterface $orderPaymentInterface,
        \Magento\Sales\Model\Order\ItemFactory $orderItemFactory,
        StoreManagerInterface $storeManager,
        StoreFactory $storeFactory,
        OrderRepository $orderRepository,
        InvoiceRepositoryInterface $invoiceRepositoryInterface,
        ShipmentRepositoryInterface $shipmentRepositoryInterface,
        EmailSender $emailSender,
        PaymentInterfaceFactory $paymentOrderInterfaceFactory,
        Registry $coreRegistry,
        RequestInterface $request,
        ManagerInterface $_eventManager,
        Data $helper,
        \Magento\CatalogInventory\Api\StockManagementInterface $stockManagement,
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor,
        Processor $priceIndexer,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\Item $resourceStock,
        \Magestore\Webpos\Helper\Order $orderHelper,
        StockManagementInterface $MSIStockManagement,
        OrderManagementInterface $orderManagement,
        TaxFactory $detailsFactory,
        ItemFactory $itemTaxFactory,
        ObjectManagerInterface $objectManager,
        PosOrderRepository $posOrderRepository,
        Logger $logger,
        ServiceInputProcessor $serviceInputProcessor,
        ActionLogFactory $actionLogFactory,
        AppendReservationsAfterOrderPlacementInterface $appendReservation,
        GetSessionByIdInterface $getSessionById
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->orderFactory = $orderFactory;
        $this->addressFactory = $addressFactory;
        $this->customerAddress = $customerAddress;
        $this->customer = $customer;
        $this->orderPaymentInterface = $orderPaymentInterface;
        $this->orderItemFactory = $orderItemFactory;
        $this->storeManager = $storeManager;
        $this->storeFactory = $storeFactory;
        $this->orderRepository = $orderRepository;
        $this->invoiceRepositoryInterface = $invoiceRepositoryInterface;
        $this->shipmentRepositoryInterface = $shipmentRepositoryInterface;
        $this->emailSender = $emailSender;
        $this->paymentOrderInterfaceFactory = $paymentOrderInterfaceFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->request = $request;
        $this->_eventManager = $_eventManager;
        $this->helper = $helper;
        $this->stockManagement = $stockManagement;
        $this->stockIndexerProcessor = $stockIndexerProcessor;
        $this->priceIndexer = $priceIndexer;
        $this->resourceStock = $resourceStock;
        $this->orderHelper = $orderHelper;
        $this->MSIStockManagement = $MSIStockManagement;
        $this->orderManagement = $orderManagement;
        $this->objectManager = $objectManager;
        $this->detailsFactory = $detailsFactory;
        $this->itemTaxFactory = $itemTaxFactory;
        $this->posOrderRepository = $posOrderRepository;
        $this->logger = $logger;
        $this->serviceInputProcessor = $serviceInputProcessor;
        $this->actionLogFactory = $actionLogFactory;
        $this->appendReservation = $appendReservation;
        $this->getSessionById = $getSessionById;
    }

    /**
     * @inheritdoc
     */
    public function sendEmailOrder(\Magento\Sales\Model\Order $order)
    {
        try {
            $this->emailSender->send($order);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function placeOrder(
        \Magestore\Webpos\Api\Data\Checkout\OrderInterface $order,
        $create_shipment,
        $create_invoice
    ) {
        /** @var PublisherInterface $messagePublisher */
        $messagePublisher = $this->objectManager->create(PublisherInterface::class);
        $messagePublisher->publish(Consumer::QUEUE_TOPIC_NAME, $order->getIncrementId());

        // Log process save to queue
        $processSaveToQueueLog = [
            'format' => 'process',
            'data' => [
                'app' => 'pos_server',
                'log_flow' => 'place_order',
                'business_flow' => 'place_order',
                'log_message' => sprintf('Add order #%s to queue', $order->getIncrementId()),
                'process_type' => 'system',
                'process_name' => 'add order to queue',
                'process_input' => [
                    'increment_id' => $order->getIncrementId()
                ]
            ]
        ];
        $this->_eventManager->dispatch('magestore_log', $processSaveToQueueLog);

        // Add request to message queue
        return [
            RequestProcessor::QUEUEING_PROCESS => 1
        ];
    }

    /**
     * Create order
     *
     * @param \Magestore\Webpos\Api\Data\Checkout\OrderInterface $order
     * @param bool $addPayment
     * @param bool $status
     * @return \Magento\Sales\Model\Order
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function createOrder(
        \Magestore\Webpos\Api\Data\Checkout\OrderInterface $order,
        $addPayment = false,
        $status = false
    ) {
        $this->_coreRegistry->register('create_order_webpos', true);
        $sessionId = $this->request->getParam(
            RequestProcessor::SESSION_PARAM_KEY
        );
        $sessionLogin = $this->getSessionById->execute($sessionId);
        $this->_coreRegistry->register('current_location_id', $sessionLogin->getLocationId());
        $this->_coreRegistry->register('pos_fulfill_online', $order->getPosFulfillOnline());
        $payments = $order->getPayments();
        $addresses = $order->getAddresses();
        $items = $order->getItems();

        $order->setEntityId('');
        $order->setQuoteAddressId('');
        $order->setBillingAddressId('');
        $order->setShippingAddressId('');
        if ($status == \Magento\Sales\Model\Order::STATE_HOLDED) {
            $befofreHoldStatus = 'pending';
            $order->setHoldBeforeState(\Magento\Sales\Model\Order::STATE_NEW);
            $order->setHoldBeforeStatus($befofreHoldStatus);
        }
        /**
         * check quote
         */
        $this->checkQuote($order);

        /** @var \Magento\Sales\Model\Order $newOrder */
        $newOrder = $this->orderFactory->create();
        try {
            $data = $order->getData();
            unset($data['items']);
            unset($data['payment']);
            unset($data['addresses']);
            unset($data['status_histories']);

            $newOrder->setData($data);

            // add address to order
            $this->addAddressToOrder($newOrder, $addresses);
            if ($addPayment) {
                // add payment to order
                $this->addPaymentToOrder($newOrder, $payments);
            }
            // add item to order
            $this->addItemToOrder($newOrder, $items);

            $storeId = $this->checkStoreId($newOrder->getStoreId());
            $newOrder->setStoreId($storeId);

            $newOrder->save();
            $this->afterCreateNewOrder($newOrder, $order);
            $this->_eventManager->dispatch('pos_sales_order_place_after', ['order' => $newOrder, 'data' => $order]);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        $this->_coreRegistry->unregister('create_order_webpos');
        return $newOrder;
    }

    /**
     * Remove quote id data if quote is not exist
     *
     * @param \Magestore\Webpos\Api\Data\Checkout\OrderInterface $order
     */
    public function checkQuote(\Magestore\Webpos\Api\Data\Checkout\OrderInterface &$order)
    {
        $quoteId = $order->getQuoteId();
        $quote = $this->quoteFactory->create()->load($quoteId);
        if (!$quote->getId()) {
            $order->setQuoteId('');
        }
    }

    /**
     * Add addresses
     *
     * @param \Magento\Sales\Model\Order $order
     * @param AddressInterface[] $addresses
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addAddressToOrder(&$order, $addresses)
    {
        foreach ($addresses as $address) {
            /** @var Address $add */
            $add = $this->addressFactory->create();
            $data = $address->getData();

            unset($data['entity_id']);
            unset($data['parent_id']);
            unset($data['quote_address_id']);
            $customerAddress = $this->customerAddress->load($data['customer_address_id']);
            if (!$customerAddress->getId()) {
                unset($data['customer_address_id']);
            }
            $customer = $this->customer->load($data['customer_id']);
            if (!$customer->getId()) {
                unset($data['customer_id']);
            }

            if (isset($data['street'][0]) && !$data['street'][0]) {
                $data['street'][0] = 'N/A';
            }
            if (isset($data['city']) && !$data['city']) {
                $data['city'] = 'N/A';
            }
            if (isset($data['region']) && !$data['region']) {
                $data['region'] = 'N/A';
            }
            if (isset($data['telephone']) && !$data['telephone']) {
                $data['telephone'] = 'N/A';
            }

            if ($address->getAddressType() == Address::TYPE_BILLING) {
                $add->setData($data);
                $order->setBillingAddress($add);
            } else {
                $add->setData($data);
                $add->setAddressType(Address::TYPE_SHIPPING);
                $order->setShippingAddress($add);
            }
        }
    }

    /**
     * Create new order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param PaymentInterface[] $payments
     * @param boolean $reCalculateTotal
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function addPaymentToOrder(&$order, $payments, $reCalculateTotal = false)
    {
        if ($order->getId()) {
            $oldOrder = $this->orderFactory->create()->load($order->getId());
        }
        /** @var OrderPaymentInterface $pm */
        $pm = $this->orderPaymentInterface;
        $pm->setMethod('multipaymentforpos');
        $pm->setAmountOrdered($order->getGrandTotal());
        $pm->setBaseAmountOrdered($order->getBaseGrandTotal());
        $pm->setAmountPaid(0);
        $pm->setBaseAmountPaid(0);
        $pm->setBaseShippingAmount(0);
        $pm->setShippingAmount(0);
        $totalPaid = 0;
        $baseTotalPaid = 0;
        $takeBaseAmount = 0;
        $takeAmount = 0;
        $posChange = 0;
        $basePosChange = 0;
        $paymentsReferenceNumber = '';
        if (!empty($payments) && count($payments)) {
            foreach ($payments as $payment) {
                if ($payment->getReferenceNumber()) {
                    $paymentsReferenceNumber .= ',' . $payment->getReferenceNumber();
                }
                if ($payment->getIsPayLater()) {
                    continue;
                }
                $totalPaid += (float)$payment->getAmountPaid();
                $baseTotalPaid += (float)$payment->getBaseAmountPaid();
                $posChange += (float)$payment->getAmountChange();
                $basePosChange += (float)$payment->getBaseAmountChange();
                if (!$payment->getIsPaid()) {
                    $takeAmount += (float)$payment->getAmountPaid();
                    $takeBaseAmount += (float)$payment->getBaseAmountPaid();
                }
                $pm->setAmountPaid(round((float)$payment->getAmountPaid() + (float)$pm->getAmountPaid(), 4));
                $pm->setBaseAmountPaid(
                    round((float)$payment->getBaseAmountPaid() + (float)$pm->getBaseAmountPaid(), 4)
                );
            }
            if ($reCalculateTotal) {
                if ($order->getPosBasePreTotalPaid() > 0 && $order->getPosPreTotalPaid() > 0) {
                    if ($oldOrder) {
                        $totalPaid = $oldOrder->getTotalPaid() + $takeAmount;
                        $baseTotalPaid = $oldOrder->getBaseTotalPaid() + $takeBaseAmount;
                    } else {
                        $totalPaid = $order->getTotalPaid() + $takeAmount;
                        $baseTotalPaid = $order->getBaseTotalPaid() + $takeBaseAmount;
                    }
                }
                $order->setTotalPaid(min($totalPaid, $order->getGrandTotal()));
                $order->setBaseTotalPaid(min($baseTotalPaid, $order->getBaseGrandTotal()));
                $totalDue = max($order->getGrandTotal() - $totalPaid, 0);
                $baseTotalDue = max($order->getBaseGrandTotal() - $baseTotalPaid, 0);
                $order->setTotalDue($totalDue);
                $order->setBaseTotalDue($baseTotalDue);
                $order->setPosChange($posChange);
                $order->setBasePosChange($basePosChange);
            }
        }
        $order->setPayment($pm);
        if ($paymentsReferenceNumber) {
            $order->setData('payment_reference_number', $paymentsReferenceNumber);
        }
    }

    /**
     * Add items
     *
     * @param \Magento\Sales\Model\Order $order
     * @param ItemInterface[] $items
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addItemToOrder(&$order, $items)
    {
        $dataItems = [];
        foreach ($items as $item) {
            $id = $item->getItemId();
            $item->setItemId('');
            $item->setOrderId('');
            $item->setQuoteItemId('');

            // check store id
            $storeId = $this->checkStoreId($item->getStoreId());
            $item->setStoreId($storeId);

            $dataItems[$id] = $item->getData();
            $dataItems[$id]['tmp_item_id'] = $id;
            if ($appliedTaxes = $item->getData(ItemInterface::APPLIED_TAXES)) {
                $dataItems[$id]['applied_taxes'] = $appliedTaxes;
            }
        }
        $tmpData = $dataItems;
        $removedData = [];
        foreach ($tmpData as $key => $datum) {
            if (isset($datum['parent_item_id']) && !empty($datum['parent_item_id'])) {
                if ($datum['parent_item_id'] && in_array($datum['parent_item_id'], array_keys($tmpData))) {
                    if (!in_array($datum['parent_item_id'], $this->parentItemId)) {
                        $dataItems[$key]['parent_item'] = $dataItems[$datum['parent_item_id']];
                        $removedData[] = $datum['parent_item_id'];
                    }
                    // check for bundle product
                    $this->parentItemId[$datum['tmp_item_id']] = $datum['parent_item_id'];
                }
            }
        }
        // remove data parent item
        foreach ($removedData as $remove) {
            unset($dataItems[$remove]);
        }

        foreach ($dataItems as $key => $dataItem) {
            /** @var Item $orderItem */
            $orderItem = $this->orderItemFactory->create();

            // check store id
            $storeId = $this->checkStoreId($dataItem['store_id']);
            $dataItem['store_id'] = $storeId;

            // check parent item
            /** @var Item $parentItem */
            $parentItem = $this->orderItemFactory->create();
            if (isset($dataItem['parent_item'])) {
                $parentItemData = $dataItem['parent_item'];
                unset($dataItem['parent_item']);

                // check store id
                $storeId = $this->checkStoreId($parentItemData['store_id']);
                $parentItemData['store_id'] = $storeId;

                $parentItem->setData($parentItemData);
            }

            if (isset($dataItem['parent_item_id'])) {
                unset($dataItem['parent_item_id']);
            }

            $orderItem->setData($dataItem);

            if ($parentItem->getData()) {
                $order->addItem($parentItem);
                $orderItem->setParentItem($parentItem);
            }

            $order->addItem($orderItem);
        }
    }

    /**
     * After create new order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magestore\Webpos\Api\Data\Checkout\OrderInterface $params
     * @throws CouldNotSaveException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * phpcs:disable Generic.Metrics.NestingLevel
     */
    public function afterCreateNewOrder($order, $params)
    {
        if ($order->getShippingAddress()) {
            $order->setData('shipping_address_id', $order->getShippingAddress()->getEntityId());
        }
        if ($order->getBillingAddress()) {
            $order->setData('billing_address_id', $order->getBillingAddress()->getEntityId());
        }
        try {
            $taxIds = [];
            $taxDetail = $params->getAppliedTaxes();
            foreach ($taxDetail as $tax) {
                $taxFactory = $this->detailsFactory->create([]);
                $taxFactory->setAmount($tax['amount']);
                $taxFactory->setCode($tax['code']);
                $taxFactory->setTitle($tax['title']);
                $taxFactory->setOrderId($order->getId());
                $taxFactory->setPercent($tax['percent']);
                $taxFactory->setProcess($tax['process']);
                $taxFactory->setBaseAmount($tax['base_amount']);
                $taxFactory->setBaseRealAmount($tax['base_real_amount']);
                $taxFactory->save();

                $taxIds[$tax['code']] = $taxFactory->getTaxId();
            }

            if (count($taxIds)) {
                /** @var ItemInterface $item */
                foreach ($order->getAllItems() as $item) {
                    if ($appliedTaxes = $item->getAppliedTaxes()) {
                        /** @var TaxInterface $appliedTax */
                        foreach ($appliedTaxes as $code => $appliedTax) {
                            if ($taxId = $taxIds[$code]) {
                                /** @var \Magento\Sales\Model\Order\Tax\Item $orderTaxItem */
                                $orderTaxItem = $this->itemTaxFactory->create();
                                $taxItemData = [
                                    'tax_id' => $taxId,
                                    'item_id' => (int)$item->getItemId(),
                                    'tax_percent' => $appliedTax->getTaxPercent(),
                                    'amount' => $appliedTax->getAmount(),
                                    'base_amount' => $appliedTax->getBaseAmount(),
                                    'real_amount' => $appliedTax->getRealAmount(),
                                    'real_base_amount' => $appliedTax->getRealBaseAmount(),
                                    'associated_item_id' => $appliedTax->getAssociatedItemId(),
                                    'taxable_item_type' => $appliedTax->getTaxableItemType()
                                ];
                                $orderTaxItem->setData($taxItemData)->save();
                            }
                        }
                    }
                }
            }

            $order->setAppliedTaxIsSaved(true);
            $order->save();
            $this->checkOrderItems($order->getAllItems());
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
    }

    /**
     * Subtract order items qtys from stock items related with order items products.
     *
     * @param \Magento\Sales\Model\Order $order
     */
    public function subtractOrderInventory($order)
    {
        $items = $this->getProductQty($order->getAllItems());
        $itemsForReindex = $this->stockManagement->registerProductsSale(
            $items,
            $order->getStore()->getWebsiteId()
        );
        $productIds = [];
        foreach ($itemsForReindex as $item) {
            $item->setData('stock_status_changed_auto', 1);
            $item->save();
            $productIds[] = $item->getProductId();
        }
        if (!empty($productIds)) {
            $this->stockIndexerProcessor->reindexList($productIds);
            $this->priceIndexer->reindexList($productIds);
        }
    }

    /**
     * Prepare array with information about used product qty and product stock item
     *
     * @param array $relatedItems
     * @return array
     */
    public function getProductQty($relatedItems)
    {
        $items = [];
        foreach ($relatedItems as $item) {
            $productId = $item->getProductId();
            if (!$productId) {
                continue;
            }
            if (isset($items[$productId])) {
                $items[$productId] += $item->getQtyOrdered();
            } else {
                $items[$productId] = $item->getQtyOrdered();
            }
        }
        return $items;
    }

    /**
     * Check order items
     *
     * @param Item[] $items
     * @throws Exception
     */
    public function checkOrderItems($items)
    {
        foreach ($items as $item) {
            if ($item->getParentItemId() == null && isset($this->parentItemId[$item->getData('tmp_item_id')])) {
                $item->setParentItemId(
                    $this->getItemIdByTmpItemId($this->parentItemId[$item->getData('tmp_item_id')])
                )->save();
            }
        }
    }

    /**
     * Get item id
     *
     * @param int $tmpItemId
     * @return mixed
     */
    public function getItemIdByTmpItemId($tmpItemId)
    {
        if (!isset($this->itemProductId[$tmpItemId])) {
            /** @var Collection $collection */
            $collection = $this->orderItemFactory->create()->getCollection();
            $collection->addFieldToFilter('tmp_item_id', $tmpItemId);
            $collection->addOrder('item_id', 'DESC');

            $this->itemProductId[$tmpItemId] = $collection->getFirstItem()->getId();
        }
        return $this->itemProductId[$tmpItemId];
    }

    /**
     * Create webpos payments
     *
     * @param \Magento\Sales\Model\Order $order
     * @param PaymentInterface[] $payments
     * @throws CouldNotSaveException
     */
    public function createWebposOrderPayment(&$order, $payments)
    {
        $orderPayments = [];
        if (!empty($payments) && count($payments)) {
            foreach ($payments as $payment) {
                if (!$payment->getIsPaid()) {
                    $data = $payment->getData();
                    /** @var Payment $paymentModel */
                    $paymentModel = $this->paymentOrderInterfaceFactory->create();
                    $paymentModel->setData($data);
                    $paymentModel->setOrderId($order->getId());
                    $this->_eventManager->dispatch(
                        'order_webpos_payment_save_before',
                        [
                            'webpos_payment' => $paymentModel,
                            'payment_data' => $payment
                        ]
                    );
                    try {
                        $paymentModel->getResource()->save($paymentModel);
                    } catch (Exception $exception) {
                        /** @var OrderPaymentError $paymentError */
                        $paymentError = $this->objectManager->create(OrderPaymentError::class);
                        $paymentError->saveErrorLog($paymentModel);
                    }

                    $orderPayments[] = $paymentModel;
                    if ($data['method'] == 'store_credit' && $order->getCustomerId()) {
                        $this->orderRepository->changeCustomerCredit(-$data['base_amount_paid'], $order);
                    }
                }
            }
        }
        if (count($orderPayments)) {
            $order->setPayments($orderPayments);
        }
    }

    /**
     * Check store id
     *
     * @param int $storeId
     * @return int
     */
    public function checkStoreId($storeId)
    {
        /** @var Store $store */
        $store = $this->storeFactory->create();
        if (!$store->load($storeId)->getId()) {
            return $this->helper->getCurrentStoreView()->getId();
        } else {
            return $storeId;
        }
    }

    /**
     * Get correct status from order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return \Magestore\Webpos\Api\Data\Checkout\OrderInterface
     */
    public function verifyOrderReturn($order)
    {
        return $this->orderHelper->verifyOrderReturn($order);
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function takePayment(
        $payments,
        $incrementId,
        $createInvoice,
        $requestIncrementId
    ) {
        try {
            /** @var \Magestore\Webpos\Api\Data\Checkout\OrderInterface $existedOrder */
            $existedOrder = $this->orderRepository->getWebposOrderByIncrementId($incrementId);
        } catch (Exception $e) {
            $existedOrder = false;
        }
        if (!$existedOrder || !$existedOrder->getEntityId()) {
            throw new LocalizedException(
                __('The order that you want to create take shipment has not been converted successfully!'),
                new Exception(),
                DispatchServiceInterface::EXCEPTION_CODE_SAVED_REQUEST_TO_SERVER
            );
        }

        try {
            $result = $this->processTakePaymentActionLog($requestIncrementId);

            if (!$result) {
                throw new LocalizedException(
                    __('Some things went wrong when trying to process new take payment request!'),
                    new Exception(),
                    DispatchServiceInterface::EXCEPTION_CODE_SAVED_REQUEST_TO_SERVER
                );
            }

            return $result;
        } catch (Exception $e) {
            throw new LocalizedException(
                __('Some things went wrong when trying to process new take payment request!'),
                new Exception(),
                DispatchServiceInterface::EXCEPTION_CODE_SAVED_REQUEST_TO_SERVER
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function processTakePaymentActionLog($requestIncrementId)
    {
        /** @var ActionLog $actionLog */
        $actionLog = $this->actionLogFactory->create();
        $actionLog->load($requestIncrementId, 'request_increment_id');
        if (!$actionLog->getId() ||
            $actionLog->getActionType() != TakePaymentAction::ACTION_TYPE ||
            $actionLog->getStatus() == ActionLog::STATUS_COMPLETED) {
            return false;
        }

        // Modify request params
        $requestParams = $this->request->getParams();
        $requestLocationId = $actionLog->getLocationId();
        $requestParams[PosOrder::PARAM_ORDER_LOCATION_ID] = $requestLocationId;
        $this->request->setParams($requestParams);
        // End: Modify request params

        // Convert array to object parameter
        $params = json_decode($actionLog->getParams(), true);
        $params = $this->serviceInputProcessor->process(
            CheckoutRepositoryInterface::class,
            'takePayment',
            $params
        );
        /** @var PaymentInterface[] $payments */
        $payments = $params[0];
        $incrementId = $params[1];
        $createInvoice = $params[2];
        // End: Convert array to object parameter

        ////////////////////////////////
        /// Process Take Payment
        ////////////////////////////////
        try {
            $newOrder = $this->orderFactory->create()->load($incrementId, 'increment_id');

            if ($newOrder->getId()) {
                $this->addPaymentToOrder($newOrder, $payments, true);
                $newOrder->save();

                if ($createInvoice) {
                    $this->posOrderRepository->processInvoice($newOrder);
                }

                $this->sendEmailOrder($newOrder);

                // create webpos order payment
                if ($payments) {
                    $this->createWebposOrderPayment($newOrder, $payments, true);
                }
                $newOrder = $this->orderRepository->get($newOrder->getId());

                // Update action log
                $actionLog->setStatus(ActionLog::STATUS_COMPLETED)->save();

                return $this->verifyOrderReturn($newOrder);
            } else {
                // Update action log
                $actionLog->setStatus(ActionLog::STATUS_FAILED)->save();
                return false;
            }
        } catch (Exception $e) {
            $this->logger->info($incrementId);
            $this->logger->info($e->getMessage());
            $this->logger->info($e->getTraceAsString());
            $this->logger->info('___________________________________________');
            // Update action log
            $actionLog->setStatus(ActionLog::STATUS_FAILED)->save();
            return false;
        }
    }

    /**
     * Hold order
     *
     * @param \Magestore\Webpos\Api\Data\Checkout\OrderInterface $order
     * @return \Magestore\Webpos\Api\Data\Checkout\OrderInterface
     * @throws CouldNotSaveException
     */
    public function holdOrder(\Magestore\Webpos\Api\Data\Checkout\OrderInterface $order)
    {
        $newOrder = $this->createOrder($order, true, \Magento\Sales\Model\Order::STATE_HOLDED);
        $this->_eventManager->dispatch('sales_order_place_after', ['order' => $newOrder]);
        $this->appendReservationsAfterOrderPlacement($newOrder);
        $this->subtractOrderInventory($newOrder);
        if ($order->getStatusHistories()) {
            /* Call to load magento order model */
            $magentoOrder = $this->orderRepository->getMagentoOrderByIncrementId($newOrder->getIncrementId());
            foreach ($order->getStatusHistories() as $statusHistories) {
                $this->orderRepository->saveComment($magentoOrder, $statusHistories);
            }
        }
        $holdedOrder = $this->orderRepository->get($newOrder->getId());
        return $holdedOrder;
    }

    /**
     * Append reservations
     *
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function appendReservationsAfterOrderPlacement(OrderInterface $order)
    {
        if ($this->MSIStockManagement->getStockId()) {
            $order = $this->appendReservation->execute($order);
        }
        return $order;
    }
}
