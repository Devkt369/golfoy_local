<?php
/**
 *
 * @copyright  Snapmint Payment
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Snapcommerce\Snapmint\Controller\Checkout;

use Snapcommerce\Snapmint\Logger\LoggerPost;
class Redirect extends \Magento\Framework\App\Action\Action
{
     /**
     * @var \Magento\Checkout\Model\Session
     */
     protected $_checkoutSession;

    /**
    * @var \Snapcommerce\Snapmint\Model\Payment\Snapmint
    */
    protected $_paymentMethod;

     /**
     * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
     protected $_resultJsonFactory;

	/**
     * @var LoggerPost
     */
    protected LoggerPost $_logger;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

     /**
     * @var \Snapcommerce\Snapmint\Helper\Data
     */
     protected $_helper;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

     protected $objectManager;



    /**
    * @param \Magento\Customer\Model\Session $customerSession
    * @param \Magento\Checkout\Model\Session $checkoutSession
    * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    * @param \Snapcommerce\Snapmint\Helper\Data $helper
    * @param LoggerPost $logger
    */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Snapcommerce\Snapmint\Helper\Data $helper,
        LoggerPost $logger

    ) {

        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;

        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_logger = $logger;
        $this->_helper = $helper;

        $this->objectManager   = \Magento\Framework\App\ObjectManager::getInstance();
        parent::__construct($context);
    }

    /**
     * Instantiate quote and checkout
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function initCheckout()
    {
        $quote = $this->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t initialize checkout.'));
        }
    }


    public function getCustomerSession()
    {
        return $this->_customerSession;
    }



    protected function getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->getCheckoutSession()->getQuote();
        }

        return $this->_quote;
    }

    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    /**
    * Get order object.
    *
    * @return \Magento\Sales\Model\Order
    */
    protected function getOrder()
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        $incrementId = $order->getIncrementId();

        if(empty($incrementId)){
            $lastQuoteId = $this->_checkoutSession->getLastQuoteId();

            if(empty($lastQuoteId)){
                $lastQuoteId = $this->_checkoutSession->getQuoteId();
            }

            $this->_logger->info("snapmeint empty entityId lastQuoteId : " . $lastQuoteId);

            //added by sandeep
            $quote = $this->objectManager->get('Magento\Quote\Model\Quote')->load($lastQuoteId);

            $lastOrderId = $quote->getReservedOrderId();

            $order = $this->objectManager->get('Magento\Sales\Model\Order')->loadByIncrementId($lastOrderId);

            $entityId = $order->getEntityId();

            $this->_logger->info("snapmeint order entityId found : " . $entityId);
        }
        return $order;
    }


    /**
    * Start checkout by requesting checkout code and dispatching customer to Snapmint Payment Gateway.
    */
    public function execute()
    {

        $order = $this->getOrder();

        $billingAddress = $order->getBillingAddress();

        $quote = $this->getQuote();
        $email = $billingAddress->getData('email');

        if ($this->getCustomerSession()->isLoggedIn()) {
            $this->getCheckoutSession()->loadCustomerQuote();
            $quote->updateCustomerData($this->getQuote()->getCustomer());
        } else {
            $quote->setCustomerEmail($email);
        }

        if ($this->getCustomerSession()->isLoggedIn()) {
            $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER);
        } else {
            $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
        }

        $quote->setCustomerEmail($email);
        $quote->setIsActive(1); //force cart enabled
        //$this->_checkoutSession->restoreQuote();
        //$this->getCheckoutSession()->setQuote($quote);
        $quote->save();



        $snapmint_base_url =  $this->_helper->getSnapmintBaseUrl();

        $merchant_key = $this->_helper->getConfig('payment/snapmint/merchantkey');
        $merchant_token = $this->_helper->getConfig('payment/snapmint/merchanttoken');
        $sub_merchant_id = $this->_helper->getConfig('payment/snapmint/submerchantid');

        $subvention_fees = $this->_helper->getConfig('payment/snapmint/subventionfees');


        $orderid = $order->getIncrementId();
        $amount = $order->getGrandTotal();



        $amount = number_format((float)$amount, 2, '.', '');
        $success_url = $this->_helper->getSnapmintReturnUrl();
        $failure_url = $this->_helper->getSnapmintReturnUrl();


        $form_action_url = $snapmint_base_url.'v1/public/online_checkout';


        $currency = $order->getOrderCurrencyCode();


        $firstname = $billingAddress->getData('firstname');
        $lastname = $billingAddress->getData('lastname');
        $email = $billingAddress->getData('email');
        $phone = $billingAddress->getData('telephone');

        $product_info = array();
        $counter = 0;
        foreach($order->getAllVisibleItems() as $item) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $product = $objectManager->create('Magento\Catalog\Model\Product')->load($item->getProduct()->getId());
            $produrl = $product->getProductUrl();

            $product_info[$counter]['sku']             = $item->getSku() ;
            $product_info[$counter]['name']            = $item->getName() ;
            $product_info[$counter]['quantity']        = $item->getQtyOrdered() ;
            $product_info[$counter]['item_url']        = $produrl ;
            $product_info[$counter]['unit_price']      = number_format((float)$item->getPriceInclTax(), 2, '.', '') ;
            $product_info[$counter]['udf1']            = '' ;
            $product_info[$counter]['udf2']            = '' ;
            $product_info[$counter]['udf3']            = '' ;

        }



        $address = $billingAddress->getStreet();
        $city = $billingAddress->getData('city');
        $zipcode = $billingAddress->getData('postcode');
        $state = $billingAddress->getData('region');

        $shippingAddress = $order->getShippingAddress();
        $shipping_firstname = $shippingAddress->getData('firstname');
        $shipping_lastname = $shippingAddress->getData('lastname');
        $shipping_phone = $shippingAddress->getData('telephone');
        $shipping_address = $shippingAddress->getStreet();
        $shipping_city = $shippingAddress->getData('city');
        $shipping_zipcode = $shippingAddress->getData('postcode');
        $shipping_state = $shippingAddress->getData('region');

        $fullname = $firstname.' '.$lastname;

        $checksum = hash('sha512', $merchant_key.'|'.$orderid.'|'.$amount.'|'.$fullname.'|'.$email.'|'.$merchant_token, false);


        $this->_logger->info('---Redirect Data---');
        $this->_logger->info('-checksum-');
        $this->_logger->info($merchant_key.'|'.$orderid.'|'.$amount.'|'.$fullname.'|'.$phone.'|'.$merchant_token);
        $this->_logger->info($checksum);

        $result = $this->_resultJsonFactory->create();

        $post_data = array(
            'action'                        => $form_action_url,
            'product_info'                  => $product_info,
            'form_fields' => array (
                'merchant_key'              => $merchant_key,
                'merchant_confirmation_url' => $success_url.$orderid,
                'merchant_failure_url'      => $failure_url.$orderid,
                'checksum_hash'             => $checksum,

                'sub_merchant_id'           => $sub_merchant_id,
                'shipping_fees'             => $order->getShippingAmount(),
                'discount_code'             => '',
                'order_id'                  => $orderid,
                'order_value'               => $amount,
                'subvention_fees'           => $subvention_fees,
                'udf1'                      => '',
                'udf2'                      => '',
                'udf3'                      => '',

                'first_name'                => $firstname,
                'middle_name'               => '',
                'lastname'                  => $lastname,
                'email'                     => $email,
                'full_name'                 => $firstname.' '.$lastname,
                'mobile'                    => $phone,

                'shipping_first_name'       => $shipping_firstname,
                'shipping_middle_name'      => '',
                'shipping_last_name'        => $shipping_lastname,
                //'shipping_full_name'        => $shipping_firstname.' '.$shipping_lastname,
                'shipping_address_line1'    => $shipping_address[0],
                'shipping_address_line2'    => (isset($shipping_address[1])? $shipping_address[1] : ''),
                'shipping_city'             => $shipping_city,
                'shipping_zip'              => $shipping_zipcode,
                //'shipping_state'            => $shipping_state,

                'billing_first_name'        => $firstname,
                'billing_middle_name'       => '',
                'billing_last_name'         => $lastname,
                //'billing_full_name'         => $firstname.' '.$lastname,
                'billing_address_line1'     => $address[0],
                'billing_address_line2'     => (isset($address[1])? $address[1] : ''),
                'billing_city'              => $city,
                'billing_zip'               => $zipcode,
                //'billing_state'             => $state
            )
        );




        $this->_logger->info('snapmint post data');
        $this->_logger->info(print_r($post_data,true));

        return $result->setData($post_data);
		//return $result->setData($post_data);

    }
}
