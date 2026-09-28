<?php
namespace ArtM\Sizeroll\Controller\Size;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Catalog\Model\ProductFactory;
use ArtM\Sizeroll\Model\SortingProducts;
use Magento\Framework\Controller\Result\JsonFactory;


class Batch extends Action
{
    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var SortingProducts
     */
    protected $sortingProducts;
    protected $jsonFactory; 

    public function __construct(
        Context $context,
        ProductFactory $productFactory,
        SortingProducts $sortingProducts,
        JsonFactory $jsonFactory    
    ) {
        $this->productFactory = $productFactory;
        $this->sortingProducts = $sortingProducts;
        $this->jsonFactory = $jsonFactory;
        parent::__construct($context);
    }

    public function execute()

    {
        $resultJson = $this->jsonFactory->create();

        $resultJson = $this->jsonFactory->create();
        $skus = [];
        $data = $this->getRequest()->getContent();
        if ($data) {
            $json = json_decode($data, true);
            if (isset($json['skus']) && is_array($json['skus'])) {
                $skus = array_filter($json['skus'], function($sku) {
                    return !empty($sku);
                });
            }
        }
        if (empty($skus)) {
            return $resultJson->setData([]);
        }
        $response = $this->sortingProducts->getAvailableSizesBySkus($skus);
        return $resultJson->setData($response);
    }
}
