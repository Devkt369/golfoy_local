<?php
namespace ArtM\Review\Controller\Submit;

class Close extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \ArtM\Review\Model\GetreviewedorderFactory $getreviewedorder,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory

    )
    {
        $this->resultJsonFactory = $resultJsonFactory;

        $this->_pageFactory = $pageFactory;
        $this->_getreviewedorder = $getreviewedorder;
        return parent::__construct($context);
    }

    
    public function execute()
    {       
        
        $result = $this->resultJsonFactory->create();
        date_default_timezone_set("Asia/Calcutta");   //India time (GMT+5:30)
        
        $date = date('Y-m-d H:i:s');
        $prd_ids = trim( ($this->getRequest()->getParam('prd_id') == '') ? ' ' : $this->getRequest()->getParam('prd_id') );
        $customer_id = trim( ($this->getRequest()->getParam('customer_id') == '') ? ' ' : $this->getRequest()->getParam('customer_id') );
        $curr_order_state = trim( ($this->getRequest()->getParam('curr_order_state') == '') ? ' ' : $this->getRequest()->getParam('curr_order_state') );
        $order_id = trim( ($this->getRequest()->getParam('order_id') == '') ? ' ' : $this->getRequest()->getParam('order_id') );
        $reviewd_or_not = trim( ($this->getRequest()->getParam('reviewd_or_not') == '') ? ' ' : $this->getRequest()->getParam('reviewd_or_not') );
        /*
        echo "prd_ids: ".$prd_ids."<br>";
        echo "customer_id: ".$customer_id."<br>";
        echo "curr_order_state: ".$curr_order_state."<br>";
        echo "order_id: ".$order_id."<br>";
        echo "reviewd_or_not: ".$reviewd_or_not."<br>";
        */
        
        
        $reviewordercollection = $this->_getreviewedorder
            ->create()
            ->getCollection()
            ->addFieldToFilter('order_id', $order_id);
        $countrevieworder = count($reviewordercollection);
//                echo "<br>countrevieworder: ".$countrevieworder;
        if($countrevieworder > 0){
            return false;
        }
        
        
        
        
        if($prd_ids != '' && $customer_id != '' && $curr_order_state != '' && $order_id != '' && $reviewd_or_not != '' ){
//            echo "not empty";
            $getreviewedorder_model = $this->_getreviewedorder->create();
            $getreviewedorder_model->addData([
                "order_id" => $order_id,
                "customer_id" => $customer_id,
                "order_status" => $curr_order_state,
                "updated_at" => $date,
                "product_ids" => $prd_ids,
                "review_given" => $reviewd_or_not
            ]);
            $saveData = $getreviewedorder_model->save();
        }else{
//            echo " empty";
        }
        
        
        $data=Array
        (
            'html' => 'html',
        );
        return $result->setData($data);   
        
        
         
    }
}


