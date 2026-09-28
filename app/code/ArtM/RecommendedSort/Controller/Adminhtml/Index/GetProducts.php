<?php
namespace ArtM\RecommendedSort\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\RecommendedSort\Model\GetProductData;

class GetProducts extends Action
{
    protected $resultJsonFactory;
    protected $getProductData;

    public function __construct(
        Context $context,
        GetProductData $getProductData,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->getProductData = $getProductData;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $products = [];
        $categoryId = (int) $this->getRequest()->getParam('category_id');
        $result = $this->resultJsonFactory->create();
        if (!$categoryId) {
            return $result->setData(['error' => true, 'message' => 'Missing category_id parameter']);
        }
        $products = $this->getProductData->getProductsWithSortingPoints($categoryId);

        return $result->setData([
            'error' => false,
            'products' => $products,
        ]);
    }
}
