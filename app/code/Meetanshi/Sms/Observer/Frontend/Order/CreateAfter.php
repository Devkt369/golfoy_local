<?php

namespace Meetanshi\Sms\Observer\Frontend\Order;

use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Meetanshi\Sms\Helper\Data;
use ArtM\SFMC\Helper\Data as SFMChelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class Orderplaceafter
 * @package Meetanshi\Sms\Observer
 */
class CreateAfter implements ObserverInterface
{
    /**
     *
     */
    const SMS_ADMIN_MOBILE = 'sms/general/mobilenumber';

    /**
     *
     */
    const SMS_SHIPMENTTRACKNUMBERADDED_ENABLE = 'sms/orderstatuschangeafter/enabled';
    const SMS_ORDER_CREATE_ENABLE = 'sms/order/enabled';

    const SMS_ORDER_CREATE_ENABLE_MSGTOADMIN = 'sms/order/msgtoadmin';
    /**
     *
     */
    const SMS_ORDER_CREATE_ENABLE_TEXT = 'sms/order/smstext';
    /**
     *
     */
    const SMS_SHIPMENTTRACKNUMBERADDED_SMSTEXT = 'sms/orderstatuschangeafter/smstext';
    /**
     *
     */
    const SMS_SHIPMENTTRACKNUMBERADDED_SMSTEXTADMIN = 'sms/orderstatuschangeafter/smstextadmin';

    /**
     *
     */
    const MSG91_TEMPLATE_ID = 'sms/orderstatuschangeafter/templateid';
    /**
     *
     */
    const TEMPLATE_ID_ADMIN = 'sms/orderstatuschangeafter/templateidadmin';

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var PriceHelper
     */
    protected $priceHelper;
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    protected $SFMChelper;

    /**
     * Orderplaceafter constructor.
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $data
     * @param StoreManagerInterface $storeManager
     * @param PriceHelper $priceHelper
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        Data $data,
        StoreManagerInterface $storeManager,
        PriceHelper $priceHelper,
        SFMChelper $SFMChelper,
        OrderRepositoryInterface $orderRepository
    )
    {
        $this->logger = $logger;
        $this->helper = $data;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->priceHelper = $priceHelper;
        $this->orderRepository = $orderRepository;
        $this->SFMChelper = $SFMChelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return bool|void
     */
    public function getShippingAddressDetails($orderId)
    {
        $details = [
            'firstName' => null,
            'lastName'  => null,
            'telephone' => null
        ];

        try {
            $order = $this->orderRepository->get($orderId);
            $shippingAddress = $order->getShippingAddress();
            if ($shippingAddress) {
                $details['firstName'] = $shippingAddress->getFirstname();
                $details['lastName']  = $shippingAddress->getLastname();
                $details['telephone'] = $shippingAddress->getTelephone();
            }
            return $details;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $details; // Return default structure with null values if no order found
        }
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->helper->customLog("create order apiCall call");
        try {
            if ($this->helper->smsEnable()) {
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
                $enableOrderCreate = $this->scopeConfig->getValue(self::SMS_ORDER_CREATE_ENABLE, $storeScope);
                $enableForadmin = $this->scopeConfig->getValue(self::SMS_ORDER_CREATE_ENABLE_MSGTOADMIN, $storeScope);

                $order = $observer->getOrder();
                
                $Id = $order->getId();
                $IncrementId = $order->getIncrementId();
                $Status = $order->getStatus();
                $State = $order->getState();
                $CustomerId = $order->getCustomerId();
                $CustomerEmail = $order->getCustomerEmail();
                $CustomerFirstname = $order->getCustomerFirstname();
                $CustomerLastname = $order->getCustomerLastname();
                $CustomerGroupId = $order->getCustomerGroupId();
                $BillingAddress = $order->getBillingAddress();
                $ShippingAddress = $order->getShippingAddress();
                $Subtotal = $order->getSubtotal();
                $GrandTotal = $order->getGrandTotal();
                $ShippingAmount = $order->getShippingAmount();
                $TaxAmount = $order->getTaxAmount();
                $DiscountAmount = $order->getDiscountAmount();
                $CouponCode = $order->getCouponCode();
                $Payment = $order->getPayment()->getMethod();
                $CreatedAt = $order->getCreatedAt();
                $UpdatedAt = $order->getUpdatedAt();
                // $BillingAddress = $order->getBillingAddress()->getTelephone();
                //var_dump($ShippingAddress); die;
                
                
               
                
                if ($enableOrderCreate) {
                    
                   
                    $adminMobiles = explode(',', $this->scopeConfig->getValue(self::SMS_ADMIN_MOBILE, $storeScope));

                    // $msgText = $this->scopeConfig->getValue(self::SMS_ORDER_CREATE_ENABLE_TEXT, $storeScope);
                    // $temp_id = $this->scopeConfig->getValue(self::MSG91_TEMPLATE_ID, $storeScope);
                    // $storeurl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true);
                    $orderTotal = round($order->getGrandTotal(), 2);
                    $CreateOrderData = [
                        "orderId" => $IncrementId,
                        "order_total" => $orderTotal,
                        "first_name" => $CustomerFirstname
                    ];
                    // $codes = ['{{Id}}', '{{IncrementId}}', '{{Status}}', '{{State}}', '{{CustomerId}}', '{{CustomerEmail}}', '{{CustomerFirstname}}', '{{CustomerLastname}}', '{{CustomerGroupId}}', '{{Subtotal}}', '{{GrandTotal}}', '{{ShippingAmount}}', '{{TaxAmount}}', '{{DiscountAmount}}', '{{CouponCode}}', '{{Payment}}', '{{CreatedAt}}', '{{UpdatedAt}}'];
                    
                    // $accurate = [$Id, $IncrementId, $Status, $State, $CustomerId, $CustomerEmail, $CustomerFirstname, $CustomerLastname, $CustomerGroupId, $Subtotal, $orderTotal, $ShippingAmount, $TaxAmount, $DiscountAmount, $CouponCode, $Payment, $CreatedAt, $UpdatedAt];
                   
                    // $finalContactText = str_replace($codes, $accurate, $msgText);
                    $ShippingAddressDetails = $this->getShippingAddressDetails($Id);

                    $observerName = 'checkout_onepage_controller_success_action';
                    // print_r($CreateOrderData);die;
                    // $this->helper->OrderSuccess( $CreateOrderData, $ShippingAddressDetails['telephone'], $observerName);

                    if(!$order->getData("pos_id")){
                        $this->helper->OrderSuccess( $CreateOrderData, $ShippingAddressDetails['telephone'], $observerName);
                    }
                    // if ($enableForadmin) {
                    //     $msgText = $this->scopeConfig->getValue(self::SMS_SHIPMENTTRACKNUMBERADDED_SMSTEXTADMIN, $storeScope);
                        
                    //     $codes = ['{{Id}}', '{{IncrementId}}', '{{Status}}', '{{State}}', '{{CustomerId}}', '{{CustomerEmail}}', '{{CustomerFirstname}}', '{{CustomerLastname}}', '{{CustomerGroupId}}',  '{{Subtotal}}', '{{GrandTotal}}', '{{ShippingAmount}}', '{{TaxAmount}}', '{{DiscountAmount}}', '{{CouponCode}}', '{{Payment}}', '{{CreatedAt}}', '{{UpdatedAt}}'];

                    //     $accurate = [$Id, $IncrementId, $Status, $State, $CustomerId, $CustomerEmail, $CustomerFirstname, $CustomerLastname, $CustomerGroupId, $Subtotal, $GrandTotal, $ShippingAmount, $TaxAmount, $DiscountAmount, $CouponCode, $Payment, $CreatedAt, $UpdatedAt];

                    //     $finalContactText = str_replace($codes, $accurate, $msgText);
                    //     $temp_id_admin = $this->scopeConfig->getValue(self::TEMPLATE_ID_ADMIN, $storeScope);
                    //     foreach ($adminMobiles as $adminMobile) {
                    //         $this->helper->apiCall($finalContactText, $adminMobile, $temp_id_admin);
                    //     }
                    // }
                }   
            }
            $this->SFMChelper->SyncCustomer($CustomerId);
            return true;
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
