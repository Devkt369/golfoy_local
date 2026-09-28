<?php

namespace ArtM\PartnersAuthentication\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableType;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;

class GetSourceQty extends Action
{
    protected $resultJsonFactory;
    protected $productRepository;
    protected $sourceItems;
    protected $searchCriteriaBuilder;
    protected $configurableType;
    protected $getSalableQtyBySku;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductRepositoryInterface $productRepository,
        SourceItemRepositoryInterface $sourceItems,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ConfigurableType $configurableType,
        GetSalableQuantityDataBySku $getSalableQtyBySku
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productRepository = $productRepository;
        $this->sourceItems = $sourceItems;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->configurableType = $configurableType;
        $this->getSalableQtyBySku = $getSalableQtyBySku;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $sku = $this->getRequest()->getParam('sku');

        if (!$sku) {
            return $result->setData(['error' => true, 'message' => 'SKU parameter is required.']);
        }

        try {
            $sourceCode = $this->getAvailableSourceCode($sku);
            return $result->setData(['success' => true, 'qty' => $sourceCode]);
        } catch (\Exception $e) {
            return $result->setData(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    public function getAvailableSourceCode($sku)
    {
        $sourceCode = [];

        $product = $this->productRepository->get($sku);
        $tqty = 0;
        if ($product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            // $qtysrc = $this->getSalableQtyBySku->execute($sku);
            // print_r($qtysrc);die($sku);
            // $tqty = 0;
            // foreach ($qtysrc as $src) {
            //     if ($src['stock_id'] == 2) {
            //         $tqty += $src['qty'];
            //         continue;
            //     }
            // }
            // return $tqty;
            $childSkus = $this->configurableType->getChildrenIds($product->getId())[0];
            
            foreach ($childSkus as $childId) {
                $child = $this->productRepository->getById($childId);
                $childSku = $child->getSku();

                $qtysrc = $this->getSalableQtyBySku->execute($childSku);
                // print_r($qtysrc);die($sku);
                // $tqty = 0;
                foreach ($qtysrc as $src) {
                    if ($src['stock_id'] == 2) {
                        $tqty += $src['qty'];
                        continue;
                    }
                }
                // return $tqty;

                // $searchCriteria = $this->searchCriteriaBuilder
                //     ->addFilter('sku', $childSku)
                //     ->create();

                // $sourceItemData = $this->sourceItems->getList($searchCriteria);

                // foreach ($sourceItemData->getItems() as $sourceItem) {
                //     if ($sourceItem['status'] != 0) {
                //         $code = $sourceItem->getSourceCode();
                //         $qty = $sourceItem->getQuantity();

                //         if (!isset($sourceCode[$code])) {
                //             $sourceCode[$code] = 0;
                //         }

                //         $sourceCode[$code] += $qty;
                //     }
                // }
            }
            // $qtysrc = $this->getSalableQtyBySku->execute($sku);
            // print_r($qtysrc);die($sku);
            // $tqty = 0;
            // foreach ($qtysrc as $src) {
            //     if ($src['stock_id'] == 2) {
            //         $tqty += $src['qty'];
            //         continue;
            //     }
            // }
            return $tqty;
        } else {
            // $searchCriteria = $this->searchCriteriaBuilder
            //     ->addFilter('sku', $sku)
            //     ->create();
            // $sourceItemData = $this->sourceItems->getList($searchCriteria);
            
            // foreach ($sourceItemData->getItems() as $sourceItem) {
                //     if ($sourceItem['status'] != 0) {
                //         $sourceCode[$sourceItem->getSourceCode()] = $sourceItem->getQuantity();
                //     }
                // }
                $qtysrc = $this->getSalableQtyBySku->execute($sku);
                foreach ($qtysrc as $src) {
                    if ($src['stock_id'] == 2) {
                        $tqty += $src['qty'];
                        continue;
                    }
                }
            }

        return $tqty;
    }
}
