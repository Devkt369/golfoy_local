<?php
namespace ArtM\RecommendedFilter\Helper;


use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use ArtM\RecommendedSort\Model\SortingCategoryProductsFactory;
use ArtM\RecommendedSort\Logger\Logger;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    
    const MAX_POINTS = 10;
    const MAX_POINTS_FOR_AVAILABILITY = 10;
    const MAX_POINTS_FOR_AVAILABILITY_INTERNAL_SOURCE = 10;
    
    const MAX_POINTS_FOR_ISFEATURED = 10;
    
    const PRIOR_SIZEPOINT = 1;
    const PRIOR_AVAILABILITY_GOLFOY = 2;
    const PRIOR_AVAILABILITY_CGC = 3;
    const PRIOR_AVAILABILITY_OTHER = 4;
    const PRIOR_RATING = 5;
    const PRIOR_HIGH_DISCOUNT = 6;
    const PRIOR_LATEST_UPDATE = 7;
    
    
    const TBL_CRED                       = "artm_sorting_cred";
    const TBL_SORT_CRON                  = "artm_sorting_cron_log";
    const TBL_SORT_PROCESS               = "artm_sorting_processed_products";
    const TBL_SORT_PROCESS_TMP           = "artm_sorting_processed_products_temp";
    const TBL_SORT_PRODUCTS              = "artm_sorting_products";
    protected $_productloader;
    protected $attributeSet;
    protected $sizeroll_helper;
    protected $productpriceblock;
    protected $ArtmSortingProducts;
    protected $connection;
    protected $productCollectionFactory;
    protected $sourceItems;
    protected $searchCriteriaBuilder;
    protected $getSalableQuantityDataBySku;
    protected $_Logger;
    protected $artmhelper;
    protected $SourceItemInterface;
    protected $reviewFactory;
    protected $sortingCategoryProductsFactory;
    protected $logger;

    public function __construct(
        \Magento\Catalog\Model\ProductFactory $_productloader,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSet,
        \ArtM\Sizeroll\Helper\Data $sizeroll_helper,
        \ArtM\Common\Block\Productpriceblock $productpriceblock,
        \Magento\InventoryApi\Api\Data\SourceItemInterface $SourceItemInterface,
        \ArtM\RecommendedFilter\Model\ArtmSortingProductsFactory $ArtmSortingProducts,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        SourceItemRepositoryInterface $sourceItems,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        \ArtM\RecommendedFilter\Logger\Logger $customLogger,
        SortingCategoryProductsFactory $sortingCategoryProductsFactory,
        \ArtM\Common\Helper\Data $helper,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        Logger $logger,
        array $data = []
    ) {
        $this->_productloader              = $_productloader;
        $this->attributeSet                 = $attributeSet;
        $this->sizeroll_helper              = $sizeroll_helper;
        $this->productpriceblock            = $productpriceblock;
        $this->ArtmSortingProducts          = $ArtmSortingProducts;
        $this->connection                   = $resourceConnection->getConnection();
        $this->productCollectionFactory     = $productCollectionFactory;
        $this->sourceItems                  = $sourceItems;
        $this->searchCriteriaBuilder        = $searchCriteriaBuilder;
        $this->getSalableQuantityDataBySku  = $getSalableQuantityDataBySku;
        $this->_Logger                      = $customLogger;
        $this->artmhelper                   = $helper;
        $this->SourceItemInterface          = $SourceItemInterface;
        $this->reviewFactory                = $reviewFactory;
        $this->sortingCategoryProductsFactory = $sortingCategoryProductsFactory;
        $this->logger                       = $logger;
        // The parent::__construct call should typically be the first line,
        // but keeping it as is to match your original structure.
        parent::__construct($context, $data);
    }


    public function RunForSingleProduct( $prd_id, $filters, $logFileName = null ) {
        try{
            $this->UpdatePoints($prd_id, 'observer', $filters);            
            $this->CalculatePoints( $prd_id, $logFileName);
        }catch(\Exception $e){

        }
        return;
        
    }


    public function RunForSingleProductd($prd_id, $filters) {


    echo "Product ID: " . $prd_id . "\n";


    // Convert JSON string to array
    $filterArray = json_decode($filters, true);

    if (!is_array($filterArray)) {
        // echo "Invalid filter format.";
        return;
    }

    foreach ($filterArray as $filter) {
        switch ($filter) {
            case 'created_date':
                $this->getPointsForCreatedDate($prd_id);
                break;

            case 'high_discount':
                $this->getPointsForHighDiscount($prd_id);
                break;

            case 'salable_qty':
                $this->getPointsForSalableQty($prd_id);
                break;

            case 'availability_compare':
                $this->getPointsForAvailabilityCompare($prd_id);
                break;

            case 'feature':
                $this->getPointsForFeature($prd_id);
                break;

            case 'size_roll':
                $this->getPointsForSizeRoll($prd_id);
                break;

            default:
                // echo "Unknown filter: $filter\n";
        }
    }

    return;
}


public function getPointsForCreatedDate($prd_id) {
    echo "→ Called: getPointsForCreatedDate for product $prd_id\n";
}

public function getPointsForHighDiscount($prd_id) {
    echo "→ Called: getPointsForHighDiscount for product $prd_id\n";
}

public function getPointsForSalableQty($prd_id) {
    echo "→ Called: getPointsForSalableQty for product $prd_id\n";
}

public function getPointsForAvailabilityCompare($prd_id) {
    echo "→ Called: getPointsForAvailabilityCompare for product $prd_id\n";
}

public function getPointsForFeature($prd_id) {
    echo "→ Called: getPointsForFeature for product $prd_id\n";
}

    public function getPointsForSizeRoll($prd_id) {
        // echo "→ Called: getPointsForSizeRoll for product $prd_id\n";
        $this->GetSizeRoll( $prd_id );
    }








    public function getSourceItemDetailBySKU(string $sku): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter("sku", $sku)
            ->create();

        $result = $this->sourceItems->getList($searchCriteria)->getItems();

        return $result;
    }
    
    
    public function CalculatePointsForAttribute($total , $caclulated){
        //calculate points out of 10
        $pointsoutof = 10;
        $pointsoutoften = round( (($caclulated/$total) * $pointsoutof) ,2);
        return $pointsoutoften;
    }
    public function GetAttributeSetName($prd_id){
         try {
            $product = $this->_productloader->create()->load($prd_id);
            $attributeSetRepository = $this->attributeSet->get( $product->getAttributeSetId() );
            $attr_set_name = $attributeSetRepository->getAttributeSetName();
            return $attr_set_name;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // Product does not exist
            return "not found";
        }
            
    }
    public function ShowGetSizeRoll(){
        $isSizerollEnabled = $this->artmhelper->getConfig('prioritycriteria/general/sizeroll');
//        $isSizerollEnabled = 1;
        if( $isSizerollEnabled != 1 ) {
            $this->_Logger->info( 'ShowGetSizeRoll not enable');
            return false;
        }
        return true;
        
    }
    public function ShowGetHighestPoints(){
        $isHighestPointsEnabled = $this->artmhelper->getConfig('prioritycriteria/general/discount');
//        $isHighestPointsEnabled = 1;
        if( $isHighestPointsEnabled != 1 ) {
            $this->_Logger->info( 'ShowHighestPoints not enable');
            return false;
        }
        return true;
        
    }
    public function ShowGetLatestUpdatePoint(){
        $isLatestUpdatePointEnabled = $this->artmhelper->getConfig('prioritycriteria/general/latest');
//        $isLatestUpdatePointEnabled = 1;
        if( $isLatestUpdatePointEnabled != 1 ) {
            $this->_Logger->info( 'ShowLatestUpdatePoint not enable');
            return false;
        }
        return true;
        
    }
    public function ShowGetAvailabilityQty(){
        $isAvailabilityQtyEnabledcgc = $this->artmhelper->getConfig('prioritycriteria/general/cgc_warehouse');
        $isAvailabilityQtyEnabledother = $this->artmhelper->getConfig('prioritycriteria/general/other_warehouse');
//        $isAvailabilityQtyEnabled = 1;
        if( $isAvailabilityQtyEnabledcgc != 1 && $isAvailabilityQtyEnabledother != 1 ) {
            $this->_Logger->info( 'ShowAvailabilityQty not enable');
            return false;
        }
        return true;
        
    }
    
    public function GetAllCategories( $prd_id ){
        $product = $this->_productloader->create()->load($prd_id);
        $proCats = $product->getCategoryIds();    
        $cat_html = '';
        foreach($proCats as $catid){
            if($cat_html == ''){
                $cat_html = $catid;
            }else{
                $cat_html = $cat_html.",".$catid;
            }
        }
        return $cat_html;
        //print_r($proCats);
        
    }
    public function AddDatainArtmSortingProducts( $prd_id, $attribute, $data ){
    //    echo "<br>------------------------------------------------------------------<br>";
    //    echo "<br>calling AddDatainArtmSortingProducts";
    //    echo "<br>".print_r($data);
        $ArtmSortingProductsobj = $this->ArtmSortingProducts->create();
        $ArtmSortingProductscollection = $ArtmSortingProductsobj
            ->getCollection()
            ->addFieldToFilter('product_id', $prd_id);
        
        if( $ArtmSortingProductscollection->count() == 0 ){
            
            $attr_set_name = $this->GetAttributeSetName($prd_id);
            
            $all_categories = $this->GetAllCategories($prd_id);
            
            $ArtmSortingProductsobj->addData([
                "product_id" => $prd_id,
                "attr_set_name" => $attr_set_name,
                "cat_id" => $all_categories
            ]);
            $ArtmSortingProductsobj->save();
            
            $ArtmSortingProductscollection = $ArtmSortingProductsobj
            ->getCollection()
            ->addFieldToFilter('product_id', $prd_id);
        }
        
        
        if( $ArtmSortingProductscollection->count() > 0 ){
            foreach($ArtmSortingProductscollection as $item){
                $ArtmSortingProductsobj->load( $item['id'] );
                if($attribute == 'sizeroll'){
                    //echo "<br>Attribute is ".$attribute;
                    //echo "<br>points_for_sizeroll: ".$data['points_for_sizeroll'];
                    $ArtmSortingProductsobj->setSizeRollPoints( $data['points_for_sizeroll'] );
                    $ArtmSortingProductsobj->setAvailableSizeRollValue( $data['html_for_sizeroll'] );
                }else if( $attribute == 'highestpoints' ){
                    $ArtmSortingProductsobj->setHighDiscountPoints( $data['points_for_highestpoints'] );
                }else if( $attribute == 'date_created' ){
                    $ArtmSortingProductsobj->setCreatedDatePoints( $data['points_for_created_date'] );
                }else if( $attribute == 'last_updated' ){
                    //echo "updating points now: ". $data['points_for_last_updated']. " for ". $prd_id;

                    $ArtmSortingProductsobj->setLatestUpdatedPoints( $data['points_for_last_updated'] );
                }else if( $attribute == 'available_qty' ){
                    
                //    echo "<br>Printing data: ";
//                    print_r($data);
                    $ArtmSortingProductsobj->setAvailabilityGolfoy( $data['points_for_golfoy'] );
                    $ArtmSortingProductsobj->setAvailabilityCgc( $data['points_for_cgc'] );
//                    $ArtmSortingProductsobj->setAvailabilityOther( $data['points_for_other'] );
                    $ArtmSortingProductsobj->setAvailabilityOther( $data['availability_saleable'] );
                //    echo 'abc-availability_saleable'.$data['availability_saleable'];
//                    $ArtmSortingProductsobj->setAvailabilitySaleable( $data['availability_saleable'] );
                    
//                    $ArtmSortingProductsobj->setAvailabilitySaleable( 12 );
                //    echo "Saved: ".$data['availability_saleable'];
                }else if( $attribute == 'isFeaturedTextPoints' ){
                    $ArtmSortingProductsobj->setIsFeature( $data['isFeaturedTextPoints'] );
                }else if( $attribute == 'rating' ){
                    $ArtmSortingProductsobj->setRating( $data['rating'] );
                }
                $ArtmSortingProductsobj->save();
            }
        }
    }
    public function UpdatePoints( $prd_id, $type = 'observer' , $filter = '' ){
        $this->_Logger->info( '----------------Updating Points for '.$prd_id."-------------------");
        
        //this function will update below column in table artm_sorting_products //id //product_id //attr_set_name  //size_roll_points    //available_size_roll_value
//        if( $this->ShowGetSizeRoll() ){
        $filters = json_decode($filter, true);
        // return;
        // Ensure it's a valid array
        // if (!is_array($filters)) {
        //     $this->_Logger->info("❌ Invalid filters input: " . $filtersJson);
        //     return;
        // }

        // Normalize: If empty, run all
        $runAll = empty($filters);

        // size_roll
        if ($runAll || in_array('size_roll', $filters)) {
            $this->_Logger->info('GetSizeRoll for ' . $prd_id);
            $this->GetSizeRoll($prd_id);
            $this->_Logger->info('Calculated GetSizeRoll for ' . $prd_id);
        }

        // high_discount
        if ($runAll || in_array('high_discount', $filters)) {
            $this->_Logger->info('GetHighestPoints for ' . $prd_id);
            $this->GetHighestPoints($prd_id);
            $this->_Logger->info('Calculated GetHighestPoints for ' . $prd_id);
        }

        // updated_date
        if ($runAll || in_array('updated_date', $filters)) {
            $this->_Logger->info('GetLatestUpdatePoint for ' . $prd_id);
            $this->GetLatestUpdatePoint($prd_id);
            $this->_Logger->info('Calculated GetLatestUpdatePoint for ' . $prd_id);
        }

        // created_date
        if ($runAll || in_array('created_date', $filters)) {
            $this->_Logger->info('GetCreatedDatePoint for ' . $prd_id);
            $this->GetCreatedDatePoint($prd_id);
            $this->_Logger->info('Calculated GetCreatedDatePoint for ' . $prd_id);
        }

        // availability_compare
        if ($runAll || in_array('availability_compare', $filters)) {
            $this->_Logger->info('GetAvailabilityQty for ' . $prd_id);
            $this->GetAvailabilityQty($prd_id);
            $this->_Logger->info('Calculated GetAvailabilityQty for ' . $prd_id);
        }
        
        // rating
        if ($runAll || in_array('rating', $filters)) {
            $this->_Logger->info('Rating for ' . $prd_id);
            $this->GetRating($prd_id);
            $this->_Logger->info('Calculated Rating for ' . $prd_id);
        }
        /*
        
            $this->_Logger->info( 'IsFeatured for '. $prd_id);
            $this->GetIsFeatured($prd_id);
            $this->_Logger->info( 'Calculated IsFeatured for '. $prd_id);*/
        
        
/*        if($type == 'observer'){
            //Calculate total points
            $this->_Logger->info( 'CalculateFinalPoints for '. $prd_id);
            $this->CalculateFinalPoints($prd_id, $type);
            $this->_Logger->info( 'Calculated CalculateFinalPoints for '. $prd_id);
        }*/
        
    }
    
    
    public function CalculatePoints($prd_id, $logFileName = null){
        
        // echo "<pre>";
        // echo "<br>calling CalculatePoints";
        
//        $this->UpdatePoints(1515 , 'cron');
        
        $ArtmSortingProductscollection = $this->ArtmSortingProducts->create();
        
        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $connection = $this->_resources->getConnection();
        
        // select query
        $table = $this->_resources->getTableName( self::TBL_SORT_PRODUCTS );
        $read = "Select * FROM ". $table;
        $query_for_min = "SELECT MIN(size_roll_points) AS size_roll_points FROM ".$table." where size_roll_points > 0";
        $query_for_max = "SELECT MAX(size_roll_points) AS size_roll_points FROM ".$table." where size_roll_points > 0";
        
        $result_for_min = $connection->fetchAll($query_for_min);
        $result_for_max = $connection->fetchAll($query_for_max);
        
        //echo "<br>result_for_min: ".$result_for_min[0]['size_roll_points'];
        //echo "<br>result_for_max: ".$result_for_max[0]['size_roll_points'];
        
        
        $ArtmSortingProductsobj = $this->ArtmSortingProducts->create();
        
        date_default_timezone_set('Asia/Kolkata'); 
        $currentTime = new \DateTime();
        $currentTimeFormatted = $currentTime->format('Y-m-d H:i:s');
        // echo $currentTimeFormatted;
        $cutoffTime = $currentTime->sub(new \DateInterval('PT24H'));
        $cutoffTimeFormatted = $cutoffTime->format('Y-m-d H:i:s');
        $ArtmSortingProductscollection = $ArtmSortingProductsobj->getCollection();
        if( $prd_id != null ){
            $ArtmSortingProductscollection->addFieldToFilter('product_id', $prd_id);
        }else{
            // $ArtmSortingProductscollection->addFieldToFilter(
            //     'updated_at',
            //     ['lt' => $cutoffTimeFormatted]
            // );
        }
        
        

        if( $ArtmSortingProductscollection->count() > 0 ){
            
            foreach($ArtmSortingProductscollection as $item){
                    // echo "ID: ".$prd_id;
                
                $product_id = $item['product_id'];
//               
                
                $id = $item['id'];
                $size_roll_points = $item['size_roll_points'];
                // echo "<br>";
                // echo $size_roll_points."<br>";
                if($size_roll_points > 0){
                    $size_roll_percentage = ($size_roll_points - $result_for_min[0]['size_roll_points']) / ( ($result_for_max[0]['size_roll_points'] - $result_for_min[0]['size_roll_points'] ) / 10);
                    // echo "<br>Percentage is ".$size_roll_percentage;
                }else{
                    $size_roll_percentage = -1;
                }
                // echo "<br>id:".$id;
                    
                $ArtmSortingProductsobj->load( $id );
                $ArtmSortingProductsobj->setSizeRollPointsPercentage( $size_roll_percentage );
                $ArtmSortingProductsobj->setUpdatedAt( $currentTimeFormatted );
                $ArtmSortingProductsobj->save();
                
                //calculate final points
                $this->CalculateFinalPoints($product_id, 'observer', $logFileName);

            }
        }
        
        
//        print_r($result);
        
    }
    
    public function CalculateFinalPoints($prd_id, $type, $logFileName = null){
        // echo "Reached CalculateFinalPoints: ".$prd_id."<br>";
        $max_points = self::MAX_POINTS;
        $max_points_for_availablity = self::MAX_POINTS_FOR_AVAILABILITY;
        $max_points_for_internal_source = self::MAX_POINTS_FOR_AVAILABILITY_INTERNAL_SOURCE;
        $max_points_for_isfeatured = self::MAX_POINTS_FOR_ISFEATURED;
        
        $total_points_out_of = 0;
        $total_points_received = 0;
        
        $ArtmSortingProductsobj = $this->ArtmSortingProducts->create();
        $ArtmSortingProductscollection = $ArtmSortingProductsobj
            ->getCollection()
            ->addFieldToFilter('product_id', $prd_id);
        
        if( $ArtmSortingProductscollection->count() > 0 ){
            foreach($ArtmSortingProductscollection as $item){
                
                $isSizerollEnabled = $this->artmhelper->getConfig('prioritycriteria/general/sizeroll');
                //echo "<br>isSizerollEnabled: ".$isSizerollEnabled."<br>";
                $isHighestPointsEnabled = $this->artmhelper->getConfig('prioritycriteria/general/discount');
                //echo "<br>isHighestPointsEnabled: ".$isHighestPointsEnabled."<br>";
                $isLatestUpdatePointEnabled = $this->artmhelper->getConfig('prioritycriteria/general/latest');
                //echo "<br>isLatestUpdatePointEnabled: ".$isLatestUpdatePointEnabled."<br>";
                $isAvailabilityQtyEnabledcgc = $this->artmhelper->getConfig('prioritycriteria/general/cgc_warehouse');
                //echo "<br>isAvailabilityQtyEnabledcgc: ".$isAvailabilityQtyEnabledcgc."<br>";
                $isAvailabilityQtyEnabledother = $this->artmhelper->getConfig('prioritycriteria/general/other_warehouse');
                //echo "<br>isAvailabilityQtyEnabledother: ".$isAvailabilityQtyEnabledother."<br>";
                $isFeaturedEnabled = 1;
                
                
                
                //  echo "<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>";
                 $this->logger->logToFile("<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>", $logFileName);

                //  echo "<br>size_roll_points: ".$item['size_roll_points_percentage']."<br>";
                 $this->logger->logToFile("<br>size_roll_points: ".$item['size_roll_points_percentage']."<br>", $logFileName);

                if($isSizerollEnabled == 1){
                    $isSizerollEnabled_enable = "Enable";
                }else{
                    $isSizerollEnabled_enable = "Disable";
                }
                //echo "<br>isSizerollEnabled: ".$isSizerollEnabled_enable."<br>";
                if($isSizerollEnabled == 1){
                    
                    if( $item['size_roll_points_percentage'] != -1 ){
                        $total_points_out_of = $total_points_out_of + $max_points;
                    }
                    if( $item['size_roll_points_percentage'] > 0 ){
                        //echo "<br>max_points: ".$max_points;
                        //echo "<br>--------<br><br>";
                        //echo "size_roll_points".$item['size_roll_points_percentage']."<br>";
                        //echo "".$total_points_out_of;
                        //echo "<br>";
                        $total_points_received = $total_points_received + $item['size_roll_points_percentage'];
                        //echo "size_roll_points: ".$item['size_roll_points_percentage']." out of ".$max_points;
                        //echo "<br>--------<br>";
                    }
                }
                
                // echo "<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>";
                $this->logger->logToFile("<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>", $logFileName);

                // echo "<br>created_at_points: ".$item['created_date_points']."<br>";
                $this->logger->logToFile("<br>created_at_points: ".$item['created_date_points']."<br>", $logFileName);

                $total_points_out_of = $total_points_out_of + 10;
                $total_points_received = $total_points_received + $item['created_date_points'];
                





                // echo "<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>";
                $this->logger->logToFile("<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>", $logFileName);

                // echo "<br>created_at_points: ".$item['created_date_points']."<br>";
                $this->logger->logToFile("<br>latest_updated_points: ".$item['latest_updated_points']."<br>", $logFileName);

                $total_points_out_of = $total_points_out_of + 10;
                $total_points_received = $total_points_received + $item['latest_updated_points'];
                







                $this->logger->logToFile("<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>", $logFileName);

                $this->logger->logToFile("<br>rating: ".$item['rating']."<br>", $logFileName);

                $total_points_out_of = $total_points_out_of + 10;
                $total_points_received = $total_points_received + $item['rating'];
                
                
                
                // echo "<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>";
                $this->logger->logToFile("<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>", $logFileName);

                // echo "<br>high_discount_points: ".$item['high_discount_points']."<br>";
                $this->logger->logToFile("<br>high_discount_points: ".$item['high_discount_points']."<br>", $logFileName);

                if($isHighestPointsEnabled == 1){
                    $isHighestPointsEnabled_enable = "Enable";
                }else{
                    $isHighestPointsEnabled_enable = "Disable";
                }
                //echo "<br>isHighestPointsEnabled: ".$isHighestPointsEnabled_enable."<br>";
                if($isHighestPointsEnabled == 1){
                    $total_points_out_of = $total_points_out_of + $max_points;
                    
                    if( $item['high_discount_points'] > 0 ){
                        //echo "<br>--------<br>high_discount_points<br>";
                        //echo "total_points_out_of";
                        //echo "<br>";
                        $total_points_received = $total_points_received + $item['high_discount_points'];
                        // echo "high_discount_points: ".$item['high_discount_points']." out of ".$max_points;
                        // echo "<br>--------<br>";
                    }
                }
                
                if($isAvailabilityQtyEnabledcgc == 1){
                    $isAvailabilityQtyEnabledcgc_enable = "Enable";
                }else{
                    $isAvailabilityQtyEnabledcgc_enable = "Disable";
                }



                // echo "<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>";
                $this->logger->logToFile("<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>", $logFileName);

                // echo "<br>availability_cgc: ".$item['availability_cgc']."<br>";
                $this->logger->logToFile("<br>availability_cgc: ".$item['availability_cgc']."<br>", $logFileName);

                if($isAvailabilityQtyEnabledcgc == 1){
                    $total_points_out_of = $total_points_out_of + $max_points_for_internal_source;
                    
                    if( $item['availability_cgc'] > 0 ){
                        //echo "<br>--------<br>availability_cgc<br>";
                        //echo $total_points_out_of;
                        //echo "<br>";
                        $total_points_received = $total_points_received + $item['availability_cgc'];
                        // echo "availability_cgc: ".$item['availability_cgc']." out of ".$max_points_for_internal_source;
                        // echo "<br>--------<br>";
                    }
                }


                // echo "<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>";
                $this->logger->logToFile("<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>", $logFileName);

                // echo "<br>availability_golfoy: ".$item['availability_golfoy']."<br>";
                $this->logger->logToFile("<br>availability_golfoy: ".$item['availability_golfoy']."<br>", $logFileName);
                
                // echo "<br>isAvailabilityQtyEnabledcgc: ".$isAvailabilityQtyEnabledcgc_enable."<br>";
                if($isAvailabilityQtyEnabledcgc == 1){
                    $total_points_out_of = $total_points_out_of + $max_points_for_internal_source;
                    
                    if( $item['availability_golfoy'] > 0 ){
                        //echo "<br>--------<br>availability_golfoy<br>";
                        //echo $total_points_out_of;
                        //echo "<br>";
                        $total_points_received = $total_points_received + $item['availability_golfoy'];
                        // echo "availability_golfoy: ".$item['availability_golfoy']." out of ".$max_points_for_internal_source;
                        // echo "<br>--------<br>";
                    }
                }
                
                
                // echo "<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>";
                $this->logger->logToFile("<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>", $logFileName);

                // echo "<br>availability_other (Saleable): ".$item['availability_other']."<br>";
                $this->logger->logToFile("<br>availability_other (Saleable): ".$item['availability_other']."<br>", $logFileName);

                if($isAvailabilityQtyEnabledother == 1){
                    $isAvailabilityQtyEnabledother_enable = "Enable";
                }else{
                    $isAvailabilityQtyEnabledother_enable = "Disable";
                }
                //echo "<br>isAvailabilityQtyEnabledother: ".$isAvailabilityQtyEnabledother_enable."<br>";
                if($isAvailabilityQtyEnabledother == 1){
                    $total_points_out_of = $total_points_out_of + $max_points_for_availablity;
                    
                    if( $item['availability_other'] > 0 ){
                        //echo "<br>--------<br>availability_other<br>";
                        //echo "<br>";
                        $total_points_received = $total_points_received + $item['availability_other'];
                        // echo "availability_other: ".$item['availability_other']." out of ".$max_points_for_availablity;
                        // echo "<br>--------<br>";
                    }
                }
                
                // echo "<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>";
                $this->logger->logToFile("<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>", $logFileName);
                
                
                if($isFeaturedEnabled == 1){
                    
                    // echo "<br>Is Featured points 10.<br>";
                    if( $item['is_feature'] > 0 ){
                        $this->logger->logToFile("<br>Is Featured points 10.<br>", $logFileName);
                        $total_points_received = $total_points_received + $item['is_feature'];
                    }else{
                        $this->logger->logToFile("<br>Is Featured points 0.<br>", $logFileName);
                    }
                    
                    $total_points_out_of = $total_points_out_of + $max_points_for_isfeatured;
                }else{
                    // echo "<br>Is Featured points 0.<br>";
                }
                
                // echo "<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>";
                $this->logger->logToFile("<br>--------------".$total_points_received." out of ".$total_points_out_of."---------------<br>", $logFileName);

                // echo "<br>Now we will calculate for latest update points.<br>";
                $this->logger->logToFile("<br>Now we will calculate for latest update points.<br>", $logFileName);
                
                
                
                
          /*       echo "<br><br><br>latest_updated_points: ".$item['latest_updated_points']."<br>";
                // echo "<br>isLatestUpdatePointEnabled: ".$isLatestUpdatePointEnabled."<br>";
                if($isLatestUpdatePointEnabled == 1){
                    $points_for_latestupdate_ouof = 150;
                    
                    if( ($item['latest_updated_points']+$item['created_date_points']) > 0 ){
                        $points_for_latestupdate = ( ( $item['latest_updated_points'] + $item['created_date_points'] ) * ($points_for_latestupdate_ouof ) ) / ($max_points_for_availablity * 2);
                    }else{
                        $points_for_latestupdate = 0;
                    }
                    // echo "<br><br>item['latest_updated_points']: ".$item['latest_updated_points'];
                    // echo "<br><br>item['created_date_points']: ".$item['created_date_points'];
                    // echo "<br><br>points_for_latestupdate_ouof * 2: ".$points_for_latestupdate_ouof * 2;
                    // echo "<br><br>max_points_for_availablity * 2: ".$max_points_for_availablity * 2;
                    
                }else{
                    
                    $points_for_latestupdate_ouof = 0;
                    $points_for_latestupdate = 0;
                }*/
                
                
                
            }
        }
        
        
        // echo "<br>Points received ".$total_points_received." out of ".$total_points_out_of;
    //    echo "<br>Points for latest update ".$points_for_latestupdate." out of ".$points_for_latestupdate_ouof."<br>";
        
        //total points out of 1000

        if( strtolower($this->GetAttributeSetName($prd_id)) == 'shoe spike' || strtolower($this->GetAttributeSetName($prd_id)) == 'shoe spikes' || strtolower($this->GetAttributeSetName($prd_id)) == 'shoe laces' ||strtolower($this->GetAttributeSetName($prd_id)) == 'socks' ||strtolower($this->GetAttributeSetName($prd_id)) == 'shoe insoles' ||strtolower($this->GetAttributeSetName($prd_id)) == 'footwear care' ){
            $total_points = ( $total_points_received / $total_points_out_of ) * 500;
            if($total_points > 0){
                $total_points = $total_points / 2;
            }
            echo "Product is in some choosen category, skiping calculating points";
            $this->logger->logToFile("Product is in some choosen category, skiping calculating points", $logFileName);
            // echo "total points out of 500 are ".$total_points;
        }else{
            $total_points = ( $total_points_received / $total_points_out_of ) * 1000;
            echo "Total points: ".$total_points." out of 1000";
            $this->logger->logToFile("Total points: ".$total_points." out of 1000", $logFileName);

            // echo "total points out of 1000 are ".$total_points;
        }
        // $total_points = ( $total_points_received / $total_points_out_of ) * 1000;
        // echo "total points out of 1000 are ".$total_points;
            
        /*if( ($total_points_received + $points_for_latestupdate) == 0){
            $total_points = 0;
        }else{
            if( strtolower($this->GetAttributeSetName($prd_id)) == 'shoe spike' || strtolower($this->GetAttributeSetName($prd_id)) == 'shoe spikes' || strtolower($this->GetAttributeSetName($prd_id)) == 'shoe laces' ||strtolower($this->GetAttributeSetName($prd_id)) == 'socks' ||strtolower($this->GetAttributeSetName($prd_id)) == 'shoe insoles' ||strtolower($this->GetAttributeSetName($prd_id)) == 'footwear care' ){
                $accessory_flag = true;
            }else{
                $accessory_flag = false;
            }
            
            
            if( $points_for_latestupdate_ouof == 150 ){
                //echo "attribute set is ".$this->GetAttributeSetName($prd_id)."<br>";
                
                if( $accessory_flag == true ){
                    //echo "<br>100 true here";
                    $total_points = ($total_points_received * 300) / $total_points_out_of;
                }else{
                    //echo "<br>100 false here";
                    $total_points = ($total_points_received * 900) / $total_points_out_of;
                }
                
                $total_points = round($total_points) + $points_for_latestupdate;
            }else{
                
                if( $accessory_flag == true ){
                    //echo "<br>not 100 true here";
                    $total_points = ($total_points_received * 300) / $total_points_out_of;
                }else{
                    //echo "<br>not 100 false here";
                    $total_points = ($total_points_received * 1000) / $total_points_out_of;
                }
            }
            
            
        }*/
        
        //echo "<br>Final Points ".$total_points." out of 1000<br>";
        
        
        // echo "get here".$item['product_id'];
        $ArtmSortingProductsobj->load( $item['id'] );
        $ArtmSortingProductsobj->setTotalPoints( $total_points );
        $ArtmSortingProductsobj->setSyncStatus(0);
        $ArtmSortingProductsobj->save();
        
        if($type == 'observer'){
            $prod_points[$prd_id] = $total_points;
            $this->updateCategoryProductsPoints($prod_points);
        }
        
    }
    public function UpdateAllPointsToCategoryTable(){       
       
        $ArtmSortingProductsobj = $this->ArtmSortingProducts->create();
        $ArtmSortingProductscollection = $ArtmSortingProductsobj
            ->getCollection();
        $ArtmSortingProductscollection->addFieldToFilter('sync_status', 0);
        // echo $ArtmSortingProductscollection->count();die;
        if( $ArtmSortingProductscollection->count() > 0 ){
            foreach($ArtmSortingProductscollection as $item){
                $this->_Logger->info( 'Updating Points in category table '.$item['product_id'] );
                
                // echo $product_id = $item['product_id'];
                // echo ' ';
                $total_points = $item['total_points'];
                
                $prod_points[$product_id] = $total_points;
                if(!empty($prod_points)){
                    if($this->updateCategoryProductsPoints($prod_points) == true){
                        $item->setData('sync_status', 1);
                        $item->save();
                    }
                }  
            }
        }
    }

    
    public function getCreatedAt($prd_id){
        $product = $this->_productloader->create()->load($prd_id);
        return $product->getCreatedAt();
    }

    public function getQtyDetails($productId){
        $return ='';
        $total_qty_golfoy = 0;
        $total_qty_other = 0;
        $product = $this->_productloader->create()->load($productId);
        $sku = $product->getSku();
        $productExtension = $product->getExtensionAttributes();
        $ManageStock = $productExtension->getStockItem()->getManageStock();

        if( $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
            $_configChild = $product->getTypeInstance()->getUsedProductIds($product);
            $getChildId = array();
            $first_id = '';
            foreach ($_configChild as $child){
                $getChildId[] = $child;
            }
            foreach( $getChildId as $simpleprd ){
                
                $simple_product = $this->_productloader->create()->load($simpleprd);
                $simple_product_sku = $simple_product->getSku();
                
                $productExtension = $simple_product->getExtensionAttributes();
                $ManageStock_simple = $productExtension->getStockItem()->getManageStock();
                    
                if( $ManageStock_simple == 1 ){

                    $total_qty = 0;
                    $total_qty_cgc = 0;
                    $result = $this->getSourceItemDetailBySKU($simple_product_sku);
                    foreach ($result as $item) {
                        $sources = $item->getData();
                    if( $sources['source_code'] == "G5" ){
                        $total_qty_golfoy = $total_qty_golfoy + $sources['quantity'];
                    }else{
                        $total_qty_other = $total_qty_other + $sources['quantity'];
                    }
                    }
                    $return = [
                        'golfoy_qty' => $total_qty_golfoy,
                        'salable_qty' => $total_qty_other + $total_qty_golfoy,
                    ];
                    // $return = json_encode($return);
                    
                }else{
                    return $return = 'Manage stock no';
                }
            }
        }else{
            if( $ManageStock == 1 ){

                $total_qty = 0;
                $total_qty_cgc = 0;
                $result = $this->getSourceItemDetailBySKU($sku);
                foreach ($result as $item) {
                    
                    $sources = $item->getData();
                    if( $sources['source_code'] == "G5" ){
                        $total_qty_golfoy = $total_qty_golfoy + $sources['quantity'];
                    }else{
                        $total_qty_other = $total_qty_other + $sources['quantity'];
                    }
                }
                $return = [
                    'golfoy_qty' => $total_qty_golfoy,
                    'salable_qty' => $total_qty_other + $total_qty_golfoy,
                ];
                // $return = json_encode($return);

            }else{
                $return = 'Manage stock No';
            }
        }
        return $return;
    }
    
    public function GetRating($prd_id)
    {

        $product = $this->_productloader->create()->load($prd_id);
        $storeId = $product->getStoreId();
        $ratingPoints = 0;

        if ($product && $product->getId()) {
            // Load the review summary using injected reviewFactory
            $reviewModel = $this->reviewFactory->create();
            $reviewModel->getEntitySummary($product, $storeId);

            $ratingSummary = $product->getRatingSummary()->getRatingSummary(); // value out of 100
            //echo "Rating Summary (0-100): <strong>{$ratingSummary}</strong><br>";

            if ($ratingSummary !== null && $ratingSummary > 0) {
                $averageRating = round(($ratingSummary / 100) * 5, 1); // convert to 0–5 scale
                //echo "Average Rating (0–5): <strong>{$averageRating}</strong><br>";

                // Convert to 1–10 points (0.5 = 1 point)
                $ratingPoints = round($averageRating * 2);
            } else {
                //echo "<span style='color:red;'>No rating available</span><br>";
            }
        } else {
            //echo "<span style='color:red;'>Product not found or invalid ID</span><br>";
        }

        $data['rating'] = $ratingPoints;
        //echo "Calculated Points for Rating: <strong>{$ratingPoints}</strong><br>";

        $this->AddDatainArtmSortingProducts($prd_id, 'rating', $data);
    }

    
    public function GetAvailabilityQty( $prd_id ){
    //    echo "called GetAvailabilityQty";
        //echo "<br>--------------------------------------------------------------------------------------------------------------------<br>On GetAvailabilityQty";
        $product = $this->_productloader->create()->load($prd_id);
        $sku = $product->getSku();
       // $salable = $this->getSalableQuantityDataBySku->execute($sku);
        
        $points = 0;
        $total_qty_cgc = 0;
        $total_qty_golfoy = 0;
        $total_qty_other = 0;
                
        $productExtension = $product->getExtensionAttributes();
        $ManageStock = $productExtension->getStockItem()->getManageStock();
        
        $check_sources_in = array("BS", "CG", "CGC", "GK", "GMN", "JB", "KM", "TGIP", "USKG", "ZSPL"); 
        if( $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
            $_configChild = $product->getTypeInstance()->getUsedProductIds($product);
            $getChildId = array();
            $first_id = '';
            foreach ($_configChild as $child){
                $getChildId[] = $child;
            }
            //echo "<div class='simple_prd_array'>";
           // $check_sources_in = array("BS", "CG", "CGC", "GK", "GMN", "JB", "KM", "TGIP", "USKG", "ZSPL"); 
            foreach( $getChildId as $simpleprd ){
                
                //echo "<br>Executing for ".$simpleprd."<br>";
                $simple_product = $this->_productloader->create()->load($simpleprd);
                $simple_product_sku = $simple_product->getSku();
                
                $productExtension = $simple_product->getExtensionAttributes();
                $ManageStock_simple = $productExtension->getStockItem()->getManageStock();
                
                    
                if( $ManageStock_simple == 1 ){

                    $total_qty = 0;
                    $total_qty_cgc = 0;
                    $result = $this->getSourceItemDetailBySKU($simple_product_sku);
                    foreach ($result as $item) {
                        $sources = $item->getData();
                        //print_r($sources);
                        if( $sources['source_code'] == "CGC" ){
                            $total_qty_cgc = $total_qty_cgc + $sources['quantity'];
                        }else if( $sources['source_code'] == "G5" ){
                            $total_qty_golfoy = $total_qty_golfoy + $sources['quantity'];
                        }else if( in_array($sources['source_code'], $check_sources_in) ){
                            $total_qty_other = $total_qty_other + $sources['quantity'];
                        }
                    }
                }
            }
            //echo "</div>";
        }else{
            if( $ManageStock == 1 ){

                $total_qty = 0;
                $total_qty_cgc = 0;
                $result = $this->getSourceItemDetailBySKU($sku);
                foreach ($result as $item) {
                    
                    $sources = $item->getData();
                    if( $sources['source_code'] == "CGC" ){
                        $total_qty_cgc = $total_qty_cgc + $sources['quantity'];
                    }else if( $sources['source_code'] == "G5" ){
                        $total_qty_golfoy = $total_qty_golfoy + $sources['quantity'];
                    }else if( in_array($sources['source_code'], $check_sources_in) ){
                        $total_qty_other = $total_qty_other + $sources['quantity'];
                    }
                }

            }else{
                $data['points_for_golfoy'] = self::MAX_POINTS_FOR_AVAILABILITY_INTERNAL_SOURCE;
                $data['points_for_cgc'] = self::MAX_POINTS_FOR_AVAILABILITY_INTERNAL_SOURCE;
                $data['points_for_other'] = self::MAX_POINTS_FOR_AVAILABILITY;
            }
        }
        if($total_qty_cgc > self::MAX_POINTS_FOR_AVAILABILITY_INTERNAL_SOURCE){
            $total_qty_cgc = self::MAX_POINTS_FOR_AVAILABILITY_INTERNAL_SOURCE;
        }
        if($total_qty_golfoy > self::MAX_POINTS_FOR_AVAILABILITY_INTERNAL_SOURCE){
            $total_qty_golfoy = self::MAX_POINTS_FOR_AVAILABILITY_INTERNAL_SOURCE;
        }
        if($total_qty_other > self::MAX_POINTS_FOR_AVAILABILITY){
            $total_qty_other = self::MAX_POINTS_FOR_AVAILABILITY;
        }

        $data['points_for_golfoy'] = $total_qty_golfoy;
        $data['points_for_cgc'] = $total_qty_cgc;
        $data['points_for_other'] = $total_qty_other;
       
//        $points_for_golfoy = $data['points_for_golfoy'];
    //    echo "points_for_golfoy: ".$points_for_golfoy;
//        $points_for_cgc = $data['points_for_cgc'];
    //    echo "points_for_cgc: ".$points_for_cgc;
//        $points_for_other = $data['points_for_other'];
    //    echo "points_for_other: ".$points_for_other;
        
        if($total_qty_golfoy + $total_qty_cgc + $total_qty_other > 10){
            $data['availability_saleable'] = 10;
        }else{
           $data['availability_saleable'] = $total_qty_golfoy + $total_qty_cgc + $total_qty_other;
        }
    //    echo "Total saleable qty: ".$data['availability_saleable'];
        $this->AddDatainArtmSortingProducts( $prd_id, 'available_qty' , $data );
    }
    
    public function GetLatestUpdatePoint( $prd_id ){
        
        date_default_timezone_set('Asia/Calcutta');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $objDate = $objectManager->create('Magento\Framework\Stdlib\DateTime\DateTime');
        $date = $objDate->gmtDate();
        
        $product = $this->_productloader->create()->load($prd_id);
        
        // Retrieve the attribute's text value
        $isFeaturedText = $product->getAttributeText('isfeatured');

    //    echo "Reached on checking isFeatured";
        if ($isFeaturedText == 'Yes') {
            // Product is featured
            $isFeaturedTextPoints = 10;
        //    echo "IsFeatured is Enable";
        } elseif ($isFeaturedText == 'No') {
            // Product is not featured
            $isFeaturedTextPoints = 0;
        //    echo "IsFeatured is Disable";
        } else {
            // Attribute not set or missing
            $isFeaturedTextPoints = 0;
        //    echo "IsFeatured is Disable";
        } 
        $updated_at = $product->getUpdatedAt();
        
        $date=date_create($date);
        $date = date_format($date,"Y-m-d");
        $date = date_create($date);
        
        if (!empty($updated_at)) {
            $updated_at = date_create($updated_at);
            $updated_at = date_format($updated_at,"Y-m-d");
            $updated_at = date_create($updated_at);
            //print_r($updated_at);
            // calculates the difference between DateTime objects
            $interval = date_diff($updated_at, $date);

            // printing result in days format

            $days = $interval->format('%a');
            if( $days > 30 ){
                $points_to_deduct = 30;
            }else{
                $points_to_deduct = $days;
            }

            $points = 30 - $points_to_deduct;
            $points_for_latest_update = round( (($points/30) * 10) ,2);


            $data['points_for_last_updated'] = $points_for_latest_update;
            
        } else {
            // Handle the case where $updated_at is null or invalid, if necessary
            $points_for_latest_update = 0;
            $updated_at = null; // Or set it to a default value if needed
            $data['points_for_last_updated'] = 0;
        }
        
        /*
        ///////////////////////////////////////////////////createdat
        if (!empty($updated_at)) {
            
            $createdAt = $product->getCreatedAt();

            $createdAt = date_create($createdAt);
            $createdAt = date_format($createdAt,"Y-m-d");
            $createdAt = date_create($createdAt);

            
            print_r($createdAt);

            // calculates the difference between DateTime objects
            $interval_created = date_diff($createdAt, $date);

            // printing result in days format

            $days_created = $interval_created->format('%a');
            if( $days_created > 120 ){
                $points = 120;
            }else{
                $points = $days_created;
            }
    //        $points = 120 - $points_to_deduct;
            $points_for_created_date = round( (($points/120) * 5) ,2);
            echo "points_for_created_date : ".$points_for_created_date;
            $data['points_for_created_date'] = $points_for_created_date;

        }else{
            $points_for_created_date = 0;
            $data['points_for_created_date'] = 0;
            
        }
            */
    //    echo "updated_at".print_r($updated_at)."<br>";
    //    echo "createdAt".print_r($createdAt)."<br>";
//        
//        echo $data['points_for_last_updated']."-".$points_for_latest_update."<br>";
    //    echo "-";
//        echo $data['points_for_created_date']."-".$points_for_created_date."<br>";
    //    echo "<br>After</br>";
        
        // if( $points_for_latest_update + $points_for_created_date > 8 ){
        //     $overall = 7;
        //     $data['points_for_last_updated'] = $overall/2;
        //     $data['points_for_created_date'] = $overall/2;
        // }
//        echo $data['points_for_last_updated'];
    //    echo "-";
//        echo $data['points_for_created_date'];
        $data['isFeaturedTextPoints'] = $isFeaturedTextPoints;
        // echo "points for updated date";
        // exit;
        
            //print_r($data);
        $this->AddDatainArtmSortingProducts($prd_id, "last_updated" , $data);
        // $this->AddDatainArtmSortingProducts($prd_id, "date_created" , $data);
        $this->AddDatainArtmSortingProducts($prd_id, "isFeaturedTextPoints" , $data);
        
    }

    public function GetCreatedDatePoint( $prd_id ){
        
        date_default_timezone_set('Asia/Calcutta');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $objDate = $objectManager->create('Magento\Framework\Stdlib\DateTime\DateTime');
        $date = $objDate->gmtDate();
        
        $product = $this->_productloader->create()->load($prd_id);
        $created_at = $product->getCreatedAt();
        
        $date=date_create($date);
        $date = date_format($date,"Y-m-d");
        $date = date_create($date);
        
        if ( !empty($created_at) ) {
            
            $created_at = date_create($created_at);
            $created_at = date_format($created_at,"Y-m-d");
            $created_at = date_create($created_at);
        //    echo "created_at: ".print_r($created_at);


            // calculates the difference between DateTime objects
            $interval = date_diff($created_at, $date);

            // printing result in days format
            $days = $interval->format('%a');
            if( $days > 30 ){
                $points_to_deduct = 30;
            }else{
                $points_to_deduct = $days;
            }

            $points = 30 - $points_to_deduct;
            $points_for_created_at = round( (($points/30) * 10) ,2);
        //    echo "points_for_created_at: ".$points_for_created_at;

            $data['points_for_created_date'] = $points_for_created_at;
        }else{
            
        //    echo "points_for_created_at: ".$points_for_created_at;

            $points_for_created_at = 0;
            $data['points_for_created_date'] = $points_for_created_at;
        }
        $this->AddDatainArtmSortingProducts($prd_id, "date_created" , $data);
        
    }
    public function GetSizeRoll( $prd_id ){            
        //load product
        $product = $this->_productloader->create()->load($prd_id);
        
        //find attribute set name
        $attr_set_name = $this->GetAttributeSetName($prd_id);
        
        //find brand name
        $p_brand_name = $product->getResource()->getAttribute('brand')->getFrontend()->getValue($product);
        $p_brand_name = trim((string) $p_brand_name);
        $p_brand_name = strtolower((string) $p_brand_name);
        
        //getting the total sizeroll along with brand from sizeroll module
        $sizeroll_data = $this->sizeroll_helper->getrow( strtolower($attr_set_name), $p_brand_name );
        if( count($sizeroll_data) == 0 ){
            //sizeroll is not found in sizeroll module
            $data = array( "attr_set_name" => $attr_set_name, "points_for_sizeroll" => -1, "html_for_sizeroll" => '' );
            $this->AddDatainArtmSortingProducts($prd_id, 'sizeroll', $data);   
            return false;
        }
        $attr_name = $sizeroll_data[0]['attribute'];
        $complete_array = $sizeroll_data[0]['totalsizedarrayvalue'];
        // echo "".$complete_array."<br>";
        $complete_array_arr = explode(',',$complete_array);
        $complete_array_arr_count = count($complete_array_arr);
        // echo "complete_array_arr_count: ".$complete_array_arr_count."<br>";
        
        $available_sizes = $this->productpriceblock->getSizes($prd_id,$attr_name);
        // echo "<br>".print_r($available_sizes)."<br>";
        $available_sizes = array_map( 'strtolower', $available_sizes );
        $available_sizes = array_unique($available_sizes);
        $available_sizes_count = count($available_sizes);
        $html_for_sizeroll = $this->productpriceblock->check_roll($complete_array_arr , $available_sizes);
        // echo "html_for_sizeroll:".print_r($html_for_sizeroll);
        
        $points_for_sizeroll = round( $this->CalculatePointsForAttribute($complete_array_arr_count , $available_sizes_count) ,2);
        $points_for_sizeroll = $this->CalculatePointsForAttribute($complete_array_arr_count , $available_sizes_count);
        // echo "complete_array_arr_count : ".$complete_array_arr_count."<br>";
        // echo "available_sizes_count : ".$available_sizes_count."<br>";
        // echo "points_for_sizeroll: ".$points_for_sizeroll;
        
        $data = array();
        
        $data = array( "attr_set_name" => $attr_set_name, "points_for_sizeroll" => $points_for_sizeroll, "html_for_sizeroll" => $html_for_sizeroll['text_value'] );
        $this->AddDatainArtmSortingProducts($prd_id, 'sizeroll', $data);   
        return true;
        
    }
    public function GetHighestPoints( $prd_id ){
        
        //functin to calculate points according to highest discount
        $product = $this->_productloader->create()->load($prd_id);
        
        if( $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){

            $_configChild = $product->getTypeInstance()->getUsedProductIds($product);
            $getChildId = array();
            $first_id = '';
            foreach ($_configChild as $child){
                $getChildId[] = $child;
            }
            if(!empty($getChildId)){
                $toprocessID = $getChildId[0];
            }else{
                $toprocessID = $prd_id;
            }
        }else{
            $toprocessID = $prd_id;
        }
            $product_first_simple = $this->_productloader->create()->load($toprocessID);
            $finalPriceAmt = $product_first_simple->getPriceInfo()->getPrice('final_price')->getValue();
            $regular_price = $product_first_simple->getPrice();
            
            
            if( (int)$finalPriceAmt == (int)$regular_price){
                $discount_per = 0;
            }else{
                if($regular_price < 1 || $finalPriceAmt < 1 ){
                    $discount_per = 0;
                }else{
                    $discount_per = (($regular_price - $finalPriceAmt)*100) / $regular_price ;
                }
                
            }
        
            $points_for_highestpoints = $this->CalculatePointsForAttribute( 100 , ($discount_per) );
            $data = array( "points_for_highestpoints" => $points_for_highestpoints );
            $this->AddDatainArtmSortingProducts($prd_id, 'highestpoints', $data);
        
        
    }

    public function getAllSize($prd_id){
        $product = $this->_productloader->create()->load($prd_id);
            
        //find attribute set name
        $attr_set_name = $this->GetAttributeSetName($prd_id);
        
        //find brand name
        $p_brand_name = $product->getResource()->getAttribute('brand')->getFrontend()->getValue($product);
        $p_brand_name = trim((string) $p_brand_name);
        $p_brand_name = strtolower((string) $p_brand_name);
        
        //getting the total sizeroll along with brand from sizeroll module
        $sizeroll_data = $this->sizeroll_helper->getrow( strtolower($attr_set_name), $p_brand_name );
        if( count($sizeroll_data) == 0 ){
            //sizeroll is not found in sizeroll module
            $data = array( "attr_set_name" => $attr_set_name, "points_for_sizeroll" => -1, "html_for_sizeroll" => '' );
            $this->AddDatainArtmSortingProducts($prd_id, 'sizeroll', $data);   
            return false;
        }
        $attr_name = $sizeroll_data[0]['attribute'];
        $complete_array = $sizeroll_data[0]['totalsizedarrayvalue'];
        //echo "".$complete_array."<br>";
        $complete_array_arr = explode(',',$complete_array);
        $complete_array_arr_count = count($complete_array_arr);
        //echo "complete_array_arr_count: ".$complete_array_arr_count."<br>";
        
        return $available_sizes = $this->productpriceblock->getSizes($prd_id,$attr_name);
    }

    public function getDiscountPercentage($prd_id) {
        $product = $this->_productloader->create()->load($prd_id);
        
        if( $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){

            $_configChild = $product->getTypeInstance()->getUsedProductIds($product);
            $getChildId = array();
            $first_id = '';
            foreach ($_configChild as $child){
                $getChildId[] = $child;
            }
            if(!empty($getChildId)){
                $toprocessID = $getChildId[0];
            }else{
                $toprocessID = $prd_id;
            }
        }else{
            $toprocessID = $prd_id;
        }
            $product_first_simple = $this->_productloader->create()->load($toprocessID);
            $finalPriceAmt = $product_first_simple->getPriceInfo()->getPrice('final_price')->getValue();
            $regular_price = $product_first_simple->getPrice();
            
            
            if( (int)$finalPriceAmt == (int)$regular_price){
                $discount_per = 0;
            }else{
                if($regular_price < 1 || $finalPriceAmt < 1 ){
                    $discount_per = 0;
                }else{
                    $discount_per = (($regular_price - $finalPriceAmt)*100) / $regular_price ;
                }
                
            }
            return $discount_per;
    }
    //--------------------------------------------------
    public function getsizerolepoints($prd_id){
        $random_number = rand(1, 10);
        return $random_number;
    }
    
    public function gethighdiscountpoints($prd_id){
        $random_number = rand(1, 10);
        return $random_number;
    }
    
    
    public function getlatestupdatepoints($prd_id){
        $random_number = rand(1, 10);
        return $random_number;
    }
    
    
    public function getavailabilitycgc($prd_id){
        $random_number = rand(1, 10);
        return $random_number;
    }
    
    
    public function getavailabilityother($prd_id){
        $random_number = rand(1, 10);
        return $random_number;
    }
    
    

    function gettotalpoints($prd_id){
        $random_number = rand(50, 100);
        return $random_number;
    }
    
    //-----------------------------------------------------

    
  
    public function CronLogStatus($attrid){    
        $select = $this->connection->select()->from(self::TBL_SORT_CRON)
        ->where('attribute_set_id = ?', $attrid)
        ->order('id DESC')
        ->limit(1);
        $result = $this->connection->fetchRow($select);
        if($result){
            return $result;
        }else{
            return [];
        }
    }
    public function insertCronLog($attrid){

       /* //delete attr set log if exists
        $where = ['attribute_set_id = ?' => $attrid];        
        $this->connection->delete(self::TBL_SORT_CRON, $where);*/

        //insert
        $data = [
            'attribute_set_id'            => $attrid,
            'cron_start_date_time'        => new \Zend_Db_Expr('NOW()'),
            'status'                      => 'processing',
            'cron_last_updated_date_time' => new \Zend_Db_Expr('NOW()')
        ];
        $this->connection->insert(self::TBL_SORT_CRON, $data);
        $lastInsertId = $this->connection->lastInsertId();
        return $lastInsertId;
    }
    public function insertPoints($attrid,$prd_id){

        $sizerolepoints           = $this->getsizerolepoints($prd_id);
        $highdiscountpoints       = $this->gethighdiscountpoints($prd_id);
        $latestupdatepoints       = $this->getlatestupdatepoints($prd_id);
        $availabilitycgc          = $this->getavailabilitycgc($prd_id);
        $availabilityother        = $this->getavailabilityother($prd_id);
        $totalpoints              = $this->gettotalpoints($prd_id);

        $data = [
           'product_id'           => $prd_id,
           'attr_set_id'          => $attrid,
           'size_roll_points'     => $sizerolepoints,
           'high_discount_points' => $highdiscountpoints,
           'latest_updated_points' => $latestupdatepoints,
           'availability_cgc'     => $availabilitycgc,
           'availability_other'   => $availabilityother,
           'total_points'         => $totalpoints,      
           'sync_status'         => 0,      
           'updated_at'         => $this->getCurrentDateTimeInIndia()
        ];

        $bind = $this->connection->quoteInto('product_id = ?', $prd_id);
        $sql = $this->connection->select()->from(self::TBL_SORT_PRODUCTS)->where($bind);
        $row = $this->connection->fetchOne($sql);
        if ($row) {
           $this->connection->update(self::TBL_SORT_PRODUCTS, $data, $bind);
        } else {
           $this->connection->insert(self::TBL_SORT_PRODUCTS, $data);
        }
    }

    /**
     * Currenct data and time
     *
     * @return string datetime
     */
    public function getCurrentDateTimeInIndia()
    {
        date_default_timezone_set('Asia/Kolkata');   
        $currentDate = new \DateTime();
        return $currentDate->format('Y-m-d H:i:s');
    }

   public function insertProcessedProducts($attrid,$prd_id){
       $data = [
           'attr_set_id'   => $attrid,
           'prd_id'        => $prd_id,
           'processed_on'  => new \Zend_Db_Expr('NOW()')
       ];

       $bind = $this->connection->quoteInto('prd_id = ?', $prd_id);
       $sql = $this->connection->select()->from(self::TBL_SORT_PROCESS)->where($bind);
       $row = $this->connection->fetchOne($sql);
       if ($row) {
          $this->connection->update(self::TBL_SORT_PROCESS, $data, $bind);
       } else {
           $this->connection->insert(self::TBL_SORT_PROCESS, $data);
       }
   }
   
    public function updateCronLog($insertedId ){
        $data = ['status' => 'compelete',
            'cron_last_updated_date_time' => new \Zend_Db_Expr('NOW()') 
        ];
        $where = ['id = ?' => $insertedId];
        
        $this->connection->update(self::TBL_SORT_CRON, $data, $where);
    }

    public function updateCategoryProductsPoints(array $prdpts, $cat_id = '', $forceUpdate = null)
    {
        foreach ($prdpts as $pid => $pts) {
            $data = [
                'sorting_points' => $pts
            ];

            // Get categories where update should be skipped
            $categoryIdNotForSorting = [];
            if ($forceUpdate === null) {
                $categoryIdNotForSorting = $this->getCategoryIdForNotSortingProductsUpdate($pid);
            }

            if (!empty($cat_id)) {
                // If specific category is passed, skip if it's in the ignore list
                if (in_array($cat_id, $categoryIdNotForSorting)) {
                    continue;
                }

                $where = [
                    'product_id = ?' => $pid,
                    'category_id = ?' => $cat_id
                ];
            } else {
                // If no category ID passed, skip all in ignore list
                if (!empty($categoryIdNotForSorting)) {
                    $where = [
                        'product_id = ?' => $pid,
                        'category_id NOT IN (?)' => $categoryIdNotForSorting
                    ];
                } else {
                    $where = [
                        'product_id = ?' => $pid
                    ];
                }
            }

            $this->connection->update('catalog_category_product', $data, $where);
        }

        return true;
    }


    /**
     * Return all category IDs where the given product has sorting data.
     *
     * @param int $productId
     * @return array
     */
    public function getCategoryIdForNotSortingProductsUpdate($productId)
    {
        $select = $this->connection->select()
            ->from('artm_sorting_category_products', ['category_id'])
            ->where('product_id = ?', $productId);

        $rows = $this->connection->fetchAll($select);

        $categoryIds = [];

        foreach ($rows as $row) {
            if (!empty($row['category_id'])) {
                $categoryIds[] = (int)$row['category_id'];
            }
        }

        return $categoryIds;
    }

    public function updateSortingProductPoints($productId, $points)
    {
        $data = [
            'total_points' => $points
        ];

        $where = [
            'product_id = ?' => $productId
        ];

        $this->connection->update('artm_sorting_products', $data, $where);

        return true;
    }

    public function updateSingleCatPrdPts($catid, $prd_id, $points){

        $data = [            
            'sorting_points' => $points
        ];
        $where = [ 'product_id = ?' => $prd_id,'category_id = ?' => $catid];
        
        $this->connection->update('catalog_category_product', $data, $where);
    }
     public function runCronForAttrset($attrid, $type = 'partial'){
        $attrid = (int) $attrid;

           $productCollection = $this->productCollectionFactory->create();
           $productCollection->addAttributeToSelect('entity_id');
           $productCollection->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH); 
           $productCollection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);         
           $productCollection->addAttributeToFilter('attribute_set_id', $attrid);

        $collection =  $productCollection->load();
        $count = $collection->count();

        if($count > 0){
           
            $insertedId = $this->insertCronLog($attrid);

            /*foreach ($collection as $product){
                $prd_id = $product->getEntityId();
                $this->insertPoints($attrid, $prd_id);
                $this->insertProcessedProductsTmp($attrid, $prd_id);                
            } */ 

            //get already existing products, insert new ones only
            $select = $this->connection->select()
            ->from(self::TBL_SORT_PRODUCTS, 'product_id');
            $existingprdids = $this->connection->fetchCol($select);

            //create array 
            $prdids = [];            
            foreach ($collection as $product){ 
                $prd_id = $product->getEntityId();               
                $prdids[] = $prd_id;  
            }
            if(!empty($prdids) && !empty($prdids)){
                $diffArray = array_diff($prdids, $existingprdids);
            }else{
                $diffArray = $prdids;
            }
            $diffArray = array_diff($prdids, $existingprdids);
            

            //get, insert points
            $prod_points = [];
            $arrayToProceed = [];
            if($type == 'full'){
                $arrayToProceed = $prdids;
            }else{
                $arrayToProceed = $diffArray;
            }
            if(!empty($arrayToProceed)){      
                foreach($arrayToProceed as $pid){
                    $this->UpdatePoints($pid , 'cron');
                   /* $prdpts = $this->gettotalpoints($pid);
                    $prod_points[$pid]= $prdpts;
                    $this->insertPoints($attrid, $pid);
                    $this->insertProcessedProducts($attrid, $pid);*/
                } 
            } 
            
            $this->updateCronLog($insertedId);

            //update catalog_category_product
          //  if(!empty($prod_points)){
               // $this->updateCategoryProductsPoints($prod_points);
           // }
            
        }
    } 
}