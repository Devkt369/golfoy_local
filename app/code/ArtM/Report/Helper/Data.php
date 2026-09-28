<?php
namespace ArtM\Report\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\Order\InvoiceFactory;

class Data extends AbstractHelper
{  
	protected $orderCollectionFactory;
	protected $invoiceFactory;
    public function __construct(
        InvoiceFactory $invoiceFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\App\Helper\Context $context)
    {	
		$this->orderCollectionFactory = $orderCollectionFactory;
        $this->invoiceFactory = $invoiceFactory;
		parent::__construct($context);
    }
    public function getorders($from , $to){

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        
        $return = [];
        $total = 0;
        $returns = [];
        $ordercollection = $this->orderCollectionFactory->create()
            ->addFieldToSelect('*')
           ->addFieldToFilter('created_at', ['from'=>$from, 'to'=>$to])
            ->setOrder('increment_id','ASC');
            $total = $ordercollection->getTotalCount();
        if ($ordercollection->getTotalCount() > 0) {           
            $orderinstance = $objectManager->create('Magento\Sales\Model\Order');            
            $orders = $ordercollection->getItems();
            $i=0;
            foreach ($orders as $order) {
               $id = $order->getId();
               $return[$i]['order_id'] = $order->getIncrementId();         
               $return[$i]['order_date'] = $order->getCreatedAt();                
               $return[$i]['order_state'] = $order->getState();
               $return[$i]['customername'] = $order->getCustomerName();
               $return[$i]['total_qty'] = $order->getData('total_qty_ordered');
               $return[$i]['subtotal'] = $order->getData('subtotal_incl_tax');
               $return[$i]['grandtotal'] = $order->getData('grand_total');
               $return[$i]['street'] =   '';
               $return[$i]['city'] =  '';
               $return[$i]['state'] =  '';
               $return[$i]['postcode'] =  '';
               $shipping_address = $order->getShippingAddress();
               if($shipping_address){
                   $street = '';
                   $stadd = $shipping_address->getStreet();
                   if(is_array($stadd)){
                        $street = implode(',',$stadd); 
                   }
                   $return[$i]['street'] =   $street;
                   $return[$i]['city'] =  $shipping_address->getcity();
                   $return[$i]['state'] =  $shipping_address->getRegion();
                   $return[$i]['postcode'] =  $shipping_address->getPostcode();
               }

               $invoiceid = [];
               $invoiceCreated = [];
               $invoiceGrandTotal = [];
               $invoicesubTotal = [];
               $invoiceids = '';
               foreach ($order->getInvoiceCollection() as $invoice)
                    {
                        $invoiceCreated[] = $invoice->getCreatedAt();
                        $invoiceid[] = $invoice->getIncrementId();
                        $invoiceGrandTotal[] = $invoice->getGrandTotal();
                        $invoicesubTotal[] = $invoice->getSubtotalInclTax();
                    }
                    $return[$i]['invoiceids'] = implode('<br/>', $invoiceid); 
                    $return[$i]['invoicecreated'] = implode('<br/>', $invoiceCreated); 
                    $return[$i]['invoicegrandtotal'] = implode('<br/>', $invoiceGrandTotal); 
                    $return[$i]['invoicesubtotal'] = implode('<br/>', $invoicesubTotal); 
                
                $shipmentid = [];
                $shipmentids = '';
                $title = [];
                $titles = '';
                $tracknumber = [];
                $tracknumbers = '';
                foreach($order->getShipmentsCollection() as $shipment){
                    $shipmentid[] = $shipment->getIncrementId();
                    $sql = "SELECT * FROM sales_shipment_track WHERE order_id = ".$shipment->getOrderId();
                    $result   = $connection->fetchAll($sql);
                    if(!empty($result)){
                        foreach($result as $v){
                            $title[] = $v['title'];
                            $tracknumber[] = $v['track_number'];
                        }

                    }
                }
                $return[$i]['shipmentids'] = implode('<br/>', $shipmentid); 
                $return[$i]['titles'] = implode('<br/>', array_unique($title)); 
                $return[$i]['tracknumbers'] = implode('<br/>', array_unique($tracknumber)); 
                $i++; 
            }
        }
        $returns = ['records' => $return, 'total' => $total];
        return $returns;
    }

    public function getShipmentId($order){
        try{
            $shipmentid = [];
            foreach($order->getShipmentsCollection() as $shipment){
                $shipmentid[] = $shipment->getIncrementId();
            }
            return $return['shipmentids'] = implode('<br/>', $shipmentid);
        }catch( Exception $e){
            return 'empty';
        }
    }
           
    public function getTrackingNumberByShipmentid($order){
        try{
            $shipmentid = [];
            $shipmentids = '';
            $title = [];
            $titles = '';
            $tracknumber = [];
            $tracknumbers = '';
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            foreach($order->getShipmentsCollection() as $shipment){
                $shipmentid[] = $shipment->getIncrementId();
                $sql = "SELECT * FROM sales_shipment_track WHERE order_id = ".$shipment->getOrderId();
                $result   = $connection->fetchAll($sql);
                if(!empty($result)){
                    foreach($result as $v){
                        $tracknumber[] = $v['track_number'];
                    }
                }
            }
            return $tracknumber = implode('<br/>', array_unique($tracknumber)); 
        }catch( \Exception $e ){
            return 'empty';
        }
    }
    
        
    public function getCourierName($order){
        try{
            $shipmentid = [];
            $shipmentids = '';
            $title = [];
            $titles = '';
            $courierTitle = [];
            $tracknumbers = '';
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            foreach($order->getShipmentsCollection() as $shipment){
                
                $shipmentid[] = $shipment->getIncrementId();
                $sql = "SELECT * FROM sales_shipment_track WHERE order_id = ".$shipment->getOrderId();
                $result   = $connection->fetchAll($sql);
                if(!empty($result)){
                    foreach($result as $v){
                        $courierTitle[] = $v['title'];
                    }
                }
            }
            return $courierTitle = implode('<br/>', array_unique($courierTitle)); 
        }catch( \Exception $e ){
            return 'empty';
        }
    }

    public function getInvoiceDataByDate($startDate , $endDate)
    {
        $shiptoName = 'Empty';
        $invoiceData = [];
        $total = 0;
        try {
            $invoices = $this->invoiceFactory->create()
                ->getCollection()
                ->addAttributeToFilter('created_at', ['from' => $startDate, 'to' => $endDate]);

            foreach ($invoices as $invoice) {     
                $total++;
                $order = $invoice->getOrder();
                try{
                    if($order->getShippingAddress()){
                        $shiptoName = $order->getShippingAddress()->getName();
                        $streetName = $order->getShippingAddress()->getStreet();
                        $cityName = $order->getShippingAddress()->getCity();
                        $regionName = $order->getShippingAddress()->getRegion();
                        $postCode = $order->getShippingAddress()->getPostcode();
                        $shipingtitle = $order->getShippingDescription();
                        $courierTitle = $this->getCourierName($order);
                    }else{
                        $shiptoName = 'Empty';
                        $streetName = 'Empty';
                        $postCode = 'Empty';
                        $cityName = 'Empty';
                        $regionName = 'Empty';
                        $shipingtitle = 'Empty';
                        $courierTitle = 'Empty';
                    }
                }catch( \Exception $e ){
                    $shiptoName = 'Empty';
                    $streetName = 'Empty';
                    $postCode = 'Empty';
                    $cityName = 'Empty';
                    $regionName = 'Empty';
                    $shipingtitle = 'Empty';
                    $courierTitle = 'Empty';
                }
                if(is_array($streetName)){
                    $streetName = implode(", ",$streetName);    
                }

                $invoiceData[] = [
                    'invoice_id' => $invoice->getIncrementId(),
                    'created_at' => $invoice->getCreatedAt(),
                    'ship_to' => $shiptoName,
                    'street' => $streetName,
                    'city' => $cityName,
                    'state' => $regionName,
                    'postcode' => $postCode,
                    'order_status' => $order->getStatus(),
                    'total_qty' => $invoice->getTotalQty(),
                    'subtotal_incl_tax' => $invoice->getBaseSubtotalInclTax(),
                    'grand_total' => $invoice->getBaseGrandTotal(),
                    'shipmentids' => $this->getShipmentId($order),
                    'courier_title' => $courierTitle,
                    'shipment_title' => $shipingtitle,
                    'tracking_number' => $this->getTrackingNumberByShipmentid($order),
                ];
            }
        } catch (\Exception $e) {
            // Handle exception as needed
            $this->_logger->error($e->getMessage());
        }

        $invoiceData = ['records' => $invoiceData, 'total' => $total];
        return $invoiceData;
       }
	  
}