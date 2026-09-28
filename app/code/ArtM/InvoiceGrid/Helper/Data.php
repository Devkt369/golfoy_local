<?php
namespace ArtM\InvoiceGrid\Helper;

use Exception;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory;
use Magento\Sales\Model\Order\InvoiceFactory ;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Catalog\Api\Data\ProductInterfaceFactory as ProductFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Magento\Tax\Model\Calculation;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $invoiceCollectionFactory;
    protected $invoiceFactory;
    protected $dateTime;
    protected $productModel;
    protected $directoryList;
    protected $fileFactory;
    protected $filesystem;
    protected $taxCalculation;
    
    public function __construct(
        CollectionFactory $invoiceCollectionFactory,
        InvoiceFactory $invoiceFactory,
        DateTime $dateTime,
        ProductFactory $productModel,
        DirectoryList $directoryList,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        Calculation $taxCalculation,
        \Magento\Framework\App\Helper\Context $context,        
		array $data = []
    ){
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->invoiceFactory           = $invoiceFactory;
        $this->dateTime                 = $dateTime;
        $this->productModel             = $productModel;
        $this->directoryList            = $directoryList;
        $this->fileFactory              = $fileFactory;
        $this->filesystem               = $filesystem;
        $this->taxCalculation           = $taxCalculation;
		parent::__construct($context);
    }
    
    
    public function createCsv($from = null, $to = null)
    {   
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();         
        
        if(!$from){
            $formattedDate = date('Y-m-d', strtotime("-1 day"));
			$from          = $formattedDate." 00:00:00";
			$to            = $formattedDate." 23:59:59";
            //$from   = $this->dateTime->gmtDate('Y-m-d H:i:s', strtotime("-1 day"));
           // $to     = $this->dateTime->gmtDate('Y-m-d H:i:s');
            
        }
        
        $contentStock      = [];
        $contentInvoice    = [];
        $contentStock[]    = ["Invoice Number", "Item Name", "Item name /sku /article", "Godown",
                              "Quantity", "Unit", "Rate", "ITEM WISE BASIC VALUE", "Gst","Payment Method"];
        $contentInvoice[]  = ["Invoice Number", "Invoice Date", "Order Number", "Order Date", "Party Name", 
                             "Bill of supply", "Buyer Name", "Billing Address1", "Billing Address2", 
                             "Billing Pincode","Place  of supply", "Shipping Address1", "Shipping Address2", 
                             "Shipping Pincode", "gstin no", "payment method", "gift card adjusted (amount)", 
                             "Reward points", "Grand Total"];
        $collection = $this->invoiceCollectionFactory->create()
                             ->addAttributeToFilter('created_at', ['from' => $from, 'to' => $to]); // Filter invoices by date range
                            //  ->addAttributeToFilter('store_id', ['eq' => 1]);
                        // ->addAttributeToSelect('entity_id');

        if($collection->getSize() > 0){ 
            foreach($collection as $invoice){
               //$invoice = $this->invoiceFactory->create()->load(60721);
            //    echo "<pre>"; print_r( $invoice->getData()); echo "</pre>"; die;
              
               // billing address
               $billingAddress       = $invoice->getBillingAddress();
               $billingName          = $billingAddress->getName();
               $billingCompany       = $billingAddress->getCompany();
               $billingStreet        = implode(" \n ", $billingAddress->getStreet());
               $billingStreet        = str_replace("\n", "", $billingStreet);
               $billingCity          = $billingAddress->getCity();
               $billingRegion        = $billingAddress->getRegion();
               $billingPostcode      = $billingAddress->getPostcode();
               $billingTel           = $billingAddress->getTelephone();
               $billingGst           = ($billingAddress->getVatId()) ? $billingAddress->getVatId() : '';
              
               
               if($billingCompany   != ''){
                   $billingAddShow   = $billingCompany.', '.$billingStreet;
               }else{
                   $billingAddShow   = $billingStreet;
               }

               // shipping address
               $shippingName     = $shippingCompany = $shippingStreet = $shippingCity    = $shippingRegion =
               $shippingPostcode = $shippingTel     = $shippingGst    = $shippingAddShow = ''; 
                    
               $shippingAddress  = $invoice->getShippingAddress();                    
               if($shippingAddress){
                   $shippingName         = $shippingAddress->getName();
                    $shippingCompany      = $shippingAddress->getCompany();
                     $shippingStreet       = implode(" \n ", $shippingAddress->getStreet());
                      $shippingStreet       = str_replace("\n", "", $shippingStreet);
                       $shippingCity         = $shippingAddress->getCity();
                      $shippingRegion       = $shippingAddress->getRegion();
                     $shippingPostcode     = $shippingAddress->getPostcode();
                    $shippingTel          = $shippingAddress->getTelephone();
                   $shippingGst          = ($shippingAddress->getVatId()) ? $shippingAddress->getVatId() : '';
                   
                   if($shippingCompany  != ''){
                       $shippingAddShow  = $shippingCompany.', '.$shippingStreet;
                   }else{
                       $shippingAddShow  = $shippingStreet;
                   }
               }
               
                $invoicedategmt   = $invoice->getData('created_at');
                $orderdategmt     = $invoice->getOrder()->getCreatedAt();                       
                $invoicedate      = date('d-m-Y H:i:s', strtotime($invoicedategmt) + 19800); 
                $orderdate        = date('d-m-Y H:i:s', strtotime($orderdategmt) + 19800); 

                $payment              = $invoice->getOrder()->getPayment();
                //$paymentMethod      = $payment->getMethodInstance()->getTitle();
                $paymentMethod        = $payment->getMethodInstance()->getCode();

                //for invoice
                $giftcardAdjustment = 0;
                if($invoice->getGiftCardAmount()){
                    $giftcardAdjustment = $giftcardAdjustment + $invoice->getGiftCardAmount();
                }
                if($invoice->getGiftCreditAmount()){
                    $giftcardAdjustment = $giftcardAdjustment + $invoice->getGiftCreditAmount();
                }
                $gistadj = '';
                if($giftcardAdjustment != 0){
                    $gistadj = $giftcardAdjustment * -1;
                }
                $partyName = 'Golfoy';
                if($billingGst != ''){
                    if($billingCompany !=''){
                        $partyName = $billingCompany;
                    }                    
                    if($partyName == 'Golfoy'){
                        if($billingName !=''){
                            $partyName = $billingName;
                        }  
                    }
                }
                
                $shipadd2 = '';
                if($shippingRegion != '' &&  $shippingCity != ''){ 
                    $shipadd2 = $shippingCity. ', '. $shippingRegion;
                }                
                $discountAmount = 0;      
                $discountAmount = $invoice->getData('gift_voucher_discount') + $invoice->getData('base_discount_amount');
                $customGrandTotal = $invoice->getData('subtotal_incl_tax') + $discountAmount - $invoice->getMpRewardDiscount();
                $contentInvoice[] = [
                    $invoice->getIncrementId(),
                    $invoicedate,
                    $invoice->getOrder()->getIncrementId(),
                    $orderdate,
                    $partyName, 
                    $billingRegion, 
                    $invoice->getOrder()->getCustomerName(),                    
                    $billingStreet,
                    $billingCity. ', '. $billingRegion,  
                    $billingPostcode,                      
                    $shippingRegion,
                    $shippingStreet,
                    $shipadd2, 
                    $shippingPostcode,  
                    $billingGst,
                    $paymentMethod,
                    // $discountAmount,
                    $invoice->getData('gift_voucher_discount'), //$gistadj,
                    $invoice->getMpRewardDiscount(),
                    $customGrandTotal
                ];


                //for stock
                $items     = $invoice->getItems();
                $cmpArray        = [];
                foreach($items as $item) {
                    $pid = $item->getProductId();
                    $currProduct = $this->productModel->create()->load($pid);
                    $typeid      = $currProduct->getTypeId();
                    if($typeid  == 'mpgiftcard'){$typeid = 'simple';}
                    $itemname    = html_entity_decode($item->getName());
                    $itemname    = str_replace("’", "'", $itemname);
                    $cmpArray[$item->getSku()][$typeid] = $itemname;
                }

                
                foreach($items as $item) {
                    $pid                  = $item->getProductId();
                    $currProduct          = $this->productModel->create()->load($pid);
                    

                    if(($currProduct->getVisibility() == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH && ($currProduct->getTypeId() == 'simple' || $currProduct->getTypeId() == 'giftvoucher'))
                       || $currProduct->getTypeId() == 'configurable' || $currProduct->getTypeId() == 'customsale'  || ($currProduct->getTypeId() == 'simple' && $currProduct->getVisibility() == 1 &&
                       $item->getRowTotal() && $item->getRowTotalInclTax()))
                    {
                        $taxClassId       = $currProduct->getTaxClassId();       
                        $request          = $this->taxCalculation->getRateRequest();
                        $request->setProductClassId($taxClassId);
                        $taxPercentage    = $this->taxCalculation->getRate($request);
                        $taxrate          = $taxPercentage / 100;
                        // echo '<pre>';
                        // print_r($item->getData());
                        // die;
                        $rowTotal = (float) $item->getRowTotal();
                        $rewardDiscount = abs((float) $item->getData('mp_reward_discount'));
                        $couponDiscount = abs((float) $item->getData('discount_amount'));
                        $taxCompensation = (float) $item->getData('discount_tax_compensation_amount');

                        $baseAmount = $rowTotal + $taxCompensation - $couponDiscount - $rewardDiscount;
                        if ($baseAmount < 0) {
                            $baseAmount = $rowTotal;
                        }

                        $contentStock[] = [
                            $invoice->getIncrementId(),
                            $cmpArray[$item->getSku()]['simple'] ?? $item->getName(),
                            $item->getSku(),
                            'Main Location',                           
                            $item->getQty(),
                            '',
                            '',
                            $baseAmount,   
                            $taxrate,
                            $paymentMethod
                        ];
                    }
                }
            }
           
         };

        // create csv
        $directory     = $this->filesystem->getDirectoryWrite(AppDirectoryList::VAR_DIR);
        $foldername    = "invoices";
        if (!$directory->isDirectory($foldername)) {
            $directory->create($foldername);}  

        date_default_timezone_set('Asia/Kolkata');   
        $timestamp     = time(); 
        $datestr       = date('Ymd_His', $timestamp); 

        $fileName      = 'export_invoice_'.$datestr.'.csv'; 
        $filePath      = $foldername.'/' . $fileName;
        $file          = $directory->openFile($filePath, 'w+');
        
        $fileNameStock      = 'export_stock_'.$datestr.'.csv'; 
        $filePathStock      = $foldername.'/' . $fileNameStock;
        $fileStock          = $directory->openFile($filePathStock, 'w+');
        
        foreach($contentInvoice as $data){            
            $file->writeCsv($data);
        }
        $file->close();

        foreach($contentStock as $data){            
            $fileStock->writeCsv($data);
        }
        $fileStock->close();

        return $fileName.', '.$fileNameStock;     
    }
}