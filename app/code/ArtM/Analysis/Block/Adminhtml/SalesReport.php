<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\Analysis\Block\Adminhtml;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Framework\File\Csv;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use ArtM\Analysis\Helper\Data;

class SalesReport extends \Magento\Backend\Block\Template
{    
	protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $productRepository;
    protected $sourceItemsRepository;
    protected $sourceItemsCriteriaFactory;
    protected $stockState;
    protected $stockItemRepository;
    protected $helperData;
    protected $getSalableQuantityDataBySku;
    private $csvProcessor;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        OrderRepositoryInterface $orderRepository,
        StockStateInterface $stockState,
        StockItemRepository $stockItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        Filesystem $filesystem, 
        Data $helperData,    
        SourceItemRepositoryInterface $sourceItems,
        \Magento\InventoryCatalogAdminUi\Model\GetSourceItemsDataBySku $sourceDataBySku,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        Csv $csvProcessor,     
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderRepository = $orderRepository;
        $this->stockState = $stockState;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->filesystem = $filesystem;
        $this->stockItemRepository = $stockItemRepository;
        $this->helperData = $helperData;       
        $this->sourceDataBySku = $sourceDataBySku;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->csvProcessor = $csvProcessor;
        $this->sourceItems = $sourceItems;
        parent::__construct($context, $data);
    }
    
    public function getProductIdBySku($sku)
    {
        try {
            $product = $this->productRepository->get($sku);
            $productId = $product->getId();

            return $productId;
        } catch (NoSuchEntityException $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    

    /**
     * Get all invoice IDs for an order.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function getAllInvoiceIds($order)
    {
        $invoiceIds = [];
        foreach ($order->getInvoiceCollection() as $invoice) {
            $invoiceIds[] = $invoice->getIncrementId();
        }
        return $invoiceIds;
    }

    /**
     * Get all shipment IDs for an order.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function getAllShipmentIds($order)
    {
        $shipmentIds = [];
        foreach ($order->getShipmentsCollection() as $shipment) {
            $shipmentIds[] = $shipment->getIncrementId();
        }
        return $shipmentIds;
    }

    protected function getShipmentByOrderItem($order)
    {
        $shipmentMap = [];
        foreach ($order->getShipmentsCollection() as $shipment) {
            $shipmentIncrementId = $shipment->getIncrementId();
            foreach ($shipment->getAllItems() as $shipmentItem) {
                $shipmentMap[$shipmentItem->getOrderItemId()] = $shipmentIncrementId;
            }
        }
        return $shipmentMap;
    }

    /**
     * Get the ID of the first credit memo associated with the order.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return int|null
     */
    protected function getCreditMemoId($order)
    {
        $creditMemo = $order->getCreditmemosCollection()->getFirstItem();
        if ($creditMemo->getIncrementId()) {
            return $creditMemo->getIncrementId();
        }
        return null;
    }

    public function getOrderProducts($order_id){
        $shipmentAll = '';
        $invoiceAll = '';
        $i = 0;
        $product_data = array();
        $invoice = '';
        $shipment = '';
        $order = $this->orderRepository->get($order_id);


        $orderIncId = $order->getIncrementId(); 
        $orderItems = $order->getAllItems();

        $invoices = $this->getAllInvoiceIds($order);
        $in = 0;
        foreach ($invoices as $invoice) {
            if($in == 1){
                $invoiceAll .= ' , '.$invoice;
            }else{
                $invoiceAll = $invoice;
            }
            $in++;
        }

        $shipmentMap = $this->getShipmentByOrderItem($order);

        foreach ($orderItems as $item) {
            $productName = '';
            $data = $item->getProductOptions();
            $productId = $item->getProductId();
              
            if(isset($data['info_buyRequest'])){
                $prd_data = $data['info_buyRequest'];
            }else{
                $prd_data['product'] = '';
            }
            $options = [];
            $productOptions = $item->getProductOptions();
            if (isset($productOptions['attributes_info'])) {
                foreach ($productOptions['attributes_info'] as $attribute) {
                    $options[] = $attribute['label'] . ': ' . $attribute['value'];
                }
            }
            if (isset($productOptions['options'])) {
                foreach ($productOptions['options'] as $option) {
                    $options[] = $option['label'] . ': ' . $option['value'];
                }
            }

            if (isset($productOptions['additional_options'])) {
                foreach ($productOptions['additional_options'] as $option) {
                    $options[] = $option['label'] . ': ' . $option['value'];
                }
            }

            if (!empty($options)) {
                $productName .= ' [' . implode(', ', $options) . ']';
            }

            $single_prd = array();
            if( (int)$item->getPrice() != 0 ){
                $single_prd['prd_id'] = $prd_data['product'] ?? '';
                $single_prd['sku'] = $item->getSku();
                $single_prd['name'] = $item->getName().$productName;
                $single_prd['qtyordered'] = $item->getQtyOrdered();
                $single_prd['price'] = $item->getPrice();
                $single_prd['rowtotal'] = $item->getRowTotal();
                $single_prd['simple_prd_id'] = $this->getProductIdBySku( $single_prd['sku'] );
                $shipmentId = $shipmentMap[$item->getItemId()] ?? '';
                
                $stock_info = array();
                $stock_info = $this->getSourceQty( $item->getSku() );

                $source_list =  $this->helperData->getSourceName();   

                $product_data_single = array();
                array_push($product_data_single, $orderIncId);
                array_push($product_data_single, $order->getCreatedAt());
                array_push($product_data_single, $order->getState());
                array_push($product_data_single, $order->getStatus());
                array_push($product_data_single, $this->getCreditMemoId($order));
                array_push($product_data_single, $single_prd['qtyordered']);
                array_push($product_data_single, $single_prd['prd_id']);
                array_push($product_data_single, $single_prd['sku']);
                array_push($product_data_single, $single_prd['name']);
                array_push($product_data_single, $invoiceAll);
                array_push($product_data_single, $shipmentId);                
                $matchedSources = [];
                foreach ($source_list as $sourceCode => $sourceName) {
                    $matchedSources[$sourceName] = null;
                }

                if(is_array($stock_info)){
                    foreach ($stock_info as $info) {
                        $sourceCode = $info['source_code'];
                        $sku = $info['sku'];
                        $quantity = $info['quantity'];

                        if (isset($source_list[$sourceCode])) {
                            $sourceName = $source_list[$sourceCode];
                            $matchedSources[$sourceName] += $quantity;
                        }
                    }
                }
                $product_data[] = array_merge($product_data_single , $matchedSources);
                $stock_data = array();
                $i++;                
            }
        }
        return $product_data;
    }
    
    public function getSalesDate(){
        $i = 0;
        $data = $this->getData('formdata');
        $startDate = $data['0'] ?? '';
        $endDate = $data['1'] ?? '';
        $order_all = [];
        $order_data = [];
        $ordered_products = [];
        if($startDate > 0 && $endDate > 0){
            
            $order_Status_to_skip = ['canceled'];
            $endDate = $endDate.' 23:59:59';
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('created_at', $startDate, 'gteq')
                ->addFilter('created_at', $endDate, 'lteq')
                ->create();

            $orders = $this->orderRepository->getList($searchCriteria)->getItems();
                
                if ($orders) {
                    
                    echo "Order IDs between $startDate and $endDate:<br>";
                   
                    foreach ($orders as $order) {
                        
                        $foundorderstatus = array_search($order->getStatus(), $order_Status_to_skip);
                        if ($foundorderstatus !== false) {
                            continue;
                        }
                        
                        $order_data['order_id'] = $order->getIncrementId();
                        $order_data['order_status'] = $order->getStatus();
                        
                        $ordered_products[] = $this->getOrderProducts( $order->getId() );
                        
                        $order_all[] = $order_data;
                        
                    }

                    $directory     = $this->filesystem->getDirectoryWrite(AppDirectoryList::VAR_DIR);
                    $foldername    = "sales";
                    if (!$directory->isDirectory($foldername)) {
                        $directory->create($foldername);
                    }  

                    date_default_timezone_set('Asia/Kolkata');   
                    $timestamp     = time(); 
                    $datestr       = date('Ymd_His', $timestamp); 

                    $fileName      = 'export_sales_'.$datestr.'.csv'; 
                    $filePath      = $foldername.'/' . $fileName;
                    $file          = $directory->openFile($filePath, 'w+');
                    // $data = ["Order","Product Id","Product Sku","Product Name","Panchkula Office","Bhaseen Sports Pvt Ltd","Callaway Golf India Pvt Ltd","Chandigarh Golf Club","Custom Order","Default Warehouse ","Golfoy Warehouse ","Golfking Corporation","Brightex Synergizer India Pvt Ltd","J&B Golf","Kudu Fabrics","Karma","Kanji Sports","Returns Rack (G5)","Second Chance","Touche Golf Sports Private Limited","Trinity Golf India Pvt. Ltd.","Underdog Athletics India Pvt Ltd","US Kids Golf India Pvt Ltd","Zaverchand Sports Private Limited","Default Stock","CGC","Stock Available","Experience studio"];
                    $data = ["Order","Order Date","State","Status","credit_memo_id","Ordered Qty","Product Id","Product Sku","Product Name","Invoices","Shipment"];
                    $source_list =  $this->helperData->getSourceName();  
                    $product_data = array_merge($data , $source_list);                    
                    
                    $file->writeCsv($product_data);
                    $data = '';
                    foreach($ordered_products as $data){ 
                        foreach($data as $value){
                            $file->writeCsv($value);
                        }   
                    }
                    $file->close();

                } else {
                    echo "No orders found between $startDate and $endDate.";
                }
            return $order_all;
        }else{
            return false;
        }
    }

    public function getSourceQty($sku){
        $sourceStock = [];
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('sku', $sku)->create();
        $sourceItemData = $this->sourceItems->getList($searchCriteria);
        if ($sourceItemData->getItems()) {
            foreach ($sourceItemData->getItems() as $sourceItem) {
                $sourceStock[] = $sourceItem->getData();
            }
            return $sourceStock;
        }
    }
}

