<?php

namespace ArtM\RecommendedSort\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Model\CategoryFactory;
use ArtM\RecommendedSort\Logger\Logger;
use ArtM\RecommendedSort\Logger\SortingQueueLogLogger;
use ArtM\RecommendedSort\Logger\SortingLogLogger;
use ArtM\RecommendedSort\Model\SortingJobFactory;
use ArtM\RecommendedSort\Model\RecommendQueueFactory;
use ArtM\RecommendedSort\Model\GetProductData;
use ArtM\RecommendedFilter\Helper\Data as FilterHelper;
use Magento\Framework\Shell;
use Magento\Framework\Shell\CommandRenderer;
use ArtM\RecommendedFilter\Model\ArtmSortingProductsFactory;
use ArtM\RecommendedSort\Model\SortingCategoryProductsFactory;

class Data extends AbstractHelper
{
    
    const CRON_GROUP = 'prioritycriteria/general/queue_process_cron';
    protected $categoryFactory;
    protected $logger;
    protected $sortingJobFactory;
    protected $getProductData;
    protected $recommendedFilterHelper;
    protected $shell;
    protected $dir;
    protected $artmSortingProductsFactory;
    protected $recommendQueueFactory;
    protected $sortingCategoryProductsFactory;
    protected $sortingQueueLogLogger;
    protected $sortingLogLogger;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        CategoryFactory $categoryFactory,
        Logger $logger,
        SortingJobFactory $sortingJobFactory,
        GetProductData $getProductData,
        FilterHelper $recommendedFilterHelper,
        ArtmSortingProductsFactory $artmSortingProductsFactory,
        RecommendQueueFactory $recommendQueueFactory,
        SortingCategoryProductsFactory $sortingCategoryProductsFactory,
        SortingQueueLogLogger $sortingQueueLogLogger,
        SortingLogLogger $sortingLogLogger
    ) {
        parent::__construct($context);
        $this->categoryFactory = $categoryFactory;
        $this->logger = $logger;
        $this->sortingJobFactory = $sortingJobFactory;
        $this->getProductData = $getProductData;
        $this->recommendedFilterHelper = $recommendedFilterHelper;
        $this->dir = $dir;
        $this->artmSortingProductsFactory = $artmSortingProductsFactory;
        $this->recommendQueueFactory = $recommendQueueFactory;
        $this->sortingCategoryProductsFactory = $sortingCategoryProductsFactory;
        $this->sortingQueueLogLogger = $sortingQueueLogLogger;
        $this->sortingLogLogger = $sortingLogLogger;
        $this->shell = new Shell(new CommandRenderer());
    }

    public function getHelloMessage()
    {
        return "Hello from Helper/Data.php";
    }

    /**
     * Get full category tree starting from category ID 2 (Default Category)
     */
    public function getCategoryTree($parentId = 2)
    {
        $category = $this->categoryFactory->create()->load($parentId);
        $tree = [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'children' => []
        ];

        $childCategories = $category->getChildrenCategories();

        foreach ($childCategories as $child) {
            if ($child->getIsActive()) {
                $tree['children'][] = $this->getCategoryTree($child->getId());
            }
        }

        return $tree;
    }

    public function RunBatchAll()
    {
        $jobCollection = $this->sortingJobFactory->create()->getCollection();

        // $this->logger->logToFile("Cron job 'RunBatchAll' started.");
        // $this->logger->logToFile(sprintf("Found %d sorting jobs to process.", $jobCollection->getSize()));
        foreach ($jobCollection as $job) {
            date_default_timezone_set("Asia/Kolkata");  
            $currentDate = date('Y-m-d H:i:s' );
            $job->setStartedAt($currentDate);
            $job->setStatus('pending');
            $job->save();
        }
        foreach ($jobCollection as $job) {
            $this->RunBatch($job); 
        }
    }

    public function RunBatch($job = null)
    {
        date_default_timezone_set("Asia/Kolkata");
        try {
            if($job === null) {
                $this->logger->logToFile("No 'initiated' sorting jobs found to process.");
                return;
                // $jobCollection = $this->sortingJobFactory->create()->getCollection()->setOrder('started_at', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
                // ->setPageSize(1);
                // $jobCollection->getFirstItem();

                // if ($jobCollection->getSize() === 0) {
                //     $this->logger->logToFile("No 'initiated' sorting jobs found to process.");
                //     return;
                // }
            }
            $logFileName = "processing_batch_" . $job->getId() . ".log";

            $job->setData("output_log", $logFileName);
            $job->setStatus('Initiated');
              
            $currentDate = date('Y-m-d H:i:s' );
            $job->setStartedAt($currentDate);
            $job->save();

            $categories = explode(',', $job->getCategories());

            foreach ($categories as $category) {
                
                $this->logger->logToFile("Initiating batch ".$job->getId()." at " . $currentDate, $logFileName);
                $filters = json_decode($job->getFilters(), true);
                $filters = array_map(function($item) {
                    $item = str_replace('_', ' ', $item);
                    return ucwords($item);
                }, $filters);

                $this->logger->logToFile("Filter used: ".implode(', ', $filters) , $logFileName);

                $this->RunForSingleCategory($category, $job->getFilters(), $logFileName);
            }
            
            $currentDate = date('Y-m-d H:i:s' );
            $job->setCompletedAt($currentDate);
            $job->setStatus('Completed');
            $job->save();

        } catch (\Exception $e) {
            $this->logger->logToFile(sprintf(
                "An error occurred for Job ID %s: %s. Stack Trace: %s",
                $job->getId(),
                $e->getMessage(),
                $e->getTraceAsString()
            ));
        }

        return true;
    }

    public function RunForSingleCategory($CategoryId, $filters, $logFileName = null){
        // $this->logger->logToFile($CategoryId. " <=CategoryId indexer => " .$filters);
        
        $productCollection = $this->getProductData->getProductsWithSortingPoints($CategoryId);
        foreach($productCollection as $product){
            $this->RunForSingleProduct($product['id'], $filters, $logFileName);

            $productPoints[$product['id']] = $this->getRecalculatedPoints($product['id']);
            $this->recommendedFilterHelper->updateCategoryProductsPoints($productPoints , $CategoryId);
        }
    }

    public function RunForSingleProduct($ProductId, $filters, $logFileName = null)
    {
        if($logFileName) {
            $this->logger->logToFile("Processing product id: " . $ProductId, $logFileName );
        }
        
        $this->recommendedFilterHelper->RunForSingleProduct($ProductId, $filters, $logFileName);

        if($logFileName) {
            $this->logger->logToFile("<><><><><><><>", $logFileName);
        }
    }

    public function runIndexerInBackground($indexerName)
    {
        $indexerName = escapeshellarg($indexerName);
        $rootDir = $this->dir->getRoot();
        $command = "cd $rootDir && nohup php bin/magento indexer:reindex $indexerName > /dev/null 2>&1 &";
        // $this->logger->logToFile("all indexer running: ".$indexerName);
        try {
            $this->shell->execute($command);
            // $this->logger->logToFile("all indexer completed: ".$indexerName);
        } catch (\Exception $e) {
            $this->_logger->error('Indexer background run failed: ' . $e->getMessage());
        }
    }

    public function runCmdInBackground($batchId)
    {
        $rootDir = $this->dir->getRoot();
        $command = "cd $rootDir && nohup php bin/magento artm_recommendedsort:process $batchId > /dev/null 2>&1 &";
        try {
            $this->sortingLogLogger->info("Batch ID {$batchId} reinitiated");
            $this->shell->execute($command);
            $this->sortingLogLogger->info("Batch ID {$batchId} reinitiated");
        } catch (\Exception $e) {
            $this->sortingLogLogger->info('Indexer background run failed: ' . $e->getMessage());
        }
    }

    public function updateForSingleBatch($batchId)
    {
        $sortingJobModel = $this->sortingJobFactory->create()->load((int)$batchId);
        $this->RunBatch($sortingJobModel);
        $this->sortingLogLogger->info("Batch ID {$batchId} completed process");
        return true;
    }

    public function getRecalculatedPoints($productId)
    {
        $collection = $this->artmSortingProductsFactory->create()
            ->getCollection()
            ->addFieldToFilter('product_id', $productId)
            ->addFieldToSelect('total_points')
            ->setPageSize(1);

        $item = $collection->getFirstItem();
        return $item->getData('total_points');
    }

    public function updateDiscription($productId, $descriptionData): bool
    {
        $descriptionData = json_decode($descriptionData , true);
        $productId = (int)$productId;
        $categoryId = (int)$descriptionData['category_id'];
        date_default_timezone_set("Asia/Kolkata");
        /** @var \ArtM\RecommendedSort\Model\SortingCategoryProducts $model */
        $model = $this->sortingCategoryProductsFactory->create();

        // Try to load existing record by product_id and category_id
        $existingRecord = $model->getCollection()
            ->addFieldToFilter('product_id', $productId)
            ->addFieldToFilter('category_id', $categoryId)
            ->setPageSize(1)
            ->getFirstItem();

        if ($existingRecord && $existingRecord->getId()) {
            // Update existing
            $existingRecord->setData('points', $descriptionData['points']);
            $existingRecord->setData('type_of_update', $descriptionData['type_of_update']);
            $existingRecord->setData('time', date('Y-m-d H:i:s'));
            $existingRecord->save();
        } else {
            // Insert new
            $model->setData([
                'product_id'      => $productId,
                'category_id'     => $categoryId,
                'points'          => $descriptionData['points'],
                'type_of_update'  => $descriptionData['type_of_update'],
                'time'            => date('Y-m-d H:i:s')
            ]);
            $model->save();
        }

        return true;
    }
    
    public function addProductToQueue($productId, $observer = null): bool
    {
        date_default_timezone_set("Asia/Kolkata");
        $queueItem = $this->recommendQueueFactory->create();
        $queueItem->setProductId($productId);
        $queueItem->setObserver($observer);
        $queueItem->setUpdatedAt(date('Y-m-d H:i:s'));
        try {
            $queueItem->save();
            $this->sortingQueueLogLogger->info("Product ID {$productId} added to queue successfully. Event: {$observer}");
            // $this->logger->logToFile("Product ID {$productId} added to queue successfully.");
            return true;
        } catch (\Exception $e) {
            // $this->logger->logToFile("Failed to add product ID {$productId} to queue: " . $e->getMessage());
            return false;
        }
    }

    public function queueProductRecaclulatePointsProcess() {
        $queueItem = $this->recommendQueueFactory->create();
        $collection = $queueItem->getCollection();

        if ($collection->getSize() === 0) {
            $this->logger->logToFile("No products in queue to process.");
            return;
        }

        foreach ($collection as $item) {
            $productId = $item->getProductId();
            $filters = ["created_date","rating","high_discount","salable_qty","availability_compare","feature","size_roll"];
            $this->sortingQueueLogLogger->info("Processing product ID {$productId} from queue.  Event: Cron");
            $this->RunForSingleProduct($productId, json_encode($filters));
            $item->delete(); 
            $this->sortingQueueLogLogger->info("Processed and removed product ID {$productId} from queue. Event: Cron");
        }
    }

    public function removeQueueItem($id)
    {
        $queueItem = $this->recommendQueueFactory->create();
        $queueItem->load($id);
        if ($queueItem->getId()) {
            $queueItem->delete();
            $this->sortingQueueLogLogger->info("Queue item with ID {$id} removed(Manually) successfully.");
            return true;
        } else {
           $this->sortingQueueLogLogger->info("Queue item with ID {$id} not found.");
            return false;   
        }
    }

    public function isQueueProcessCronEnabled(){
        return $this->scopeConfig->getValue(
            self::CRON_GROUP,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
