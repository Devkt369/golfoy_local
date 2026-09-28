<?php

namespace ArtM\Common\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableType;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;

class SourceQty extends Action
{
    protected $resultJsonFactory;
    protected $productRepository;
    protected $configurableType;
    protected $getSalableQtyBySku;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductRepositoryInterface $productRepository,
        ConfigurableType $configurableType,
        GetSalableQuantityDataBySku $getSalableQtyBySku
    ) {
        parent::__construct($context);
        $this->resultJsonFactory   = $resultJsonFactory;
        $this->productRepository  = $productRepository;
        $this->configurableType   = $configurableType;
        $this->getSalableQtyBySku = $getSalableQtyBySku;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $sku = $this->getRequest()->getParam('sku');

        if (!$sku) {
            return $result->setData([
                'success' => false,
                'qty'     => 0,
                'message' => __('SKU parameter is required.')
            ]);
        }

        try {
            $qty = $this->getAvailableSourceQty($sku);

            return $result->setData([
                'success' => true,
                'qty'     => $qty,
                'message' => null
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'qty'     => 0,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get available source quantity (stock_id = 2)
     */
    private function getAvailableSourceQty($sku)
    {
        $product = $this->productRepository->get($sku);
        $totalQty = 0;

        // Configurable product
        if ($product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {

            $childIds = $this->configurableType->getChildrenIds($product->getId())[0] ?? [];

            foreach ($childIds as $childId) {
                $child = $this->productRepository->getById($childId);
                $sources = $this->getSalableQtyBySku->execute($child->getSku());

                foreach ($sources as $src) {
                    if ((int)$src['stock_id'] === 2) {
                        $totalQty += (float)$src['qty'];
                    }
                }
            }

        } 
        // Simple product
        else {
            $sources = $this->getSalableQtyBySku->execute($sku);

            foreach ($sources as $src) {
                if ((int)$src['stock_id'] === 2) {
                    $totalQty += (float)$src['qty'];
                }
            }
        }

        return $totalQty;
    }
}
