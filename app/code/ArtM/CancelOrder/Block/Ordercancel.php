<?php

namespace ArtM\CancelOrder\Block;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderFactory;
use ArtM\CancelOrder\Controller\Page\Cancelorderrequest;
use ArtM\CancelOrder\Model\ResourceModel\CancelOrderCustom\CollectionFactory;
   class Ordercancel extends \Magento\Framework\View\Element\Template
   {
     
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;
    protected $orderFactory;
    
    public function __construct(
      \Magento\Framework\View\Element\Template\Context $context,
      Registry $registry,      
      \Magento\Sales\Model\OrderFactory $orderFactory,
      array $data = []
      ) {

         parent::__construct($context, $data);
         $this->coreRegistry = $registry;      
         $this->orderFactory = $orderFactory;
  }


   function customorder(){
		echo  "hello order";
	}
  
     public function getOrder()
        {
             return $this->coreRegistry->registry('current_order');
         }

    public function getOrderStatusLabel($orderId)
    {
            return $order = $this->orderFactory->create()->load($orderId)->getStatusLabel();
           // return $order = $this->orderFactory->create()->load($orderId)->getState();
    
    }
    public function getOrderStateLabel($orderId)
    {
            //return $order = $this->orderFactory->create()->load($orderId)->getStatusLabel();
            return $order = $this->orderFactory->create()->load($orderId)->getState();
    
    }
    public function setOrdercancelrequest()
    {
       return $this->getUrl('cancelorder/index/submit', ['_secure' => true]);
    }

//////////////////////////////////////////////////////////////////////////


  
   }