<?php
namespace ArtM\Delhiveryqc\Block\Adminhtml;

class Delhiveryqc extends \Magento\Framework\View\Element\Template
{
    protected $_storeManager;
    protected $_urlInterface;
    protected $orderRepository;
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,        
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        array $data = []
    ){
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $data);
    }
    
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
    
    public function GetQcFormAction(){
        return $this->getUrl('qualitycheck/submit');        
    }  
    
    public function GetProductDetails($order_id){
        $order = $this->orderRepository->get($order_id);
        
        $orderItems = $order->getAllItems();
        $i = 0;
        $product_data = array();
        foreach ($orderItems as $item) {
            $data = $item->getProductOptions();
              
            // $prd_data = $data['info_buyRequest'];
            if ($item['product_type'] === 'simple' && isset($item['parent_item']['price_incl_tax'])) {
                $single_prd = array();
                $single_prd['prd_id'] = $item['product_id'];
                $single_prd['sku'] = $item->getSku();
                $single_prd['name'] = $item->getName();
                $single_prd['qtyordered'] = $item->getQtyOrdered();
                $single_prd['price'] = $item['parent_item']['price_incl_tax'];
                $single_prd['rowtotal'] = $item->getRowTotal();
                $i++;
                $product_data[] = $single_prd;
            }
        }
        return $product_data;
    }
    public function GetOrderDetails($order_id){
        
        $order = $this->orderRepository->get($order_id);
        $vals = array();
        
        $vals['incrementid'] = $order->getIncrementId();
        
        $vals['createdat'] = $order->getCreatedAt();
        if($order->getShippingAddress()){
            $billing_address = $order->getShippingAddress()->getData();
            $vals['telephone'] = $billing_address['telephone'];
            
            $vals['name'] = $billing_address['firstname']." ".$billing_address['lastname'];
            
            $vals['country'] = $billing_address['country_id'];
            $vals['street'] = $billing_address['street'];
            $vals['postcode'] = $billing_address['postcode'];
            $vals['city'] = $billing_address['city'];
            $vals['region'] = $billing_address['region'];
            
            $vals['payment_mode'] = $order->getPayment()->getMethod();
            return $vals;

        }
//        print_r($vals);
//        echo "<br><br><br><br><br><br>";
        ////////////////////////////////////////////
//        product description
        
        
        
        
        
        
//        
//        //"phone": "9654395588",
//        
//        "products_desc": "nokia 6 32 gb memory",
//        //"name": "amit",
//        //"country": "India",
//        //"order_date": "2018-05-18 06:22:43",
//        "total_amount": "1.0",
//        //"add": "jaipur, ",
//        //"pin": "110096",
//        "quantity": "1",
//        "payment_mode": "Pickup",
//        "state": "Delhi",
//        "city": "Delhi
//
//        
        /*
        echo "<br>grandTotal:";
        echo $grandTotal = $order->getGrandTotal();
        echo "<br>subTotal:";
        echo $subTotal = $order->getSubtotal();

        echo "<br>amount:";
        echo $amount = $order->getPayment()->getAmountPaid();
        echo "<br>paymentMethod:";
        echo $paymentMethod = $order->getPayment()->getMethod();
        echo "<br>info:";
        echo $info = $order->getPayment()->getAdditionalInformation('method_title');

        echo "<br>orderItems:<br>";
        
        */

        
        
    }
    
//        $base_url = $this->_storeManager->getStore()->getBaseUrl();
//        $media_url = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    public function getResponseSaveUrl()
    {
        return $this->getUrl('delhiveryqc/response/save');
    }
}
