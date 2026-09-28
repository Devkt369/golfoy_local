<?php
namespace ArtM\RecommendedSort\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\RecommendedSort\Model\GetProductData;
use ArtM\RecommendedFilter\Helper\Data;
use ArtM\RecommendedSort\Model\SortingJobFactory;
use ArtM\RecommendedSort\Helper\Data as RecommendedSortHelper;
use ArtM\RecommendedFilter\Model\ArtmSortingProductsFactory;
use ArtM\RecommendedSort\Logger\SortingLogLogger;

class Recalculate extends Action
{
    protected $resultJsonFactory;
    protected $getProductData;
    protected $dataHelper;
    protected $sortingJobFactory;
    protected $RecommendedSortHelper;
    protected $artmSortingProductsFactory;
    protected $sortingLogLogger;

    public function __construct(
        Context $context,
        GetProductData $getProductData,
        JsonFactory $resultJsonFactory,
        Data $dataHelper,
        SortingJobFactory $sortingJobFactory,
        RecommendedSortHelper $recommendedSortHelper,
        ArtmSortingProductsFactory $artmSortingProductsFactory,
        SortingLogLogger $sortingLogLogger
    ) {
        parent::__construct($context);
        $this->getProductData = $getProductData;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->dataHelper = $dataHelper;
        $this->sortingJobFactory = $sortingJobFactory;
        $this->RecommendedSortHelper = $recommendedSortHelper;
        $this->artmSortingProductsFactory = $artmSortingProductsFactory;
        $this->sortingLogLogger = $sortingLogLogger;
    }

    public function execute()
    {
        $fetchCategoryBatch = [];
        $productId = (int) $this->getRequest()->getParam('product_id');
        $categoryId = (int) $this->getRequest()->getParam('category_id');
        $result = $this->resultJsonFactory->create();
        if (!$categoryId || !$productId) {
            return $result->setData(['error' => true, 'message' => 'Missing category_id parameter']);
        }
        try{
            $sortingJobFactory = $this->sortingJobFactory->create()->getCollection()
                ->addFieldToFilter('categories', [
                    ['eq' => $categoryId], // exact
                    ['like' => $categoryId . ',%'], // starts with "2,"
                    ['like' => '%,' . $categoryId], // ends with ",2"
                    ['like' => '%,' . $categoryId . ',%'] // in the middle ",2,"
                ])
                ->addFieldToSelect(['id', 'filters']);
            if(count($sortingJobFactory)){
                $fetchCategoryBatch = $sortingJobFactory->getData();       
            }else{
                $productPoints = [];
                $filters = ["created_date","rating","high_discount","salable_qty","availability_compare","feature","size_roll"];
                $this->RecommendedSortHelper->RunForSingleProduct($productId, json_encode($filters));
                $productPoints[$productId] = $this->getRecalculatedPoints($productId);
                $this->dataHelper->updateCategoryProductsPoints($productPoints , $categoryId);

                $this->sortingLogLogger->info("Recalculation for Category ID: {$categoryId} with Product ID: {$productId} .");
                
                return $result->setData(['error' => false, 'message' => 'index_all','product-data' => json_encode($productPoints), "category_id" => $categoryId]);
            }
        }catch(\Exception $e){
            return $result->setData(['error' => true, 'message' => 'Something went wrong']);
        }
        $this->sortingLogLogger->info("Recalculation for Category ID: {$categoryId} with Product ID: {$productId} .");

        return $result->setData([
            'error' => false,
            'data' => $fetchCategoryBatch,
            'category_id' => $categoryId
        ]);
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
}
