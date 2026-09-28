<?php
    namespace ArtM\CancelOrder\Controller\Index;

    use Magento\Framework\App\Action\Action;
    use Magento\Framework\App\Action\Context;
    use Magento\Framework\App\RequestInterface;
    use Magento\Framework\View\Result\PageFactory;
    use Magento\Framework\Controller\Result\JsonFactory;
    
    class CancelOrder extends Action
    {
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
            \Magento\Sales\Model\OrderFactory $orderFactory,
            \Magento\Sales\Api\OrderManagementInterface $orderManagement
        ) {
            parent::__construct($context);
            $this->_resultJsonFactory = $_resultJsonFactory;
            $this->request = $request;   
            $this->resultPageFactory = $resultPageFactory; 
            $this->orderFactory = $orderFactory;
            $this->orderManagement = $orderManagement;
        }
    
        public function execute()
        {
            $resultPage = $this->resultPageFactory->create();
       
            $result = $this->_resultJsonFactory->create();
            
            $formData = $this->request->getPost();
            $parts = explode("_", $formData['orderId']);
            if ($formData) {
                try {
                    $order = $this->orderFactory->create()->loadByIncrementId($parts[0]);
                    $orderId = $order->getEntityId();
                    $this->orderManagement->cancel($orderId);
                    $response = ['success' => true];
                } catch (\Exception $e) {
                    $response = ['success' => false, 'error' => $e->getMessage()];
                }
            }
            $result->setData($response);
            return $result;
        }
    }

?>