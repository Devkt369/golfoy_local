<?php
    namespace ArtM\Common\Controller\Index;

    use Magento\Framework\App\Action\Action;
    use Magento\Framework\App\Action\Context;
    use Magento\Framework\App\RequestInterface;
    use Magento\Framework\View\Result\PageFactory;
    use Magento\Framework\Controller\Result\JsonFactory;
    use Magento\Checkout\Model\Cart;
    use Magento\Framework\Pricing\Helper\Data;
    
    class PriceUpdate extends Action
    {
        protected $cart;
        protected $priceHelperData;
        protected $orderFactory;
        protected $orderManagement;
        protected $_resultJsonFactory;
        protected $request;   
        protected $resultPageFactory;   
    
        public function __construct(
            Context $context, 
            PageFactory $resultPageFactory,
            RequestInterface $request,
            JsonFactory $_resultJsonFactory,
            Cart $cart,
            Data $priceHelperData
        ) {
            parent::__construct($context);
            $this->_resultJsonFactory = $_resultJsonFactory;
            $this->request = $request;   
            $this->resultPageFactory = $resultPageFactory;
            $this->cart = $cart;
            $this->priceHelperData = $priceHelperData;
        }
    
        public function execute()
        {
            $resultPage = $this->resultPageFactory->create();
            $result = $this->_resultJsonFactory->create();
            
            // $formData = $this->request->getPost();

            $cartItems = $this->cart->getQuote()->getAllVisibleItems();
            $grandTotal = $this->cart->getQuote()->getGrandTotal();
            $earningPoint = $this->cart->getQuote()->getData('mp_reward_earn');
            $totalMRP = 0;
            foreach ($cartItems as $item) {
                $product = $item->getProduct();
                if ($product->getTypeId() == 'configurable') {
                    $childProduct = $item->getOptionByCode('simple_product')->getProduct();
                    $product = $childProduct;
                }
                if ($product->getTypeId() == 'giftvoucher') {
                    $response = ['save' => 'giftvoucher'];
                    $result->setData($response);
                    return $result;
                }
                $productPrice = $product->getPrice();
                $qty = $item->getQty();
                $totalMRP += $productPrice * $qty;
            }
            
            $save = $totalMRP - $grandTotal + $earningPoint;
            $grandTotal = $this->priceHelperData->currency($grandTotal, true, false);
            $totalMRP = $this->priceHelperData->currency($totalMRP, true, false);

            $save = $this->priceHelperData->currency($save, true, false);
            // echo "\n Grand total = ".$grandTotal;
            // echo "\n Cart Total = ".$totalMRP;
            // echo "\n You Save = ".$save;

                try {
                    $response = ['save' => $save];
                } catch (\Exception $e) {
                    $response = ['success' => false, 'error' => $e->getMessage()];
                }
            $result->setData($response);
            return $result;
        }
    }

?>