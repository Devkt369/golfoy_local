<?php
namespace ArtM\RecommendedSort\Controller\Adminhtml\Background;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\RecommendedSort\Model\GetProductData;
use ArtM\RecommendedFilter\Helper\Data as FilterHelper;
use ArtM\RecommendedSort\Helper\Data as RecommendedSortHelper;
use ArtM\RecommendedSort\Model\SortingJobFactory;
use ArtM\RecommendedFilter\Model\ArtmSortingProductsFactory;

class RecalculateProcess extends Action
{
    protected $resultJsonFactory;
    protected $getProductData;
    protected $FilterHelper;
    protected $RecommendedSortHelper;
    protected $sortingJobFactory;
    protected $artmSortingProductsFactory;

    public function __construct(
        Context $context,
        GetProductData $getProductData,
        JsonFactory $resultJsonFactory,
        FilterHelper $filterHelper,
        RecommendedSortHelper $recommendedSortHelper,
        SortingJobFactory $sortingJobFactory,
        ArtmSortingProductsFactory $artmSortingProductsFactory
    ) {
        parent::__construct($context);
        $this->getProductData = $getProductData;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->FilterHelper = $filterHelper;
        $this->RecommendedSortHelper = $recommendedSortHelper;
        $this->sortingJobFactory = $sortingJobFactory;
        $this->artmSortingProductsFactory = $artmSortingProductsFactory;
    }

    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $category_id = $this->getRequest()->getParam('category_id');
        $filtersString = $this->getRequest()->getParam('filters');

        $filters = $filtersString ? explode(',', $filtersString) : [];
        $productPoints = [];
        if ($productId && !empty($filters)) {
            $this->RecommendedSortHelper->RunForSingleProduct($productId, json_encode($filters));
            
            $productPoints[$productId] = $this->getRecalculatedPoints($productId);
            $this->FilterHelper->updateCategoryProductsPoints($productPoints , $category_id);
        }
        if ($productId && empty($filters)) {
            $filters = ["created_date","rating","high_discount","salable_qty","availability_compare","feature","size_roll"];
            $this->RecommendedSortHelper->RunForSingleProduct($productId, json_encode($filters));

            $productPoints[$productId] = $this->getRecalculatedPoints($productId);
            $this->FilterHelper->updateCategoryProductsPoints($productPoints , $category_id);
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData(
            [
                'success' => true,
                'message' => 'Sorting process started'
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
