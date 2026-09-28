<?php

namespace ArtM\SFMC\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use ArtM\SFMC\Model\SfmcFactory;
use ArtM\SFMC\Model\SfmcOrderFailedFactory;
use ArtM\SFMC\Model\SfmcOrderBatchFactory;
use ArtM\SFMC\Model\SfmcCustomerBatchFactory;
use ArtM\SFMC\Model\SfmcCustomerFailedFactory;
use ArtM\SFMC\Model\SfmcOrderSyncSucceedFactory;
use ArtM\SFMC\Model\SfmcAbandonedCartFactory;
use ArtM\SFMC\Model\SfmcAbandonedCouponFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Api\ProductRepositoryInterface;
use ArtM\SFMC\Model\SfmcProductFailedFactory;
use ArtM\SFMC\Model\SfmcProductBatchFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\OrderFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use ArtM\SFMC\Model\SfmcCouponSyncFactory;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as CustomQuoteCollectionFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Store\Model\StoreManagerInterface;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\Coupon\Codegenerator;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory as CouponCollectionFactory;
// use Magento\Catalog\Model\CategoryFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;

class Data extends AbstractHelper
{
    protected $groupRepository;
    protected $customerCollectionFactory;
    protected $configWriter;
    protected $resourceConnection;
    protected $customerRepository;
    protected $timezone;
    protected $_sfmcCustomerBatchFactory;
    protected $customerFactory;
    protected $addressRepository;
    protected $productCollectionFactory;
    protected $SfmcProductFailedFactory;
    protected $SfmcProductBatchFactory;
    protected $productRepository;
    protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $orderFactory;
    protected $SfmcOrderFailedFactory;
    protected $SfmcOrderBatchFactory;
    protected $SfmcOrderSyncSucceedFactory;
    protected $getSalableQuantityDataBySku;
    protected $orderLogger;
    protected $productLogger;
    protected $productAttributeRepository;
    protected $SfmcCouponSync;
    protected $customQuoteCollectionFactory;
    protected $SfmcAbandonedCartFactory;
    protected $SfmcAbandonedCouponFactory;
    protected $quoteRepository;
    protected $ruleFactory;
    protected $couponGenerator;
    protected $couponCollectionFactory;
    protected $attributeSetRepository;
    protected $productAction;
    protected $_orderCollection;
    protected $_scopeConfig;
    protected $_sfmcModel;
    protected $_SfmcCustomerFailed;
    protected $_SfmcLogger;
    protected $deleteLogger;
    protected $cartLogger;
    protected $abandonedCouponLogger;
    protected $curl;
    protected $storeManager;
    protected $couponFactory;

    public function __construct(
        Context $context,
        Codegenerator $couponGenerator,
        GroupRepositoryInterface $groupRepository,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        TimezoneInterface $timezone,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        WriterInterface $configWriter,
        CustomerCollectionFactory $customerCollectionFactory,
        SfmcFactory $_sfmcModel,
        SfmcAbandonedCouponFactory $SfmcAbandonedCouponFactory,
        SfmcCustomerBatchFactory $SfmcCustomerBatchFactory,
        SfmcCustomerFailedFactory $_SfmcCustomerFailed,
        ResourceConnection $resourceConnection,
        AddressRepositoryInterface $addressRepository,
        ScopeConfigInterface $_scopeConfig,
        CollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        SfmcProductFailedFactory $SfmcProductFailedFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SfmcProductBatchFactory $SfmcProductBatchFactory,
        OrderFactory $orderFactory,
        SfmcOrderBatchFactory $SfmcOrderBatchFactory,
        SfmcOrderFailedFactory $SfmcOrderFailedFactory,
        SfmcOrderSyncSucceedFactory $SfmcOrderSyncSucceedFactory,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        SfmcCouponSyncFactory $SfmcCouponSync,
        CustomQuoteCollectionFactory $customQuoteCollectionFactory,
        SfmcAbandonedCartFactory $SfmcAbandonedCartFactory,
        QuoteRepository $quoteRepository,
        StoreManagerInterface $storeManager,
        RuleFactory $ruleFactory,
        CouponFactory $coupon,
        CouponCollectionFactory $couponCollectionFactory,
        ProductAction $productAction,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $_orderCollection,
        \ArtM\SFMC\Logger\Logger $sfmcLogger,
        \ArtM\SFMC\Logger\OrderLogger $orderLogger,
        \ArtM\SFMC\Logger\ProductLogger $productLogger,
        \ArtM\SFMC\Logger\DeleteLogger $deleteLogger,
        \ArtM\SFMC\Logger\CartLogger $cartLogger,
        \ArtM\SFMC\Logger\AbandonedCouponLogger $AbandonedCouponLogger,
        AttributeSetRepositoryInterface $attributeSetRepository,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        parent::__construct($context);
        $this->_orderCollection = $_orderCollection;
        $this->_sfmcModel = $_sfmcModel;
        $this->SfmcOrderFailedFactory = $SfmcOrderFailedFactory;
        $this->SfmcOrderBatchFactory = $SfmcOrderBatchFactory;
        $this->SfmcAbandonedCouponFactory = $SfmcAbandonedCouponFactory;
        $this->_scopeConfig = $_scopeConfig;
        $this->configWriter = $configWriter;
        $this->timezone = $timezone;
        $this->_sfmcCustomerBatchFactory = $SfmcCustomerBatchFactory;
        $this->_SfmcCustomerFailed = $_SfmcCustomerFailed;
        $this->customerRepository = $customerRepository;
        $this->resourceConnection = $resourceConnection;
        $this->_SfmcLogger = $sfmcLogger;
        $this->orderLogger = $orderLogger;
        $this->productLogger = $productLogger;
        $this->deleteLogger = $deleteLogger;
        $this->cartLogger = $cartLogger;
        $this->abandonedCouponLogger = $AbandonedCouponLogger;
        $this->customerFactory = $customerFactory;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->curl = $curl;
        $this->groupRepository = $groupRepository;
        $this->addressRepository = $addressRepository;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->SfmcProductFailedFactory = $SfmcProductFailedFactory;
        $this->SfmcProductBatchFactory = $SfmcProductBatchFactory;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderFactory = $orderFactory;
        $this->customQuoteCollectionFactory = $customQuoteCollectionFactory;
        $this->SfmcOrderSyncSucceedFactory = $SfmcOrderSyncSucceedFactory;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->SfmcCouponSync = $SfmcCouponSync;
        $this->productAction = $productAction;
        $this->SfmcAbandonedCartFactory = $SfmcAbandonedCartFactory;
        $this->quoteRepository = $quoteRepository;
        $this->storeManager = $storeManager;
        $this->ruleFactory = $ruleFactory;
        $this->couponGenerator = $couponGenerator;
        $this->couponFactory = $coupon;
        $this->couponCollectionFactory = $couponCollectionFactory;
        $this->attributeSetRepository = $attributeSetRepository;
    }

    public function getNumCustomerCollection()
    {
        $customerCollection = $this->customerCollectionFactory->create();
        $totalCustomers = $customerCollection->getSize();

        return $totalCustomers;
    }

    public function getNumCustomerSynced()
    {

        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addAttributeToFilter('sfmcsync', ['eq' => 1]);

        $enabledCustomersCount = $customerCollection->getSize();

        return $enabledCustomersCount;
    }

    public function isAbandonedCartCronEnable()
    {
        $module_enable = $this->getConfig('sfmc/module_abandoned/cron');
        if ($module_enable) {
            return true;
        } else {
            return false;
        }
        return false;
    }

    public function IsModuleEnable()
    {
        $config_path = "sfmc/module_status/status";
        $module_enable = $this->getConfig($config_path);
        if ($module_enable == 0) {
            return false;
        } else if ($module_enable == 1) {
            return true;
        }
        return false;
    }

    public function IsCustomerCronEnable()
    {
        if ($this->IsModuleEnable()) {
            $config_path = "sfmc/module/cron";
            $module_enable = $this->getConfig($config_path);
            if ($module_enable == 0) {
                return false;
            } else if ($module_enable == 1) {
                return true;
            }
            return false;
        } else {
            return false;
        }
        return false;
    }

    public function getBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    public function IsOrderCronEnable()
    {
        if ($this->IsModuleEnable()) {
            $config_path = "sfmc/module_order/cron";
            $module_enable = $this->getConfig($config_path);
            if ($module_enable == 0) {
                return false;
            } else if ($module_enable == 1) {
                return true;
            }
            return false;
        } else {
            return false;
        }
        return false;
    }

    public function IsProductCronEnable()
    {
        if ($this->IsModuleEnable()) {
            $config_path = "sfmc/module_product/cron";
            $module_enable = $this->getConfig($config_path);
            if ($module_enable == 0) {
                return false;
            } else if ($module_enable == 1) {
                return true;
            }
            return false;
        } else {
            return false;
        }
        return false;
    }

    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    public function getCustomerCronConfig()
    {
        if ($this->IsModuleEnable()) {
            $data = array();
            $cron_after = $this->getConfig('sfmc/module/cron_after_message');
            $batch_of = $this->getConfig('sfmc/module/batch_of');
            $data['cron_after'] = $cron_after;
            $data['batch_of'] = $batch_of;
            return $data;
        }
        return false;
    }
    public function GetCustomerCreateUrl()
    {
        $CustomerCreateUrl = $this->getConfig('sfmc/module/create_customer_url');
        if ($CustomerCreateUrl != '') {
            return $CustomerCreateUrl;
        } else {
            $this->_SfmcLogger->info("Customer Url is empty in admin");
            return false;
        }
        $this->_SfmcLogger->info("Customer Url was not created in admin");
        return false;
    }

    public function GetOrderCreateUrl()
    {
        $orderCreateUrl = $this->getConfig('sfmc/module_order/create_order_url');
        if ($orderCreateUrl != '') {
            return $orderCreateUrl;
        } else {
            $this->_SfmcLogger->info("Order Url is empty in admin");
            return false;
        }
        $this->_SfmcLogger->info("Order Url was not created in admin");
        return false;
    }
    public function GetTrackingOrderCreateUrl()
    {
        $orderCreateUrl = $this->getConfig('sfmc/module_order/tracking_order_url');
        if ($orderCreateUrl != '') {
            return $orderCreateUrl;
        }
        return false;
    }
    public function GetCouponCreateUrl()
    {
        $orderCreateUrl = $this->getConfig('sfmc/module_coupon/create_coupon_url');
        if ($orderCreateUrl != '') {
            return $orderCreateUrl;
        } else {
            $this->_SfmcLogger->info("Coupon Url is empty in admin");
            return false;
        }
        $this->_SfmcLogger->info("Coupon Url was not created in admin");
        return false;
    }

    public function GetProductCreateUrl()
    {
        $productCreateUrl = $this->getConfig('sfmc/module_product/create_product_url');
        if ($productCreateUrl != '') {
            return $productCreateUrl;
        } else {
            $this->_SfmcLogger->info("Product Url is empty in admin");
            return false;
        }
        $this->_SfmcLogger->info("Product Url was not created in admin");
        return false;
    }

    public function newGetProductCreateUrl()
    {
        $productCreateUrl = $this->getConfig('sfmc/module_product/new_create_product_url');
        if ($productCreateUrl != '') {
            return $productCreateUrl;
        } else {
            $this->_SfmcLogger->info("Product Url is empty in admin");
            return false;
        }
        $this->_SfmcLogger->info("Product Url was not created in admin");
        return false;
    }

    public function GetKeys()
    {
        $data = array();
        $bearerKey = $this->getConfig('sfmc/configuration/bearer_key');
        $clientId = $this->getConfig('sfmc/configuration/client_id');
        $clientSecret = $this->getConfig('sfmc/configuration/client_secret');
        $mid = $this->getConfig('sfmc/configuration/mid');
        $createBearerKeyUrl = $this->getConfig('sfmc/configuration/create_bearer_key');
        $createBearerKeyUpdateAfter = $this->getConfig('sfmc/configuration/bearer_key_update_after');
        $createCustomerURL = $this->getConfig('sfmc/module/create_customer_url');

        // fetch bearer_key value from database
        $configPath = 'sfmc/configuration/bearer_key';
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(['config' => $connection->getTableName('core_config_data')])
            ->where('config.path = ?', $configPath)
            ->order('config_id DESC');
        $updatedAt = $connection->fetchAll($select);
        $bearerKey = $updatedAt['0']['value'];

        $data["bearerKey"] = $bearerKey;
        $data["clientId"] = $clientId;
        $data["clientSecret"] = $clientSecret;
        $data["mid"] = $mid;
        $data["url"] = $createBearerKeyUrl;

        return $data;
    }

    public function lastUpdateBearerKey()
    {
        try {
            $configPath = 'sfmc/configuration/bearer_key';
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from(['config' => $connection->getTableName('core_config_data')])
                ->where('config.path = ?', $configPath)
                ->order('config_id DESC');
            $updatedAt = $connection->fetchAll($select);
            $indianTime = $updatedAt['0']['updated_at'];

            $utcTimeZone = new \DateTimeZone('UTC');
            $dateTimeUTC = new \DateTime($indianTime, $utcTimeZone);
            $targetTimeZone = $this->timezone->getConfigTimezone('default');
            $dateTimeUTC->setTimezone(new \DateTimeZone($targetTimeZone));
            $indianTime = $dateTimeUTC->format('Y-m-d H:i:s');
            return $indianTime;
        } catch (\Exception $e) {
            return 'Please Update time !!';
        }
    }

    public function enableSfmcAttribute($customerId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $customer->setCustomAttribute('sfmcsync', 1);
            $this->customerRepository->save($customer);
            return true;
        } catch (\Exception $e) {
            $this->_SfmcLogger->error($e->getMessage());
            $this->_SfmcLogger->info("Not able to save this customer " . $customerId);
            return false;
        }
        return false;
    }

    public function disableSfmcAttribute($customerId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $customer->setCustomAttribute('sfmcsync', 0);
            $this->customerRepository->save($customer);
            return true;
        } catch (\Exception $e) {
            $this->_SfmcLogger->error($e->getMessage() . ' ' . $customerId);
            $this->_SfmcLogger->info("Not able to save this customer " . $customerId);
            return false;
        }
        return false;
    }

    public function enableSfmcProductAttributeNew($product)
    {
        try {
            $product->setData('sfmcsyncproduct', '1');
            $this->productRepository->save($product);
            return true;
        } catch (\Exception $e) {
            $this->_SfmcLogger->error($e->getMessage() . ' ' . $product->getId());
            $this->_SfmcLogger->info("Not able to save this product " . $product->getId());
            return false;
        }
        return false;
    }

    public function enableSfmcProductAttribute($product)
    {
        try {
            if (!$product || !$product->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid product instance.'));
            }
            $productId = $product->getId();
            $this->productAction->updateAttributes(
                [$productId],
                ['sfmcsyncproduct' => '1'],
                1
            );
            return true;
        } catch (\Exception $e) {
            $this->_SfmcLogger->error($e->getMessage() . ' Product ID: ' . ($product->getId() ?? 'N/A'));
            return false;
        }
        return false;
    }

    public function disableSfmcProductAttribute($productId)
    {
        try {
            $product = $productId; //$this->productRepository->getById($productId);
            $currentValue = $product->getData('sfmcsyncproduct');
            $product->setData('sfmcsyncproduct', '0');
            $this->productRepository->save($product);
            return true;
        } catch (\Exception $e) {
            $this->_SfmcLogger->error($e->getMessage() . ' ' . $productId);
            $this->_SfmcLogger->info("Not able to save this product " . $productId);
            return false;
        }
        return false;
    }

    public function removeCustomerIdFromFailedtable($customer_id)
    {
        try {
            $sfmcCustomerFailed = $this->_SfmcCustomerFailed->create();
            $sfmcCustomerFailed = $sfmcCustomerFailed->getCollection();
            $sfmcCustomerFailed = $sfmcCustomerFailed->addFieldToFilter('cust_id', $customer_id);
            foreach ($sfmcCustomerFailed as $sfmcCustomerFailedDelete) {
                if ($sfmcCustomerFailed) {
                    $sfmcCustomerFailedDelete->delete();
                }
            }
            return true;
        } catch (\Exception $e) {
            return 'not found';
        }
    }

    public function ValidateBearerKey()
    {

        $updatedTime = $this->lastUpdateBearerKey();
        $updatedTime = strtotime($updatedTime);
        $currentTimestamp = time();

        $timeDifference = $currentTimestamp - $updatedTime;
        $timeDifferenceInMinutes = $timeDifference / 60;

        $createBearerKeyUpdateAfter = $this->getConfig('sfmc/configuration/bearer_key_update_after');

        if ($timeDifferenceInMinutes > $createBearerKeyUpdateAfter) {
            return true;
        } else {
            return false;
        }
    }

    public function updateStoreConfig($path, $value)
    {
        $this->configWriter->save($path, $value);
    }

    public function cleanTypeStoreValue()
    {
        $failedCustomerList = [];
        $SfmcCustomerFailedCollection = $this->_SfmcCustomerFailed->create()->getCollection();
        $isEmptyTable = $SfmcCustomerFailedCollection->getSize();
        if ($isEmptyTable > 0) {
            $sfmcData = $SfmcCustomerFailedCollection->getData();
            foreach ($sfmcData as $failedData) {
                $failedCustomerList[] = $failedData['cust_id'];
            }
            return $failedCustomerList;
        } else {
            return false;
        }
        return false;
    }


    public function failedProductCollection()
    {
        $failedProductList = [];
        $SfmcProductFailedCollection = $this->SfmcProductFailedFactory->create()->getCollection();
        $isEmptyTable = $SfmcProductFailedCollection->getSize();
        if ($isEmptyTable > 0) {
            $sfmcData = $SfmcProductFailedCollection->getData();
            foreach ($sfmcData as $failedData) {
                $failedProductList[] = $failedData['product_id'];
            }
            return $failedProductList;
        } else {
            return false;
        }
        return false;
    }

    public function DisbaleSyncAttr()
    {
        echo "<br>Called DisbaleSyncAttr";
        $customerCollection = $this->customerCollectionFactory->create()
            ->addAttributeToFilter('sfmcsync', 1);

        $customerLists = $customerCollection->getItems();
        echo "<br>Count: " . $customerCollection->count();

        foreach ($customerLists as $customerList) {
            $customerId = $customerList->getId();
            echo "<br>customerId: " . $customerId;
            $this->disableSfmcAttribute($customerId);
        }
    }

    public function GetCurrentDateTime()
    {
        $currentTimestamp = date('Y-m-d H:i:s');
        $utcTimeZone = new \DateTimeZone('UTC');
        $dateTimeUTC = new \DateTime($currentTimestamp, $utcTimeZone);
        $targetTimeZone = $this->timezone->getConfigTimezone('default');
        $dateTimeUTC->setTimezone(new \DateTimeZone($targetTimeZone));
        $indianTime = $dateTimeUTC->format('Y-m-d H:i:s');
        return $indianTime;
    }

    public function ProcessCustomer()
    {

        $this->_SfmcLogger->info("----------------------------ProcessCustomer----------------------------");
        $indianTime = $this->GetCurrentDateTime();

        if ($this->IsModuleEnable()) {

            $_sfmcCustomerBatchModel = $this->_sfmcCustomerBatchFactory->create();

            $_sfmcCustomerBatchModel->addData([
                "first_cust_to_process" => 0,
                "last_cust_to_process" => 0,
                "processed_customers" => 0,
                "success_synced" => 0,
                "failed_synced" => 0,
                "last_updated" => $indianTime,
            ]);
            $_sfmcCustomerBatchModel->save();

            $this->_SfmcLogger->info("Starting to Sync customers.");

            $batch_of = $this->getConfig('sfmc/module/batch_of');
            $this->_SfmcLogger->info("Batch of " . $batch_of);

            $updatedTime = $this->lastUpdateBearerKey();
            $updatedTime = strtotime($updatedTime);
            $currentTimestamp = time();

            $timeDifference = $currentTimestamp - $updatedTime;
            $timeDifferenceInMinutes = $timeDifference / 60;

            $createBearerKeyUpdateAfter = $this->getConfig('sfmc/configuration/bearer_key_update_after');

            if ($timeDifferenceInMinutes > $createBearerKeyUpdateAfter) {
                if ($this->CreateBearerKey()) {
                } else {
                    $this->_SfmcLogger->info("Exiting, Because bearer key was not created.");
                    exit;
                }
            }
            $failed_customer_array = $this->cleanTypeStoreValue();
            $customerCollection = $this->customerCollectionFactory->create()
                ->addAttributeToFilter(
                    [
                        ['attribute' => 'sfmcsync', 'null' => true], // Customers where sfmcsync is not assigned
                        ['attribute' => 'sfmcsync', 'eq' => 0]    // Customers where sfmcsync is not equal to 1
                    ]
                );
            if ($failed_customer_array != false) {
                //echo "<br>adding ".print_r($failed_customer_array);
                $customerCollection->addAttributeToFilter('entity_id', ['nin' => $failed_customer_array]);
                $customerCollection->setPageSize($batch_of);
            } else {
                $customerCollection->setPageSize($batch_of);
            }


            echo "<br>customerCollection: " . $customerCollection->getSize() . '<br>';
            $customerLists = $customerCollection->getItems();

            $i = 1;
            $passed = 1;
            $failed = 1;
            $failedCustomer = '';
            foreach ($customerLists as $customerList) {
                $customerId = $customerList->getId();

                $_sfmcCustomerBatchModel = $this->_sfmcCustomerBatchFactory->create();
                $fetchIdforUpdate = $_sfmcCustomerBatchModel->getCollection()->setOrder('last_updated', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
                    ->setPageSize(1);

                $lastUpdatedRow = $fetchIdforUpdate->getFirstItem();

                // $this->_SfmcLogger->info( "Customer Cron is disable from admin." );

                //    echo "<br>processing: " .$customerList->getId();

                $this->_SfmcLogger->info("processing: " . $customerList->getId() . "with batch ID " . $lastUpdatedRow->getId() . ".");
                if ($i == 1) {
                    $lastUpdatedRow->setData("first_cust_to_process", $customerList->getId());
                    $lastUpdatedRow->setData("last_updated", $indianTime);
                }
                $lastUpdatedRow->setData("last_cust_to_process", $customerList->getId());
                $lastUpdatedRow->setData("last_updated", $indianTime);


                $this->_SfmcLogger->info("Syncing customer " . $customerList->getId());

                if ($this->SyncCustomer($customerList->getId()) == 1) {
                    $this->_SfmcLogger->info("Synced customer " . $customerList->getId());
                    $lastUpdatedRow->setData("success_synced", $passed);

                    if ($this->enableSfmcAttribute($customerList->getId()) != true) {
                        //$failedCustomer .= ','.$customerList->getId();
                        $response = 'not able to enable attribustes';
                        $this->AddInFailedTable($customerList->getId(), $response);
                    }
                    $passed++;
                } else {
                    $this->_SfmcLogger->info("Synced failed for customer " . $customerList->getId());
                    $lastUpdatedRow->setData("failed_synced", $failed);
                    //$this->AddInFailedTable($customerList->getId());
                    $failed++;
                }

                $lastUpdatedRow->setData("processed_customers", $i);
                $lastUpdatedRow->save();
                $i++;
            }
            $this->_SfmcLogger->info("All Customers were synced in current batch.");
        } else {
            $this->_SfmcLogger->info("Module is disable from admin.");
        }
    }

    public function AddInFailedTable($cust_id, $response)
    {
        $CurrentDateTime = $this->GetCurrentDateTime();

        $SfmcCustomerFailed = $this->_SfmcCustomerFailed->create();
        $SfmcCustomerFailed->addData([
            "cust_id" => $cust_id,
            "last_updated" => $CurrentDateTime,
            "response" => $response,
        ]);

        $SfmcCustomerFailed->save();
    }

    public function AddFailedProduct($prd_id, $response)
    {
        $CurrentDateTime = $this->GetCurrentDateTime();

        $SfmcProductFailed = $this->SfmcProductFailedFactory->create();
        $SfmcProductFailed->addData([
            "product_id" => $prd_id,
            "last_updated" => $CurrentDateTime,
            "response" => $response,
        ]);

        $SfmcProductFailed->save();
    }


    public function CreateBearerKey()
    {

        $keys = $this->GetKeys();

        $bearerKey = $keys["bearerKey"];
        $clientId = $keys["clientId"];
        $clientSecret = $keys["clientSecret"];
        $mid = $keys["mid"];
        $bearer_key_create_url = $keys["url"];

        $rawData = '{
                "grant_type": "client_credentials",
                "client_id": "' . $clientId . '",
                "client_secret": "' . $clientSecret . '",
                "account_id": "' . $mid . '"
            }';

        $ch = curl_init($bearer_key_create_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Check for cURL errors
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            return false;
        }
        curl_close($ch);

        $data = json_decode($response, true);
        if (isset($data['access_token'])) {

            $accessToken = $data['access_token'];

            $path = 'sfmc/configuration/bearer_key';
            $this->configWriter->save($path, $accessToken);

            return true;
        } else {
            $this->_SfmcLogger->info("Error in creating bearer Key");
            return false;
        }
        $this->_SfmcLogger->info("Error in creating bearer Key");
        return false;
    }


    public function getCustomerData($customerId)
    {
        $this->_logger->info("Customer ID: $customerId");
        $customer = $this->customerFactory->create()->load($customerId);
        $customerCreatePhoneIndex = $customer->getData();

        $lastOrder = $this->_orderCollection->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addAttributeToSort('created_at', 'DESC')
            ->setPageSize(1) // Ensure only the last order is fetched
            ->getFirstItem();

        if ($lastOrder->getId()) {
            // If last order exists, get its creation date
            $lastPurchaseDate = $lastOrder->getCreatedAt();
            $customerCreatePhoneIndex['last_purchase_date'] = $lastPurchaseDate;
        } else {
            // If no orders found, set default value
            $customerCreatePhoneIndex['last_purchase_date'] = '01/01/2014 00:00:00';
        }

        return $customerCreatePhoneIndex;
        try {
            $this->addressRepository->getById($customer->getDefaultBilling());
            $customerAddress = $this->addressRepository->getById($customer->getDefaultBilling());
            $pincode = $customerAddress->getPostcode();
            $state = $customerAddress->getRegion()->getRegion();
            $city = $customerAddress->getCity();
            $phoneNumber = $customerAddress->getTelephone();
            $customerCreatePhoneIndex['phone_number'] = $phoneNumber;
            $customerCreatePhoneIndex['customer_telephone'] = $phoneNumber;
            $customerCreatePhoneIndex['pincode'] = $pincode;
            $customerCreatePhoneIndex['state'] = $state;
            $customerCreatePhoneIndex['city'] = $city;
            return $customerCreatePhoneIndex;
        } catch (\Exception $e) {
            return $customerCreatePhoneIndex;
            // $customerCreatePhoneIndex['phone_number'] = '0000000000';
            // $response = "phone_number not found";
            // $this->AddInFailedTable($customerId , $response);
        }
    }

    public function SyncCustomer($cust_id)
    {

        $this->_SfmcLogger->info("Called sync customer for " . $cust_id);

        if ($this->ValidateBearerKey() != true) {
        }
        $this->CreateBearerKey();

        if ($this->getCustomerData($cust_id) != false) {
            $rawData = $this->getCustomerData($cust_id);

            $rowData['email'] = $rawData['email'] ?? 'null';
            $rowData['website'] = 'https://www.golfoy.com';
            $rowData['website_id'] = $rawData['website_id'] ?? 'null';
            $rowData['store'] = 'https://www.golfoy.com';
            $rowData['store_id'] = $rawData['store_id'] ?? 'null';
            $rowData['created_in'] = $rawData['created_in'] ?? 'null';
            $rowData['created_at'] = $rawData['created_at'] ?? 'null';
            $rowData['updated_at'] = $rawData['updated_at'] ?? 'null';
            $rowData['firstname'] = substr($rawData['firstname'] ?? 'empty', 0, 100);
            $rowData['lastname'] = substr($rawData['lastname'] ?? 'empty', 0, 100);
            $rowData['suffix'] = $rawData['suffix'] ?? 'null';
            $rowData['group_id'] = $rawData['group_id'] ?? 'null';
            $rowData['gender'] = $rawData['gender'] ?? 'null';
            $rowData['taxvat'] = $rawData['taxvat'] ?? 'null';
            $rowData['failures_num'] = $rawData['failures_num'] ?? 'null';
            $rowData['is_customer_new'] = $rawData['is_customer_new'] ?? 'null';
            $rowData['ac_sync_status'] = $rawData['ac_sync_status'] ?? 'null';
            $rowData['ac_contact_id'] = $rawData['ac_contact_id'] ?? 'null';
            $rowData['dob'] = $rawData['dob'] ?? '';
            $rowData['phone_number'] = preg_replace('/\D/', '', $rawData['phone_number'] ?? '');
            $rowData['customer_telephone'] = preg_replace('/\D/', '', $rawData['customer_telephone'] ?? '');
            $rowData['pincode'] = preg_replace('/\D/', '', $rawData['pincode'] ?? '');
            $rowData['state'] = $rawData['state'] ?? '';
            $rowData['city'] = $rawData['city'] ?? '';
            $rowData['last_purchase_date'] = $rawData['last_purchase_date'] ?? '';
        } else {
            return false;
        }

        //get bearer updated time 
        $bearer_key_updated_at = '';
        $get_current_time = '';


        //check for bearer key first

        $keys = $this->GetKeys();
        $GetCustomerCreateUrl = $this->GetCustomerCreateUrl();

        $bearerKey = $keys["bearerKey"];
        $url = $GetCustomerCreateUrl;


        $rawDatafinal = '   
        [
            {
                "keys": {
                    "ac_customer_id": "' . $cust_id . '"
                },
                "values": {
                    "email":"' . $rawData['email'] . '",
                    "website":"https://www.golfoy.com",
                    "store":"https://www.golfoy.com",
                    "website_id":"1",
                    "store_id":"1",
                    "created_in":"Default",
                    "prefix":"1",
                    "firstname":"' . $rowData['firstname'] . '",
                    "lastname":"' . $rowData['lastname'] . '",
                    "suffix":"1",
                    "group_id":"1",
                    "taxvat":"' . $rowData['taxvat'] . '",
                    "created_at":"' . $rowData['updated_at'] . '",
                    "gender":"' . $rowData['gender'] . '",
                    "disable_auto_group_change":"0",
                    "magesms_customer_marketing":"yes",
                    "is_customer_new":"yes",
                    "updated_at":"' . $rowData['updated_at'] . '",
                    "failures_num":"0",
                    "first_failure":"yes",
                    "lock_expires":"yes",
                    "phone_number":"' . $rowData['phone_number'] . '",
                    "mp_smtp_is_synced":"yes",
                    "customer_telephone":"' . $rowData['phone_number'] . '",
                    "ac_contact_id":"' . $cust_id . '",
                    "tmp_customer_id":"' . $cust_id . '",
                    "ac_sync_status":"true",
                    "dob":"' . $rowData['dob'] . '",
                    "pincode":"' . $rowData['pincode'] . '",
                    "state":"' . $rowData['state'] . '",
                    "city":"' . $rowData['city'] . '",
                    "last_purchase_date":"' . $rowData['last_purchase_date'] . '"
                    }
            }
        ]';
        // echo "<br><br><br>URL: ".$url;
        // echo "<br><br><br>bearerKey: ".$bearerKey;

        // echo "<br><br><br>rawDatafinal: ".$rawDatafinal;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer " . $bearerKey
        ));

        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawDatafinal);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // echo "<br><br><br>statusCode: ";
        // print_r( $statusCode );
        // echo 'customer id '.$cust_id ;
        // echo 'response '.$response ;
        if ($statusCode == 200) {
            return true;
        } else {
            $this->_SfmcLogger->info("Error in sync status code " . $statusCode . " -> " . $cust_id);
            $this->AddInFailedTable($cust_id, $response);
            return false;
        }
    }

    public function getCategoryNameById($categoryIds)
    {
        $categoryName = '';
        try {
            if (empty($categoryIds)) {
                return '';
            } else {
                foreach ($categoryIds as $categoryId) {
                    $category = $this->_categoryFactory->create()->load($categoryId);
                    $categoryName .= $category->getName() . '|';
                }
                return $categoryName;
            }
        } catch (\Exception $e) {
            return 'Category not Selected';
        }
    }

    public function getProductData($prd_id)
    {
        try {
            $product = $this->productRepository->getById($prd_id);
            $attributeSet = $this->attributeSetRepository->get($product->getAttributeSetId());
            $baseUrl = "https://golfoy.com/media/catalog/product";

            // Stock Info
            $qty = 0;
            $isInStock = false;

            if ($product->getTypeId() !== \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                try {
                    $productQty = $this->getSalableQuantityDataBySku->execute($product->getSku());
                    
                    foreach ($productQty as $prdQty) {
                        if ($prdQty['stock_id'] == 2) {
                            $qty = $prdQty['qty'];
                            break;
                        }
                    }

                    $isInStock = $product->isAvailable();

                } catch (\Magento\Framework\Exception\InputException $e) {
                    // Specific error like "Can't check requested quantity..."
                    $qty = 0;
                    $isInStock = false;

                } catch (\Exception $e) {
                    // Any other unexpected error
                    $qty = 0;
                    $isInStock = false;
                }
            }

            $description = $this->sanitizeDescription($product->getDescription());
            $shortDescription = $this->sanitizeDescription($product->getShortDescription());

            return [
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'product_websites' => $product->getProductWebsites(),
                'store_view_code' => $product->getStoreId(),
                'attribute_set_code' => $product->getAttributeSetId(),
                'attributes_set_lable' => $attributeSet->getAttributeSetName(),
                'price' => $product->getPrice(),
                'special_price' => $product->getSpecialPrice(),
                'created_at' => $product->getCreatedAt(),
                'updated_at' => $product->getUpdatedAt(),
                'color' => $product->getAttributeText('color') ?? '',
                'brand' => $product->getAttributeText('brand'),
                'new_arrivals' => $product->getNewarrivals() ?? '',
                'best_seller' => $product->getBestSeller() ?? '',
                'express_shipping' => $product->getAttributeText('express_shipping') ?? '',
                'can_print' => $product->getCanPrint() ?? '',
                'offer_status' => $product->getOfferStatus() ?? '',
                'clearance_sale' => $product->getClearanceSale() ?? '',
                'shoe_sole' => $product->getAttributeText('shoe_sole') ?? '',
                'water_resistance' => $product->getAttributeText('water_resistance') ?? '',
                'shoe_width' => $product->getAttributeText('shoe_width') ?? '',
                'outer_shoe_material' => $product->getAttributeText('outer_shoe_material') ?? '',
                'apparel_material' => $product->getAttributeText('apparel_material') ?? '',
                'player_type' => $product->getAttributeText('player_type') ?? '',
                'glove_material' => $product->getAttributeText('glove_material') ?? '',
                'shoe_size' => $product->getAttributeText('shoe_size') ?? '',
                'shirt_size' => $product->getAttributeText('shirt_size') ?? '',
                'glove_size' => $product->getAttributeText('glove_size') ?? '',
                'golve_hand' => $product->getAttributeText('golve_hand') ?? '',
                'grip_size' => $product->getAttributeText('grip_size') ?? '',
                'grip_type' => $product->getAttributeText('grip_type') ?? '',
                'club_loft' => $product->getAttributeText('club_loft') ?? '',
                'flex' => $product->getAttributeText('flex') ?? '',
                'shaft' => $product->getAttributeText('shaft') ?? '',
                'shaft_weight' => $product->getAttributeText('shaft_weight') ?? '',
                'club_head' => $product->getAttributeText('club_head') ?? '',
                'belt_size' => $product->getAttributeText('belt_size') ?? '',
                'putter_length' => $product->getAttributeText('putter_length') ?? '',
                'bag_type' => $product->getAttributeText('bag_type') ?? '',
                'trolley_structure' => $product->getAttributeText('trolley_structure') ?? '',
                'cover_size' => $product->getAttributeText('cover_size') ?? '',
                'product_type' => $product->getTypeId(),
                'product_online' => $product->getStatus(),
                'tax_class_name' => $product->getTaxClassId(),
                'visibility' => $product->getVisibility(),
                'url_key' => $product->getUrlKey(),
                'meta_title' => $product->getMetaTitle(),
                'meta_keywords' => $product->getMetaKeyword(),
                'meta_description' => $product->getMetaDescription(),
                'gift_message_available' => $product->getGiftMessageAvailable(),
                'country_of_manufacture' => $product->getCountryOfManufacture(),
                'description' => $description,
                'short_description' => $shortDescription,
                'additional_attributes' => $product->getAdditionalAttributes(),
                'qty' => $qty,
                'is_in_stock' => $isInStock,
                'website_id' => $product->getWebsiteIds(),
                'related_skus' => $product->getRelatedSkus(),
                'crosssell_skus' => $product->getCrosssellSkus(),
                'upsell_skus' => $product->getUpsellSkus(),
                'coupon_apply' => $product->getCouponApply(),
                'iron_type' => $product->getAttributeText('iron_type') ?? '',
                'head_construction_iron' => $product->getAttributeText('head_construction_iron') ?? '',
                'head_type_putter' => $product->getAttributeText('head_type_putter') ?? '',
                'face_type_putter' => $product->getAttributeText('face_type_putter') ?? '',
                'driver_forgiveness' => $product->getAttributeText('driver_forgiveness') ?? '',
                'wedge_type' => $product->getAttributeText('wedge_type') ?? '',
                'headcover_type' => $product->getAttributeText('headcover_type') ?? '',
                'season' => $product->getSeason() ?? '',
                'mat_type' => $product->getAttributeText('mat_type') ?? '',
                'construction' => $product->getAttributeText('construction') ?? '',
                'trolley_wheel' => $product->getAttributeText('trolley_wheel') ?? '',
                'waterproof' => $product->getAttributeText('waterproof') ?? '',
                'playerschoice' => $product->getAttributeText('playerschoice') ?? '',
                'freshness' => $product->getAttributeText('freshness') ?? '',
                'base_image' => $baseUrl . $product->getImage(),
                'base_image_label' => $product->getImageLabel(),
                'thumbnail_image' => $baseUrl . $product->getThumbnail(),
                'thumbnail_image_label' => $product->getThumbnailLabel(),
                'additional_images' => $product->getAdditionalImages(),
                'additional_image_labels' => $product->getAdditionalImageLabels(),
                'associated_skus' => $product->getAssociatedSkus(),
                'configurable_variations' => $product->getConfigurableVariations(),
                'configurable_variation_labels' => $product->getConfigurableVariationLabels(),
                'product_inventory' => $product->getProductInventory(),
            ];
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $this->productLogger->info("Product not found: " . $product->getId());
            return false;
        }
    }

    public function sanitizeDescription($text)
    {
        if (!$text) return '';
        $text = stripslashes($text);
        return substr(preg_replace('/\s+/', ' ', strip_tags($text)), 0, 3999);
    }

    public function addOrderToSucceedTable($order_id, $responce)
    {
        try {
            $succeedOrderModel = $this->SfmcOrderSyncSucceedFactory->create();
            $currentTime = $this->GetCurrentDateTime();
            $succeedOrderModel->addData([
                "order_id" => $order_id,
                "response" => $responce,
                "update_at" => $currentTime,
            ]);
            $succeedOrderModel->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function addOrderToFailedTable($order_id, $responce)
    {
        try {
            $failedOrderModel = $this->SfmcOrderFailedFactory->create();
            $currentTime = $this->GetCurrentDateTime();
            $failedOrderModel->addData([
                "order_id" => $order_id,
                "response" => $responce,
                "last_updated" => $currentTime,
            ]);
            $failedOrderModel->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCollectionSyncSucceedOrderId()
    {
        $result = [];
        $sfmcSucceedOrderCollection = $this->SfmcOrderSyncSucceedFactory->create();
        $sfmcSucceedOrderCollection = $sfmcSucceedOrderCollection->getCollection();
        $collectionSize = $sfmcSucceedOrderCollection->getSize();
        if ($collectionSize > 0) {
            $succeedOrderData = $sfmcSucceedOrderCollection->getData();
            foreach ($succeedOrderData as $orderid) {
                $result[] = $orderid["order_id"];
            }
            return $result;
        } else {
            return false;
        }
        return false;
    }

    public function getCollectionSyncFailedOrder()
    {
        $result = [];
        $sfmcFailedOrder = $this->SfmcOrderFailedFactory->create();
        $sfmcFailedOrder = $sfmcFailedOrder->getCollection();
        $collectionSize = $sfmcFailedOrder->getSize();
        if ($collectionSize > 0) {
            $sfmcFailedOrder = $sfmcFailedOrder->getData();
            foreach ($sfmcFailedOrder as $FailedOrder) {
                $result[] = $FailedOrder['order_id'];
            }
            return $result;
        } else {
            return false;
        }
        return false;
    }

    public function SyncTrackingOrder($trackingData)
    {
        $this->CreateBearerKey();
        $keys = $this->GetKeys();
        $url = $this->GetTrackingOrderCreateUrl();
        $bearerKey = $keys["bearerKey"];
        $rawDatafinal = '[
                            {
                                "keys": {
                                    "order_no": "' . $trackingData['order_no'] . '"
                                },
                                "values": {
                                    "utm_source":"' . $trackingData['utm_source'] . '",
                                    "utm_campaign":"' . $trackingData['utm_campaign'] . '",
                                    "utm_term":"' . $trackingData['utm_term'] . '",
                                    "sfmc_id":"' . $trackingData['sfmc_id'] . '",
                                    "ordered_Items":"' . str_replace('"', "'", $trackingData['ordered_Items']) . '",
                                    "order_value":"' . $trackingData['order_value'] . '",
                                    "utm_medium":"' . $trackingData['utm_medium'] . '"
                                    }
                            }
                        ]';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer " . $bearerKey
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawDatafinal);
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // print_r( $statusCode );
        // print_r($response);die;
        if ($statusCode == 200) {
            $this->orderLogger->info("Tracking order Sync status code " . $statusCode . " -> " . $trackingData['order_no']);
            return true;
        } else {
            $this->orderLogger->info("Tracking order Error in sync status code " . $statusCode . " -> " . $trackingData['order_no']);
            return false;
        }
    }

    public function getOrderedItems($order)
    {
        $baseUrl = 'golfoy.com/';
        $productData = [];

        $orderItems = $order->getAllVisibleItems();
        foreach ($orderItems as $item) {
            try {
                $product = $item->getProduct();
                if ($product) {
                    $productName = str_replace("'", "", $product->getName());
                    $productSku = $product->getSku();
                    $productPrice = $product->getFinalPrice();
                    $productId = $product->getId();
                    $productImageUrl = $baseUrl . 'media/catalog/product' . $product->getImage();
                    $productUrl = $product->getProductUrl();
                    $attributeSetId = $product->getAttributeSetId();
                } else {
                    throw new \Magento\Framework\Exception\NoSuchEntityException(__('Product not found'));
                }

                $qty = $item->getQtyOrdered();
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $productName = 'Not Found';
                $productImageUrl = 'Not Found';
                $productPrice = 'Not Found';
                $productUrl = 'Not Found';
                $qty = 'Not Found';
                $productSku = 'Not Found';
                $attributeSetId = 'Not Found';
            }

            $productData[] = [
                'name' => $productName,
                'image' => $productImageUrl,
                'price' => $productPrice,
                'url' => $productUrl,
                'qty' => $qty,
                'sku' => $productSku,
                'attribute_set_id' => $attributeSetId,
            ];
        }

        return json_encode($productData);
    }

    public function getOrderData($order_id)
    {
        $rowOrderData = [];
        $shipToName = 'Not found';
        $shippingAddress = 'Not found';
        $shippingCity = 'Not found';
        $shippingStreet = 'Not found';
        $customerPhoneNumber = 'Not found';

        try {
            $order = $this->orderFactory->create()->load($order_id);
            $orderedItems = $this->getOrderedItems($order);
            $orderData = $order->getData();
            try {
                $groupName = $this->groupRepository->getById($orderData['customer_group_id'])->getCode();
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $groupName = 'Not found';
            }
            if ($order->getShippingAddress()) {
                $shipToName = $order->getShippingAddress()->getName();
                $shippingAddress = $order->getShippingAddress()->getStreetLine(1);
                $shippingCity = $order->getShippingAddress()->getCity();
                $shippingStreet = $order->getShippingAddress()->getStreetFull();
            }
            $rowOrderData['id']                            = $order_id;
            $rowOrderData['purchase_point']                = 'golfoy.com';
            $rowOrderData['purchase_date']                 = $orderData['created_at'];
            $rowOrderData['bill_to_name']                  = $order->getBillingAddress()->getName();
            $rowOrderData['ship_to_name']                  = $shipToName;
            $rowOrderData['grand_total_base']              = $order->getBaseGrandTotal();
            $rowOrderData['grand_total_purchased']         = $order->getBaseGrandTotal();
            $rowOrderData['status']                        = $order->getStatus();
            $rowOrderData['billing_address']               = $order->getBillingAddress()->getStreetLine(1);
            $rowOrderData['billing_city']                  = $order->getBillingAddress()->getCity();
            $rowOrderData['shipping_address']              = $shippingAddress;
            $rowOrderData['shipping_city']                 = $shippingCity;
            $rowOrderData['shipping_information']          = $shippingStreet;
            $rowOrderData['customer_email']                = $order->getCustomerEmail();
            $rowOrderData['customer_group']                = $groupName;
            $rowOrderData['subtotal']                      = $order->getSubtotal();
            $rowOrderData['shipping_and_handling']         = $order->getShippingAmount();
            $rowOrderData['customer_name']                 = $order->getCustomerName();
            $rowOrderData['payment_method']                = $order->getPayment()->getMethod();
            $rowOrderData['total_refunded']                = $order->getTotalRefunded();
            $rowOrderData['allocated_sources']             = $order->getAllocatedSources();
            $rowOrderData['pickup_location_code']          = $orderData['location_pickup_id'];
            $rowOrderData['active_campaign_sync_status']   =  $order->getActiveCampaignSyncStatus();
            $rowOrderData['reward_earn']                   = $orderData['mp_reward_earn'];
            $rowOrderData['reward_spent']                  = $orderData['mp_reward_spent'];
            $rowOrderData['fulfill_online']                = $orderData['pos_fulfill_online'];
            $rowOrderData['location']                      = $orderData['location_pickup_id'];
            $rowOrderData['staff']                         = $orderData['pos_staff_name'];
            $rowOrderData['payment_method_pos']            = 'Yes';
            $rowOrderData['reference_number']              = $orderData['payment_reference_number'];
            $rowOrderData['customer_phone']                = substr(preg_replace('/\D+/', '', $order->getBillingAddress()->getTelephone()), -10);
            $rowOrderData['order_items']                   = str_replace('"', "'", $orderedItems);

            return $rowOrderData;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
    }

    public function ProcessProduct()
    {

        $this->productLogger->info("----------------------------ProcessProduct----------------------------");
        $indianTime = $this->GetCurrentDateTime();

        if ($this->IsModuleEnable()) {

            $_sfmcProductBatchModel = $this->SfmcProductBatchFactory->create();

            $_sfmcProductBatchModel->addData([
                "first_product_to_process" => 0,
                "last_product_to_process" => 0,
                "processed_product" => 0,
                "success_synced" => 0,
                "failed_synced" => 0,
                "last_updated" => $indianTime,
            ]);
            $_sfmcProductBatchModel->save();

            $this->productLogger->info("Starting to Sync product.");

            $batch_of = $this->getConfig('sfmc/module_product/batch_of');
            $this->productLogger->info("Batch of " . $batch_of);

            $this->CreateBearerKey();

            $failed_product_array = $this->failedProductCollection();
            $productCollection = $this->productCollectionFactory->create()
                ->addAttributeToFilter(
                    [
                        ['attribute' => 'sfmcsyncproduct', 'null' => true],
                        ['attribute' => 'sfmcsyncproduct', 'eq' => 0]
                    ]
                )->addAttributeToSort('entity_id', 'ASC');
            if ($failed_product_array != false) {
                //echo "<br>adding ".print_r($failed_customer_array);
                $productCollection->addAttributeToFilter('entity_id', ['nin' => $failed_product_array]);
                $productCollection->setPageSize($batch_of);
            } else {
                $productCollection->setPageSize($batch_of);
            }


            echo "<br>productCollection: " . $productCollection->getSize() . '<br>';
            echo "<br>batch of: " . $batch_of . '<br>';
            $productLists = $productCollection->getItems();

            $i = 1;
            $passed = 1;
            $failed = 1;
            $failedproduct = '';
            foreach ($productLists as $productList) {
                echo $productList->getId() . "\n";
                $_sfmcProductBatchModel = $this->SfmcProductBatchFactory->create();
                $fetchIdforUpdate = $_sfmcProductBatchModel->getCollection()->setOrder('last_updated', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
                    ->setPageSize(1);

                $lastUpdatedRow = $fetchIdforUpdate->getFirstItem();

                $this->productLogger->info("processing product_id: " . $productList->getId() . "with batch ID " . $lastUpdatedRow->getId() . ".");
                if ($i == 1) {
                    $lastUpdatedRow->setData("first_product_to_process", $productList->getId());
                    $lastUpdatedRow->setData("last_updated", $indianTime);
                }
                $lastUpdatedRow->setData("last_product_to_process", $productList->getId());
                $lastUpdatedRow->setData("last_updated", $indianTime);


                $this->productLogger->info("Syncing product_id " . $productList->getId());
                // $this->SyncProduct($productList);die;
                if ($this->SyncProduct($productList->getId()) == true) {
                    $this->productLogger->info("Synced product_id " . $productList->getId());
                    $lastUpdatedRow->setData("success_synced", $passed);

                    if ($this->enableSfmcProductAttribute($productList) != true) {
                        //$failedCustomer .= ','.$productList->getId();
                        $response = 'failed to enable attributes but sync success';

                        $this->AddFailedProduct($productList->getId(), $response);
                    }
                    $passed++;
                } else {
                    $response = 'Something went wrong with this productGetData';
                    $this->AddFailedProduct($productList->getId(), $response);
                    $this->productLogger->info("Synced failed for product_id " . $productList->getId());
                    $lastUpdatedRow->setData("failed_synced", $failed);
                    $failed++;
                }
                $lastUpdatedRow->setData("processed_product", $i);
                $lastUpdatedRow->save();
                $i++;
            }
            $this->productLogger->info("All product were synced in current batch.");
        } else {
            $this->productLogger->info("Module is disable from admin.");
        }
    }


    public function SyncProduct($product)
    {
        try {
            $this->CreateBearerKey();
            $this->productLogger->info("Called sync product for " . $product);

            $rawData = $this->getProductData($product);
            if ($rawData == false) {
                return false;
            }
            
            $keys = $this->GetKeys();
            $url = $this->newGetProductCreateUrl();

            $bearerKey = $keys["bearerKey"];
            // $url = $GetProductCreateUrl;
            $url = "https://mc43swm62-c7zw8--y8rwzbh4gdm.rest.marketingcloudapis.com/hub/v1/dataevents/key:FF465323-F1CA-45CF-B889-E987E62F27D9/rowset";

            $rawDatafinal = '[
            {
                "keys": {
                    "sku": "' . $rawData['sku'] . '"
                },
                "values": {
                    "store_view_code":"' . $rawData['store_view_code'] . '",
                    "attribute_set_code":"' . $rawData['attribute_set_code'] . '",
                    "attributes_set_lable":"' . $rawData['attributes_set_lable'] . '",
                    "color":"' . $rawData['color'] . '",
                    "brand":"' . $rawData['brand'] . '",
                    "newarrivals":"' . $rawData['new_arrivals'] . '",
                    "best_seller":"' . $rawData['best_seller'] . '",
                    "express_shipping":"' . $rawData['express_shipping'] . '",
                    "can_print":"' . $rawData['can_print'] . '",
                    "offer_status":"' . $rawData['offer_status'] . '",
                    "clearance_sale":"' . $rawData['clearance_sale'] . '",
                    "shoe_sole":"' . $rawData['shoe_sole'] . '",
                    "water_resistance":"' . $rawData['water_resistance'] . '",
                    "shoe_width":"' . $rawData['shoe_width'] . '",
                    "outer_shoe_material":"' . $rawData['outer_shoe_material'] . '",
                    "apparel_material":"' . $rawData['apparel_material'] . '",
                    "player_type":"' . $rawData['player_type'] . '",
                    "glove_material":"' . $rawData['glove_material'] . '",
                    "shoe_size":"' . $rawData['shoe_size'] . '",
                    "shirt_size":"' . $rawData['shirt_size'] . '",
                    "glove_size":"' . $rawData['glove_size'] . '",
                    "golve_hand":"' . $rawData['golve_hand'] . '",
                    "grip_size":"' . $rawData['grip_size'] . '",
                    "grip_type":"' . $rawData['grip_type'] . '",
                    "club_loft":"' . $rawData['club_loft'] . '",
                    "flex":"' . $rawData['flex'] . '",
                    "shaft":"' . $rawData['shaft'] . '",
                    "shaft_weight":"' . $rawData['shaft_weight'] . '",
                    "club_head":"' . $rawData['club_head'] . '",
                    "belt_size":"' . $rawData['belt_size'] . '",
                    "putter_length":"' . $rawData['putter_length'] . '",
                    "bag_type":"' . $rawData['bag_type'] . '",
                    "trolley_structure":"' . $rawData['trolley_structure'] . '",
                    "product_type":"' . $rawData['product_type'] . '",
                    "product_online":"' . $rawData['product_online'] . '",
                    "tax_class_name":"' . $rawData['tax_class_name'] . '",
                    "visibility":"' . $rawData['visibility'] . '",
                    "url_key":"' . $rawData['url_key'] . '",
                    "meta_title":"' . $rawData['meta_title'] . '",
                    "meta_keywords":"' . $rawData['meta_keywords'] . '",
                    "meta_description":"' . $rawData['meta_description'] . '",
                    "gift_message_available":"' . $rawData['gift_message_available'] . '",
                    "country_of_manufacture":"' . $rawData['country_of_manufacture'] . '",
                    "description":"' . str_replace('"','', $rawData['description']) . '",
                    "short_description":"' . $rawData['short_description'] . '",
                    "additional_attributes":"' . $rawData['additional_attributes'] . '",
                    "qty":"' . $rawData['qty'] . '",
                    "is_in_stock":"' . $rawData['is_in_stock'] . '",
                    "website_id":"' . $rawData['website_id'][0] . '",
                    "related_skus":"' . $rawData['related_skus'] . '",
                    "crosssell_skus":"' . $rawData['crosssell_skus'] . '",
                    "upsell_skus":"' . $rawData['upsell_skus'] . '",
                    "coupon_apply":"' . $rawData['coupon_apply'] . '",
                    "iron_type":"' . $rawData['iron_type'] . '",
                    "head_construction_iron":"' . $rawData['head_construction_iron'] . '",
                    "head_type_putter":"' . $rawData['head_type_putter'] . '",
                    "face_type_putter":"' . $rawData['face_type_putter'] . '",
                    "driver_forgiveness":"' . $rawData['driver_forgiveness'] . '",
                    "wedge_type":"' . $rawData['wedge_type'] . '",
                    "season":"",
                    "mat_type":"' . $rawData['mat_type'] . '",
                    "construction":"' . $rawData['construction'] . '",
                    "trolley_wheel":"' . $rawData['trolley_wheel'] . '",
                    "waterproof":"' . $rawData['waterproof'] . '",
                    "playerschoice":"' . $rawData['playerschoice'] . '",
                    "freshness":"' . $rawData['freshness'] . '",
                    "base_image":"' . $rawData['base_image'] . '",
                    "base_image_label":"' . trim($rawData['base_image_label']) . '",
                    "thumbnail_image":"' . $rawData['thumbnail_image'] . '",
                    "thumbnail_image_label":"' . trim($rawData['thumbnail_image_label']) . '",
                    "additional_images":"' . $rawData['additional_images'] . '",
                    "additional_image_labels":"' . $rawData['additional_image_labels'] . '",
                    "associated_skus":"' . $rawData['associated_skus'] . '",
                    "configurable_variations":"' . $rawData['configurable_variations'] . '",
                    "configurable_variation_labels":"' . $rawData['configurable_variation_labels'] . '",
                    "product_inventory":"' . $rawData['product_inventory'] . '",
                    "product_websites":"' . $rawData['product_websites'] . '",
                    "name":"' . $rawData['name'] . '",
                    "price":"' . (int)$rawData['price'] . '",
                    "special_price":"' . (int)$rawData['special_price'] . '",
                    "created_at":"' . $rawData['created_at'] . '",
                    "updated_at":"' . $rawData['updated_at'] . '"
                }
            }
        ]';

            // echo "<br><br><br>URL: ".$url;
            // echo "<br><br><br>bearerKey: ".$bearerKey;

            echo "\n rawDatafinal: ".$rawDatafinal;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Authorization: Bearer " . $bearerKey
            ));

            curl_setopt($ch, CURLOPT_POSTFIELDS, $rawDatafinal);

            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            echo "\nstatusCode: ";
            print_r($statusCode);
            echo "\n";
            echo "\n response : \n";
            print_r($response);
            return true;
            if ($statusCode == 200) {
                return true;
            } else {
                $this->productLogger->info("Error in sync status code " . $statusCode . " -> " . $product->getId());
                $this->AddFailedProduct($product->getId(), $response);
                return false;
                // $resposeData = json_decode($response);
                // $productIdFound = $resposeData[0]->keys->sku;
                // if($productIdFound > 0){
                //     return true;
                // }else{
                //     $this->productLogger->info( "Error in sync [ product not found ]".$product );
                //     $this->AddFailedProduct($product , $response);
                //     return false;
                // }
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function ProcessOrder()
    {
        $this->orderLogger->info("----------------------------ProcessOrder----------------------------");
        $indianTime = $this->GetCurrentDateTime();
        if ($this->IsModuleEnable()) {
            $_sfmcOrderBatchModel = $this->SfmcOrderBatchFactory->create();
            $_sfmcOrderBatchModel->addData([
                "first_order_to_process" => 0,
                "last_order_to_process" => 0,
                "processed_order" => 0,
                "success_synced" => 0,
                "failed_synced" => 0,
                "last_updated" => $indianTime,
            ]);
            $_sfmcOrderBatchModel->save();
            $this->orderLogger->info("Starting to Sync order.");
            $batch_of = $this->getConfig('sfmc/module_order/batch_of');
            $this->orderLogger->info("Batch of " . $batch_of);
            if ($this->ValidateBearerKey() != true) {
                $this->CreateBearerKey();
            }
            $failed_order_array = $this->getCollectionSyncFailedOrder();
            $succed_order_array = $this->getCollectionSyncSucceedOrderId();
            $orderCollection = $this->orderFactory->create()->getCollection();
            $orderCollection = $orderCollection->addAttributeToFilter('store_id', ['eq' => 1])->addAttributeToFilter('pos_id', ['null' => true]);
            if ($failed_order_array != false) {
                $orderCollection->addAttributeToFilter('entity_id', ['nin' => $failed_order_array]);
                $orderCollection->addAttributeToFilter('entity_id', ['nin' => $succed_order_array]);
                $orderCollection->setPageSize($batch_of);
            } else {
                $orderCollection->setPageSize($batch_of);
            }
            echo "orderCollection: " . $orderCollection->getSize() . '';
            echo "batch of: " . $batch_of . '';
            $orderLists = $orderCollection->getItems();

            $i = 1;
            $passed = 1;
            $failed = 1;
            $failedproduct = '';
            foreach ($orderLists as $orderList) {
                echo "\n".$orderList->getId() . '_';
                $_sfmcOrderBatchModel = $this->SfmcOrderBatchFactory->create();
                $fetchIdforUpdate = $_sfmcOrderBatchModel->getCollection()->setOrder('last_updated', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
                    ->setPageSize(1);

                $lastUpdatedRow = $fetchIdforUpdate->getFirstItem();

                $this->orderLogger->info("processing order_id: " . $orderList->getId() . "with batch ID " . $lastUpdatedRow->getId() . ".");
                if ($i == 1) {
                    $lastUpdatedRow->setData("first_order_to_process", $orderList->getId());
                    $lastUpdatedRow->setData("last_updated", $indianTime);
                }
                $lastUpdatedRow->setData("last_order_to_process", $orderList->getId());
                $lastUpdatedRow->setData("last_updated", $indianTime);


                $this->orderLogger->info("Syncing order_id " . $orderList->getId());

                if ($this->SyncOrder($orderList->getId()) == true) {
                    $this->orderLogger->info("Synced order_id " . $orderList->getId());
                    $lastUpdatedRow->setData("success_synced", $passed);
                    $DummyResponse = '';
                    if ($this->addOrderToSucceedTable($orderList->getId(), $DummyResponse) != true) {
                        //$failedCustomer .= ','.$orderList->getId();
                        $response = '';
                        $this->addOrderToFailedTable($orderList->getId(), $response);
                    }
                    $passed++;
                } else {
                    $this->orderLogger->info("Synced failed for order_id " . $orderList->getId());
                    $lastUpdatedRow->setData("failed_synced", $failed);
                    // $response = 'sync falied';
                    // $this->addOrderToFailedTable($orderList->getId() , $response);
                    $failed++;
                }

                $lastUpdatedRow->setData("processed_order", $i);
                $lastUpdatedRow->save();
                $i++;
            }

            $this->orderLogger->info("All product were synced in current batch.");
        } else {
            $this->orderLogger->info("Module is disable from admin.");
        }
    }

    public function SyncOrder($order_id)
    {
        date_default_timezone_set('Asia/Kolkata');
        $this->orderLogger->info("Called sync order for " . $order_id);
        if ($this->ValidateBearerKey() != true) {
            $this->CreateBearerKey();
        }
        if ($this->getOrderData($order_id) != false) {
            $rowOrderData = $this->getOrderData($order_id);
            $date = new \DateTime($rowOrderData['purchase_date'], new \DateTimeZone('UTC'));
            $date->setTimezone(new \DateTimeZone('Asia/Kolkata'));
            $purchaseDate = $date->format('Y-m-d H:i:s');
        } else {
            return false;
        }
        $keys = $this->GetKeys();
        $GetOrderCreateUrl = $this->GetOrderCreateUrl();
        $bearerKey = $keys["bearerKey"];
        $url = $GetOrderCreateUrl;
        $rawDatafinal = '[
                        {
                            "keys": {
                                "ID": "' . $order_id . '"
                            },
                            "values": {
                                "Purchase Point":"' . $rowOrderData['purchase_point'] . '",
                                "Purchase Date":"' . $purchaseDate . '",
                                "Bill-to Name":"' . $rowOrderData['bill_to_name'] . '",
                                "Ship-to Name":"' . $rowOrderData['ship_to_name'] . '",
                                "Grand Total Base":"' . $rowOrderData['grand_total_base'] . '",
                                "Grand Total Purchased":"' . $rowOrderData['grand_total_purchased'] . '",
                                "Status":"' . $rowOrderData['status'] . '",
                                "Billing Address":"' . $rowOrderData['billing_city'] . '",
                                "Shipping Address":"' . $rowOrderData['shipping_city'] . '",
                                "Shipping Information":"' . $rowOrderData['shipping_information'] . '",
                                "Customer Email":"' . $rowOrderData['customer_email'] . '",
                                "Customer Group":"' . $rowOrderData['customer_group'] . '",
                                "Subtotal":"' . $rowOrderData['subtotal'] . '",
                                "Shipping and Handling":"' . $rowOrderData['shipping_and_handling'] . '",
                                "Customer Name":"' . $rowOrderData['customer_name'] . '",
                                "Payment Method":"' . $rowOrderData['payment_method'] . '",
                                "Total Refunded":"' . $rowOrderData['total_refunded'] . '",
                                "Allocated sources":"' . $rowOrderData['allocated_sources'] . '",
                                "Pickup Location Code":"' . $rowOrderData['pickup_location_code'] . '",
                                "ActiveCampaign Sync Status":"' . $rowOrderData['active_campaign_sync_status'] . '",
                                "Reward Earn":"' . $rowOrderData['reward_earn'] . '",
                                "Reward Spent":"' . $rowOrderData['reward_spent'] . '",
                                "Fulfill Online":"' . $rowOrderData['fulfill_online'] . '",
                                "Location":"' . $rowOrderData['location'] . '",
                                "Staff":"' . $rowOrderData['staff'] . '",
                                "Payment Method POS":"' . $rowOrderData['payment_method_pos'] . '",
                                "Reference Number":"' . $rowOrderData['reference_number'] . '",
                                "Tracking URL":"https://www.golfoy.com",
                                "Customer Phone":"' . $rowOrderData['customer_phone'] . '",
                                "Order Items":"' . json_decode($rowOrderData['order_items'], true) . '"
                                }
                        }
                    ]';
        // echo "<br><br><br>URL: ".$url;
        // echo "<br><br><br>bearerKey: ".$bearerKey;
        // echo "<br><br><br>rawDatafinal: ".$rawDatafinal;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer " . $bearerKey
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawDatafinal);
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        echo "<br><br><br>statusCode: ";
        print_r($statusCode);
        // echo "<br><br><br>response: ";
        // print_r($response);
        if ($statusCode == 200) {
            return true;
        } else {
            $this->orderLogger->info("Error in sync status code " . $statusCode . " -> " . $order_id);
            $this->addOrderToFailedTable($order_id, $response);
            return false;
            // $resposeData = json_decode($response);
            // $orderIdFound = $resposeData[0]->keys->ac_order_id;
            // if($orderIdFound > 0){
            //     return true;
            // }else{
            //     $this->orderLogger->info( "Error in sync [ order not found ]".$order_id );
            //     $this->addOrderToFailedTable($order_id , $response);
            //     return false;
            // }
        }
    }

    public function insertCouponInTable($couponCode, $status, $response)
    {
        $indianTime = $this->GetCurrentDateTime();

        $sfmcCouponModel = $this->SfmcCouponSync->create();
        $sfmcCouponModel->addData([
            "coupon_code" => $couponCode,
            "coupon_status" => $status,
            "response" => $response,
            "last_update" => $indianTime
        ]);
        $sfmcCouponModel->save();
    }

    public function syncCouponList($couponTrue, $couponFalse)
    {
        if ($couponTrue['0'] > 0) {
            foreach ($couponTrue as $trueCoupon) {
                $response = 'null';
                $url = $this->GetCouponCreateUrl();

                if ($this->ValidateBearerKey() != true) {
                    $this->CreateBearerKey();
                }

                $keys = $this->GetKeys();
                $bearerKey = $keys["bearerKey"];
                $rawDatafinal = '[
                                    {
                                        "keys": {
                                            "Coupon_code": "' . $trueCoupon . '"
                                        },
                                        "values": {
                                            "isClaimed":"True"
                                
                                        }
                                    }
                                ]';
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $bearerKey
                ));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $rawDatafinal);
                $response = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $response = 'statusCode: ' . $statusCode . '->' . $response;
                if ($statusCode == 200) {
                    $this->_SfmcLogger->info("Sync sucess status code for coupon True -> " . $trueCoupon);
                    $this->insertCouponInTable($trueCoupon, 'True', $response);
                    return true;
                } else {
                    $this->_SfmcLogger->info("Sync failed status code for coupon True -> " . $trueCoupon);
                    $this->insertCouponInTable($trueCoupon, 'True', $response);
                    return false;
                }
            }
        }

        if ($couponFalse['0'] > 0) {
            foreach ($couponFalse as $falseCoupon) {
                $response = 'null';
                $url = $this->GetCouponCreateUrl();

                if ($this->ValidateBearerKey() != true) {
                    $this->CreateBearerKey();
                }

                $keys = $this->GetKeys();
                $bearerKey = $keys["bearerKey"];
                $rawDatafinal = '[
                                    {
                                        "keys": {
                                            "Coupon_code": "' . $falseCoupon . '"
                                        },
                                        "values": {
                                            "isClaimed":"False"
                                
                                        }
                                    }
                                ]';
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $bearerKey
                ));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $rawDatafinal);
                $response = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $response = 'statusCode: ' . $statusCode . '->' . $response;
                if ($statusCode == 200) {
                    $this->_SfmcLogger->info("Sync sucess status code for coupon FALSE -> " .  $falseCoupon);
                    $this->insertCouponInTable($falseCoupon, 'FALSE', $response);
                    return true;
                } else {
                    $this->_SfmcLogger->info("Sync failed status code for coupon FALSE -> " .  $falseCoupon);
                    $this->insertCouponInTable($falseCoupon, 'FALSE', $response);
                    return false;
                }
            }
        }
        return true;
    }

    public function getAbandonedCartsData()
    {
        $batch_of = $this->getConfig("sfmc/module_abandoned/batch_of");
        $quoteCollection = $this->customQuoteCollectionFactory->create();
        $quoteCollection->addFieldToFilter('is_active', 1);
        $quoteCollection->addFieldToFilter('items_count', ['neq' => 0]);
        $quoteCollection->addFieldToFilter('customer_id', ['notnull' => true]);
        $quoteCollection->setOrder('updated_at', 'ASC');
        // $quoteCollection->setPageSize(10);
        $abandonedCarts = $quoteCollection->getData();
        return $abandonedCarts;
    }

    public function getCustomerAddress($customerId)
    {
        $customer = $this->customerFactory->create()->load($customerId);
        try {
            $customerAddress = $this->addressRepository->getById($customer->getDefaultShipping());
            $customerAddress = $customerAddress->getCity();
            return $customerAddress;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getProductsFromAbandonedCart($quoteId)
    {
        $productSKU = '';
        //$quoteId = '225337';
        $baseUrl = $this->getBaseUrl();
        try {
            $quote = $this->quoteRepository->get($quoteId);
            $lastUpdateData = $quote->setPageSize(1)->getData();
            // print_r($lastUpdateData);die;
            // $lastUpdate = $lastUpdateData['0']['updated_at'];
            // $customerFirstName = $lastUpdateData['0']['customer_firstname'];
            // $customerLastName = $lastUpdateData['0']['customer_lastname'];
            // $lastCouponCode = $lastUpdateData['0']['coupon_code'];
            // $lastGiftCard = $lastUpdateData['0']['gift_voucher_gift_codes'];
            // $lastCustomerEmail = $lastUpdateData['0']['customer_email'];

            $lastUpdate = $quote->getUpdatedAt();
            $customerFirstName = $quote->getCustomerFirstname();
            $customerLastName = $quote->getCustomerLastname();
            $lastCouponCode = $quote->getCouponCode();
            $lastGiftCard = $quote->getGiftVoucherGiftCodes();
            $lastCustomerEmail = $quote->getCustomerEmail();

            $customerId = $quote->getCustomerId();
            $items = $quote->getAllVisibleItems();
            $productData = [];
            $customerAddress = $this->getCustomerAddress($customerId);
            $customerNumber = $this->getCustomerData($customerId);

            foreach ($items as $item) {
                $qty = $item->getQty();
                $productName = str_replace("'", "", $item->getProduct()->getName());
                $productSku = $item->getProduct()->getSku();
                $productPrice = $item->getProduct()->getFinalPrice();
                $productId = $item->getProduct()->getId();
                $product = $this->productRepository->getById($productId);
                $productImageUrl = $product->getImage();
                $productImageUrl = $baseUrl . 'media/catalog/product' . $productImageUrl;
                $productUrl = $product->getProductUrl();
                $productSKU .= '||' . $productSku;
                $productData[] = [
                    'name' => $productName,
                    'image' => $productImageUrl,
                    'price' => $productPrice,
                    'url' => $productUrl,
                    'qty' => $qty,
                    'sku' => $productSku,
                ];
            }
            $productData = json_encode($productData);
            $createCartData['product_data'] =  $productData;
            $createCartData['customer_id'] = $customerId;
            $createCartData['last_updated'] = $lastUpdate;
            $createCartData['address'] = $customerAddress;
            $createCartData['email'] = $lastCustomerEmail;
            $createCartData['gift_card'] = $lastGiftCard;
            $createCartData['discount_coupon'] =  $lastCouponCode;
            $createCartData['telephone'] =  $customerNumber['phone_number'] ?? '';
            $createCartData['first_name'] =  $customerFirstName;
            $createCartData['last_name'] =  $customerLastName;
            $createCartData['sku'] =  $productSKU;

            return $createCartData;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isAvailableQuoteId($quote_id)
    {
        $SfmcAbandonedCartModel = $this->SfmcAbandonedCartFactory->create();
        $SfmcAbandonedCartCollection = $SfmcAbandonedCartModel->getCollection();
        $SfmcAbandonedCartCollection->addFieldToFilter('quote_id', $quote_id);
        if ($SfmcAbandonedCartCollection->getSize() > 0) {
            return $SfmcAbandonedCartCollection->getFirstItem()->getData('updated_at');
        } else {
            return false;
        }
        return false;
    }

    public function getCustomerIdByQuote($quoteid)
    {
        $quote = $this->quoteRepository->get($quoteid);
        $lastUpdateData = $quote->getCollection()->setOrder('updated_at', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->setPageSize(1)->getData();
        return $lastUpdateData['0']['updated_at'];
    }

    public function getCustomerLastUpdate($quoteid)
    {
        $quote = $this->quoteRepository->get($quoteid);
        $lastUpdateData = $quote->getCollection()->setOrder('updated_at', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->setPageSize(1)->getData();
        return $customerId = $quote->getCustomerId();
    }


    public function insertAbandonedCartTable($quote_id, $customer_id, $product_detiails, $response, $lastUpdateData)
    {
        //$indianTime = $this->GetCurrentDateTime(); 
        $isExitValue = $this->isAvailableQuoteId($quote_id);
        if ($isExitValue) {
            $this->updateAbandonedCartTable($quote_id, $product_detiails, $response, $lastUpdateData);
        } else {
            $SfmcAbandonedCartModel = $this->SfmcAbandonedCartFactory->create();
            $SfmcAbandonedCartModel->addData([
                "quote_id" => $quote_id,
                "customer_id" => $customer_id,
                "product_detiails" => $product_detiails,
                "response" => $response,
                "updated_at" => $lastUpdateData,
            ]);
            $SfmcAbandonedCartModel->save();
        }
    }

    public function updateAbandonedCartTable($quote_id, $product_detiails, $response, $lastUpdateData)
    {
        $SfmcAbandonedCartModel = $this->SfmcAbandonedCartFactory->create();
        $SfmcAbandonedCartModel->load($quote_id, 'quote_id');
        if ($SfmcAbandonedCartModel->getId()) {
            $currentProductDetails = $SfmcAbandonedCartModel->getData();
            $updatedProductDetails = $currentProductDetails['product_detiails'] . ' || ' . $product_detiails;
            $SfmcAbandonedCartModel->setData('product_detiails', $updatedProductDetails);
            $SfmcAbandonedCartModel->setData('response', $response);
            $SfmcAbandonedCartModel->setData('updated_at', $lastUpdateData);
            $SfmcAbandonedCartModel->save();
        }
    }

    public function ProcessAbandonedCart()
    {
        $this->cartLogger->info('Abandoned cart running time  ' . $this->GetCurrentDateTime());
        $abandonedColletion = $this->getAbandonedCartsData();
        $currentDate = date('Y-m-d');
        $i = 1;
        $SfmcAbandonedCouponModel = $this->SfmcAbandonedCouponFactory->create();
        $SfmcAbandonedCouponModel->addData(
            [
                'last_update'              => $currentDate,
                'total_abandoned_sync'     => 0,
                'abandoned_status'         => false
            ]
        );
        $SfmcAbandonedCouponModel->save();
        foreach ($abandonedColletion as $abandonedCollect) {
            $QuoteId = $abandonedCollect['entity_id'];
            //$this->syncAbandonedCart($QuoteId);
            $isRequiredSync = $this->isCartUpdated($QuoteId);

            if ($isRequiredSync !== false) {
                echo 'User updated Cart ->' . $QuoteId;
                echo "\n we are Syncing Abandoned Cart Wait...";
                if ($this->syncAbandonedCart($QuoteId)) {

                    $SfmcAbandonedCouponModel = $this->SfmcAbandonedCouponFactory->create()->getCollection();

                    if ($SfmcAbandonedCouponModel->getSize() > 0) {
                        $SfmcAbandonedCouponModelToUpdate = $SfmcAbandonedCouponModel->addFieldToFilter('last_update', $currentDate);
                        if (count($SfmcAbandonedCouponModelToUpdate) > 0) {
                            foreach ($SfmcAbandonedCouponModel as $Data) {
                                if ($Data['last_update'] == $currentDate) {
                                    $SfmcAbandonedCouponModelToUpdate = $this->SfmcAbandonedCouponFactory->create()->load($Data['id']);

                                    $newTotalSync = $Data['total_abandoned_sync'];
                                    $newTotalSync++;
                                    $SfmcAbandonedCouponModelToUpdate->setData('total_abandoned_sync', $newTotalSync);
                                    $SfmcAbandonedCouponModelToUpdate->save();
                                }
                            }
                        } else {
                            $SfmcAbandonedCouponModel = $this->SfmcAbandonedCouponFactory->create();
                            $SfmcAbandonedCouponModel->addData(
                                [
                                    'last_update'              => $currentDate,
                                    'total_abandoned_sync'     => $i,
                                    'abandoned_status'         => false
                                ]
                            );
                            $SfmcAbandonedCouponModel->save();
                        }
                    } else {
                        $SfmcAbandonedCouponModel = $this->SfmcAbandonedCouponFactory->create();
                        $SfmcAbandonedCouponModel->addData(
                            [
                                'last_update'              => $currentDate,
                                'total_abandoned_sync'     => $i,
                                'abandoned_status'         => false
                            ]
                        );
                        $SfmcAbandonedCouponModel->save();
                        echo "new entry";
                    }
                } else {
                    echo "Something went wrong while sync...";
                }
            } else {
                echo "Cart not updated -> \n" . $QuoteId;
            }
        }
        return true;
    }

    public function isCartUpdated($quoteId)
    {

        try {
            $quote = $this->quoteRepository->get($quoteId);
            $quote = $this->quoteRepository->get($quoteId);
            $lastUpdateData = $quote->setPageSize(1)->getData();
            $lastUpdate = $quote->getUpdatedAt();

            $checkAbandonedCartIsExist = $this->isAvailableQuoteId($quoteId);
            if ($lastUpdate == $checkAbandonedCartIsExist) {
                return false;
            } else {
                return $quoteId;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function syncAbandonedCart($quote_id)
    {

        $product_detiails = '';
        $response = '';
        $lastUpdateData = '';
        $customer_id = '';

        if ($this->ValidateBearerKey() != true) {
            $this->CreateBearerKey();
        }

        $keys = $this->GetKeys();
        $bearerKey = $keys["bearerKey"];

        $url = 'https://mc43swm62-c7zw8--y8rwzbh4gdm.rest.marketingcloudapis.com/hub/v1/dataevents/key:4108A6BB-B76D-4744-8406-F423B6ED9D58/rowset';
        //$this->getConfig("sfmc/module_abandoned/create_abandoned_url");
        $checkAbandonedCartIsExist = $this->isAvailableQuoteId($quote_id);
        if ($checkAbandonedCartIsExist) {

            $cartProduct = $this->getProductsFromAbandonedCart($quote_id);

            if (count($cartProduct) > 0) {

                $rowProductData = $cartProduct['product_data'] ?? '';
                $jsonDataWithSingleQuotes = str_replace('"', "'", $rowProductData);
                $customerId = $cartProduct['customer_id'] ?? '';
                $email = $cartProduct['email'] ?? '';
                $discount_coupon = $cartProduct['discount_coupon'] ?? '';
                $gift_card = $cartProduct['gift_card'] ?? '';
                $telephone = $cartProduct['telephone'] ?? '';
                $address = $cartProduct['address'] ?? '';
                $first_name = $cartProduct['first_name'] ?? '';
                $last_name = $cartProduct['last_name'] ?? '';
                $product_detiails = $cartProduct['sku'] ?? '';
                $customer_id = $cartProduct['customer_id'] ?? '';
                $lastUpdateData = $cartProduct['last_updated'] ?? '';
                $keys = $this->GetKeys();
                $bearerKey = $keys["bearerKey"];

                $url = 'https://mc43swm62-c7zw8--y8rwzbh4gdm.rest.marketingcloudapis.com/hub/v1/dataevents/key:4108A6BB-B76D-4744-8406-F423B6ED9D58/rowset';


                $rawDatafinal = '[
                                        {
                                            "keys": {
                                                "iD": "' . $quote_id . '"
                                            },
                                            "values": {
                                                "token": "https://golfoy.com/checkout/cart/",
                                                "line_items": "' . $jsonDataWithSingleQuotes . '",
                                                "billing_address": "' . $address . '",
                                                "shipping_address": "' . $address . '",
                                                "gift_card": "' . $gift_card . '",
                                                "coupon": "' . $discount_coupon . '",
                                                "email": "' . $email . '",
                                                "phone": "' . $telephone . '",
                                                "first_name": "' . $first_name . '",
                                                "last_name": "' . $last_name . '",
                                                "created_date": "' . $lastUpdateData . '",
                                                "customer_ID": "' . $customerId . '"
                                            }
                                        }
                                    ]';

                // echo "<br><br><br>URL: ".$url;
                // echo "<br><br><br>bearerKey: ".$bearerKey;
                echo "<br><br><br>rawDatafinal: " . $rawDatafinal;

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $bearerKey
                ));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $rawDatafinal);
                $response = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                echo "<br><br><br>statusCode: ";
                print_r($statusCode);
                // echo $quote_id;
                echo "\nresponse: ";
                print_r($response);

                $this->updateAbandonedCartTable($quote_id, $product_detiails, $response, $lastUpdateData);
                echo "<br><br><br>statusCode: ";
                print_r($statusCode);
                // echo $quote_id;
                // echo "<br><br><br>response: ";
                // var_dump($response);
                if ($statusCode == 200) {
                    $this->cartLogger->info('Abandoned cart (' . $quote_id . ') product sync code' . $statusCode);
                    return true;
                } else {
                    $this->cartLogger->info('Abandoned cart (' . $quote_id . ') product sync failed code' . $statusCode);
                    return false;
                }
            } else {
                $lastUpdateData = $this->getCustomerIdByQuote($quote_id);
                $customer_id = $this->getCustomerIdByQuote($quote_id);
                $this->insertAbandonedCartTable($quote_id, $customer_id, 'no products', 'empty cart', $lastUpdateData);
                $this->cartLogger->info('Abandoned cart product sync code no product found' . $quote_id);
                return false;
            }
        } else {
            $cartProduct = $this->getProductsFromAbandonedCart($quote_id);

            if (count($cartProduct) > 0) {
                // foreach($cartProduct as $key => $cartProduct){

                $rowProductData = $cartProduct['product_data'];
                $jsonDataWithSingleQuotes = str_replace('"', "'", $rowProductData);
                $product_detiails = $cartProduct['sku'] ?? '';
                $customerId = $cartProduct['customer_id'] ?? '';
                $email = $cartProduct['email'] ?? '';
                $discount_coupon = $cartProduct['discount_coupon'] ?? '';
                $gift_card = $cartProduct['gift_card'] ?? '';
                $telephone = $cartProduct['telephone'] ?? '';
                $address = $cartProduct['address'] ?? '';
                $first_name = $cartProduct['first_name'] ?? '';
                $last_name = $cartProduct['last_name'] ?? '';

                $customer_id = $cartProduct['customer_id'] ?? '';
                $lastUpdateData = $cartProduct['last_updated'] ?? '';
                $keys = $this->GetKeys();
                $bearerKey = $keys["bearerKey"];

                $url = 'https://mc43swm62-c7zw8--y8rwzbh4gdm.rest.marketingcloudapis.com/hub/v1/dataevents/key:4108A6BB-B76D-4744-8406-F423B6ED9D58/rowset';


                $rawDatafinal = '[
                                        {
                                            "keys": {
                                                "iD": "' . $quote_id . '"
                                            },
                                            "values": {
                                                "token": "https://golfoy.com/checkout/cart/",
                                                "line_items": "' . $jsonDataWithSingleQuotes . '",
                                                "billing_address": "' . $address . '",
                                                "shipping_address": "' . $address . '",
                                                "gift_card": "' . $gift_card . '",
                                                "coupon": "' . $discount_coupon . '",
                                                "email": "' . $email . '",
                                                "phone": "' . $telephone . '",
                                                "first_name": "' . $first_name . '",
                                                "last_name": "' . $last_name . '",
                                                "created_date": "' . $lastUpdateData . '",
                                                "customer_ID": "' . $customerId . '"
                                            }
                                        }
                                    ]';


                // echo "<br><br><br>URL: ".$url; 
                // echo "<br><br><br>bearerKey: ".$bearerKey;
                // echo "<br><br><br>rawDatafinal: ".$rawDatafinal;

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $bearerKey
                ));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $rawDatafinal);
                $response = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                echo "<br><br><br>statusCode: ";
                print_r($statusCode);
                // echo $quote_id.'_______';
                // echo $quote_id;
                // echo "response: ";
                // var_dump($response);

                $this->insertAbandonedCartTable($quote_id, $customer_id, $product_detiails, $response, $lastUpdateData);
                if ($statusCode == 200) {
                    $this->cartLogger->info('Abandoned cart (' . $quote_id . ') product sync code' . $statusCode);
                    return true;
                } else {
                    $this->cartLogger->info('Abandoned cart (' . $quote_id . ') product sync failed code' . $statusCode);
                    return false;
                }
            } else {
                $lastUpdateData = $this->getCustomerIdByQuote($quote_id);
                $customer_id = $this->getCustomerIdByQuote($quote_id);
                $this->insertAbandonedCartTable($quote_id, $customer_id, 'no products', 'empty cart', $lastUpdateData);
                $this->cartLogger->info('Abandoned cart product sync code no product found' . $quote_id);
                return false;
            }
        }
        return false;
    }

    public function createCartPriceRuleCoupon()
    {
        $currentDate = date('Y-m-d');
        $SfmcAbandonedCouponModel = $this->SfmcAbandonedCouponFactory->create()->getCollection();
        $SfmcAbandonedCouponModelToUpdate = $SfmcAbandonedCouponModel->addFieldToFilter('last_update', $currentDate);
        if (count($SfmcAbandonedCouponModelToUpdate) > 0) {
            $totalNumberOfCoupons = $SfmcAbandonedCouponModelToUpdate->getData();
            if ($totalNumberOfCoupons['0']['total_abandoned_sync'] > 0) {
                $totalDays = $this->getConfig('sfmc/module_abandoned_coupon/expiry_date');
                $description = $this->getConfig('sfmc/module_abandoned_coupon/descripton');
                $ruleData = [
                    'name' => $description, // Name of the rule
                    'is_active' => 1,
                    'website_ids' => 1,
                    'customer_group_ids' => [0, 1, 2, 3], // Apply to all customer groups
                    'coupon_type' => 2,
                    'use_auto_generation' => 1,
                    'uses_per_coupon' => 1,
                    'uses_per_customer' => 1,
                    'from_date' => date('Y-m-d'),
                    'to_date' => date('Y-m-d', strtotime($totalDays)),
                    'sort_order' => 1,
                    'is_advanced' => 1,
                    'stop_rules_processing' => 1,
                    'description' => $description,  // Description  
                    'store_labels' => [$description], // Store view specific labels
                ];

                // Add condition to ensure no conditions are applied
                $ruleData['conditions'] = [
                    '1' => [
                        'type' => 'Magento\SalesRule\Model\Rule\Condition\Combine',
                        'aggregator' => 'all',
                        'value' => '1',
                        'new_child' => ''
                    ]
                ];
                // Set the discount percentage
                $ruleData['simple_action'] = Rule::BY_PERCENT_ACTION;
                $totalDiscount = $this->getConfig('sfmc/module_abandoned_coupon/total_discount');
                $ruleData['discount_amount'] = $totalDiscount; // 5% discount

                // Create the rule
                $rule = $this->ruleFactory->create();
                $rule->setData($ruleData);
                $rule->save();

                $SfmcAbandonedCouponCollection = $this->SfmcAbandonedCouponFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('last_update', $currentDate);

                foreach ($SfmcAbandonedCouponCollection as $coupon) {
                    $coupon->setData('rule_id', $rule->getId());
                    $coupon->save();
                }
                $totalNumberOfCoupon = $this->getThreeDaysOldAbandonedData();
                return $this->addCouponsToRule($rule->getId(), $totalNumberOfCoupon);
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Get count of exactly 3 days old abandoned carts
     *
     * @return integer
     */
    public function getThreeDaysOldAbandonedData()
    {
        $threeDaysAgoStart = (new \DateTime())->modify('-3 days')->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $threeDaysAgoEnd = (new \DateTime())->modify('-3 days')->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        $quoteCollection = $this->customQuoteCollectionFactory->create();
        $quoteCollection->addFieldToFilter('is_active', 1);
        $quoteCollection->addFieldToFilter('items_count', ['neq' => 0]);
        $quoteCollection->addFieldToFilter('customer_id', ['notnull' => true]);
        $quoteCollection->addFieldToFilter('updated_at', ['from' => $threeDaysAgoStart, 'to' => $threeDaysAgoEnd]);
        $quoteCollection->setOrder('updated_at', 'ASC');
        $abandonedCartCount = $quoteCollection->getSize();
        return $abandonedCartCount;
    }


    public function addCouponsToRule($ruleId,  $numCoupons)
    {
        // Load the rule by its ID
        try {
            $rule = $this->ruleFactory->create()->load($ruleId);
            $couponCodePrefix = $this->getConfig('sfmc/module_abandoned_coupon/prefix');
            $couponCodeSuffix = $this->getConfig('sfmc/module_abandoned_coupon/suffix');
            $couponCodeLength = $this->getConfig('sfmc/module_abandoned_coupon/total_length');
            $generatedCouponCodes = [];

            for ($i = 0; $i < $numCoupons; $i++) {
                // Generate a coupon code with prefix and suffix
                $couponCode = $this->generateCouponCode($couponCodePrefix, $couponCodeSuffix, $couponCodeLength);
                $generatedCouponCodes[] = $couponCode;
            }

            // Add the generated coupon codes to the rule
            foreach ($generatedCouponCodes as $couponCode) {
                $coupon = $this->couponFactory->create();
                $coupon->setRuleId($ruleId)
                    ->setCode($couponCode)
                    ->setUsageLimit(1)
                    ->setUsagePerCustomer(1)
                    ->setType(1)
                    ->save();
            }
            return "Added 10 coupons to rule with ID $ruleId.";
        } catch (\Exception $e) {

            return false;
        }
    }

    /**
     * Generate a coupon code with prefix and suffix.
     *
     * @param string $prefix
     * @param string $suffix
     * @param int $length
     * @return string
     */
    public function generateCouponCode($prefix, $suffix, $length)
    {
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = substr(str_shuffle($str_result), 0, $length);
        return $prefix . $randomString . $suffix;
    }

    public function isEnableAbandonedCoupon()
    {
        return $this->getConfig('sfmc/module_abandoned_coupon/cron');
    }

    public function ProccessCouponForAbandonedCart()
    {
        $this->CreateBearerKey();
        if ($this->IsModuleEnable()) {
            $currentDate = date('Y-m-d');
            if ($this->createCartPriceRuleCoupon() != false) {
                $SfmcAbandonedCouponModel = $this->SfmcAbandonedCouponFactory->create()->getCollection();
                $SfmcAbandonedCouponModelToUpdate = $SfmcAbandonedCouponModel->addFieldToFilter('last_update', $currentDate);
                if (count($SfmcAbandonedCouponModelToUpdate) > 0) {
                    $totalNumberOfCoupons = $SfmcAbandonedCouponModelToUpdate->getData();
                    if ($totalNumberOfCoupons['0']['rule_id'] > 0) {
                        $couponCollection = $this->couponCollectionFactory->create()
                            ->addFieldToFilter('rule_id', $totalNumberOfCoupons['0']['rule_id']);
                        $couponCodes = [];
                        foreach ($couponCollection as $coupon) {
                            // $couponCodes[] = $coupon->getCode();
                            echo 'We are Syncing coupon : ' . $coupon->getCode() . '\n';
                            if ($this->syncCouponForAbandonedCart($coupon->getCode())) {
                                $this->abandonedCouponLogger->info('Coupon code Sync Successful ' . $coupon->getCode());
                            } else {
                                $this->abandonedCouponLogger->info('Coupon code Sync Failed ' . $coupon->getCode());
                            }
                        }
                        $SfmcAbandonedCouponCollection = $this->SfmcAbandonedCouponFactory->create()
                            ->getCollection()->addFieldToFilter('last_update', $currentDate);

                        foreach ($SfmcAbandonedCouponCollection as $coupon) {
                            $coupon->setData('abandoned_status', true);
                            $coupon->save();
                        }
                        echo "\nCoupon  synced successfully.";
                        return true;
                    } else {
                        $this->abandonedCouponLogger->info('Rule id not found');
                        // $this->abandonedCouponLogger->info('We are try again to create create rule and sync');
                        // $this->createCartPriceRuleCoupon();
                        // $this->ProccessCouponForAbandonedCart();
                    }
                } else {
                    $this->abandonedCouponLogger->info('Current Date coupon not created ' . $currentDate);
                    $this->ProcessAbandonedCart();
                    $this->abandonedCouponLogger->info('We are try again to create create rule and sync');
                    $this->createCartPriceRuleCoupon();
                    $this->ProccessCouponForAbandonedCart();
                }
            } else {
                $this->abandonedCouponLogger->info('Current Date coupon not created ' . $currentDate);
                $this->ProcessAbandonedCart();
                $this->abandonedCouponLogger->info('We are try again to create create rule and sync');
                $this->createCartPriceRuleCoupon();
                $this->ProccessCouponForAbandonedCart();
                $this->abandonedCouponLogger->info('Coupon Not able to Create');
            }
        } else {
            $this->abandonedCouponLogger->info('Abandoned Cart Coupon Sync Disable');
        }
    }

    public function syncCouponForAbandonedCart($abndCoupon)
    {
        $currentDate = date('Y-m-d');
        $response = 'null';
        $url = $this->getConfig('sfmc/module_abandoned_coupon/abandoned_coupon_url');
        //'https://mc43swm62-c7zw8--y8rwzbh4gdm.rest.marketingcloudapis.com/hub/v1/dataevents/key:9FA442D8-3191-428F-8533-A11E4D412E7F/rowset'; 
        //$this->GetCouponCreateUrl();

        $keys = $this->GetKeys();
        $bearerKey = $keys["bearerKey"];
        $rawDatafinal = '[
                            {
                                "keys": {
                                    "Coupon_code": "' . $abndCoupon . '"
                                },
                                "values": {
                                    "isClaimed":"False",
                                    "creation_date":"' . $currentDate . '"
                        
                                }
                            }
                        ]';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer " . $bearerKey
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawDatafinal);
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        echo $response = 'statusCode: ' . $statusCode . '  ->   ' . $response . "\n";
        if ($statusCode == 200) {
            // $this->abandonedCouponLogger->info( $abndCoupon ,'FALSE', $response);
            return true;
        } else {
            // $this->abandonedCouponLogger->info( $abndCoupon , 'FALSE' , $response);
            return false;
        }
    }

    public function failedToSyncAbandonedCoupon()
    {
        $this->CreateBearerKey();
        if ($this->IsModuleEnable()) {
            if ($this->createCartPriceRuleCoupon() != false) {
                $currentDate = date('Y-m-d');
                $SfmcAbandonedCouponModel = $this->SfmcAbandonedCouponFactory->create()->getCollection();
                $SfmcAbandonedCouponModelToUpdate = $SfmcAbandonedCouponModel->addFieldToFilter('last_update', $currentDate);
            }
        }
    }
}
