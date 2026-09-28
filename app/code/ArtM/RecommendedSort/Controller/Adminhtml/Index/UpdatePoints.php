<?php
namespace ArtM\RecommendedSort\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\RecommendedSort\Model\GetProductData;
use ArtM\RecommendedFilter\Helper\Data;
use ArtM\RecommendedSort\Helper\Data as SortHelper;
use ArtM\RecommendedSort\Logger\SortingLogLogger;

class UpdatePoints extends Action
{
    protected $resultJsonFactory;
    protected $getProductData;
    protected $dataHelper;
    protected $sortHelper;
    protected $sortingLogLogger;

    public function __construct(
        Context $context,
        GetProductData $getProductData,
        JsonFactory $resultJsonFactory,
        Data $dataHelper,
        SortHelper $sortHelper,
        SortingLogLogger $sortingLogLogger
    ) {
        parent::__construct($context);
        $this->getProductData = $getProductData;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->dataHelper = $dataHelper;
        $this->sortHelper = $sortHelper;
        $this->sortingLogLogger = $sortingLogLogger;
    }

    public function execute()
    {
        $productPoints = [];

        $product_id = $this->getRequest()->getParam('product_id');
        $categoryId = $this->getRequest()->getParam('category_id');
        $points = $this->getRequest()->getParam('points');
        
        $result = $this->resultJsonFactory->create();
        date_default_timezone_set('Asia/Kolkata');
        if($product_id && $categoryId && $points) {
            $productPoints[$product_id] = $points;
            $forceUpdate = true;
            $this->dataHelper->updateCategoryProductsPoints($productPoints , $categoryId , $forceUpdate);
            $this->dataHelper->updateSortingProductPoints($product_id , $points);
            $discriptionData = [    
                'category_id' => $categoryId,
                'points' => $points,
                'type_of_update'=> 'manual',
                'time' => date('Y-m-d H:i:s'),
            ];
            $this->sortHelper->updateDiscription($product_id, json_encode($discriptionData));
            $this->sortingLogLogger->info("manual points added for Product ID: {$product_id} updated with points: {$points} and category id : {$categoryId}");
        }else{
            return $result->setData(['error' => true, 'message' => 'Missing parameter']);
        }

        return $result->setData([
            'error' => false,
            'message' => 'Points updated successfully',
        ]);
    }
}
