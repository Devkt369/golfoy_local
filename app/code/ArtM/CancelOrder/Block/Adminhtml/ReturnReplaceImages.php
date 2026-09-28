<?php
namespace ArtM\CancelOrder\Block\Adminhtml;
use Magento\Sales\Model\OrderFactory;
class ReturnReplaceImages extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \ArtM\CancelOrder\Model\ReturnOrderImageFactory $returnimages,
        \ArtM\CancelOrder\Model\BankAccountDetailsFactory $refundType,
        \ArtM\CancelOrder\Model\CancelOrderCustomFactory $cancelOrder,
        OrderFactory $orderFactory,
        array $data = []
    ){
        $this->returnimages = $returnimages;
        $this->refundType = $refundType;
        $this->cancelOrder = $cancelOrder;
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }
    
    public function getAccountDetails($refund_account_id){
        $Accdetails = $this->refundType->create();
        $Accdetails->load($refund_account_id);
        $data = $Accdetails->getData(); // get the loaded data
        $bankdetails = $data['details'];
        if(isset($bankdetails)){
            $bankdetails = explode("||", $bankdetails);
            return $bankdetails;        
        }
    }
        public function getAllReturnedRequests( $orderid ){
            $orderCollections = $this->orderFactory->create()->load($orderid);
            foreach ($orderCollections->getAllItems() as $item) {
                $incId['product_names'][] = $item->getName();
            }

        $AllReturnedRequestsCollection = $this->cancelOrder->create()->getCollection()
            ->addFieldToFilter('order_id', $orderid);
        
        if( $AllReturnedRequestsCollection->count() > 0 ){
            $AllReturnedRequestsCollectioncollection_data = array();

            foreach($AllReturnedRequestsCollection as $item){
                $AllReturnedRequestsCollectioncollection_data[] = $item->getData();
            }
            $shippingAddress = $orderCollections->getShippingAddress();
            
            $incId = [
                'customer_name' => $shippingAddress->getName(),
                'customer_phone' => $shippingAddress->getTelephone(),
                'incremented_id' => $orderCollections->getIncrementId()
            ];
            array_push($AllReturnedRequestsCollectioncollection_data,$incId);
            return $AllReturnedRequestsCollectioncollection_data;
        }
        return false;
    }
    public function getCrrRequestSaveAdminUrl(){
        return $this->getUrl('cancelorderrequest/request/update');
    }
    public function getimages($orderid, $prd_id){
        
        $returnimagescollection = $this->returnimages->create()->getCollection()
            ->addFieldToFilter('prd_id', $prd_id)
            ->addFieldToFilter('order_id', $orderid);
        
        if( $returnimagescollection->count() > 0 ){
            $returnimagescollection_data = array();

            foreach($returnimagescollection as $item){
//                $returnimagescollection_data[] = $item->getData();
                $returnimagescollection_data[$item['prd_id']] = $item['cancel_product_img'];
            }
            return $returnimagescollection_data;
            
//            if( count($returnimagescollection_data) > 0 ){
//                $images = array();
//                foreach( $returnimagescollection_data as $obj ){
////                    echo "<br>images are". $obj['cancel_product_img'];
//                    if( count($obj) > 0 ){
//                        
//                        $img_arr = explode(',', $obj['cancel_product_img']);
//                        foreach($img_arr as $imgs){
//                            if( trim($imgs) != '' ){
//                                $imgs = str_replace('/' , '' , $imgs);
//                                $images[] = $imgs;
//                            }
//                        }
//                    }
//                    exit;
//                }
//                return $images;
//            }
        }else{
//            echo "efew";
        }
        return false;
        
    }
    public function getCancelledOrderInfo($orderid){
        $collection = $this->cancelOrder->create()->getCollection()->addFieldToFilter('order_id', $orderid);
        $result = ['type' => '', 'accId' => ''];
        if( $collection->count() > 0 ){
            foreach($collection as $item)          
                $result['type'] = $item['refund_type'];
                $result['accId'] = $item['refund_account_id'];
           
        }
        return $result;
    }
    public function getAccounts($id){
        $collection = $this->refundType->create()->getCollection()->addFieldToFilter('id', $id);
        
        if( $collection->count() > 0 ){
            foreach($collection as $item){        
                if($item['method'] == 'refund_bank'){
                    $title = 'Bank transfer';
                    $Accdetails =  explode('||', $item['details']);
                    $accNumber = $Accdetails[0];
                    $ifsc = $Accdetails[1];
                  
                    echo "<strong> $title </strong> <br/>";
                   echo "<strong> Account number: </strong> &nbsp; $accNumber <br/>";
                   echo "<strong> ifsc code: </strong> &nbsp; $ifsc <br/>";
                   echo "<strong> Bank Name: </strong> &nbsp; $Accdetails[2] <br/>";
                   echo "<strong> Bank Branch: </strong> &nbsp; $Accdetails[3] <br/>";
                }
            }            
        }
    }
}