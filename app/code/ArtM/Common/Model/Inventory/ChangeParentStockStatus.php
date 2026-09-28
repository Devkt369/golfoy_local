<?php
namespace ArtM\Common\Model\Inventory;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class ChangeParentStockStatus extends \Magento\ConfigurableProduct\Model\Inventory\ChangeParentStockStatus
{
    private $configurableType;
    private $criteriaInterfaceFactory;
    private $stockItemRepository;
    private $stockConfiguration;

    public function __construct(
        Configurable $configurableType,
        StockItemCriteriaInterfaceFactory $criteriaInterfaceFactory,
        StockItemRepositoryInterface $stockItemRepository,
        StockConfigurationInterface $stockConfiguration
    ) {
        parent::__construct(
            $configurableType,
            $criteriaInterfaceFactory,
            $stockItemRepository,
            $stockConfiguration
        );

        $this->configurableType = $configurableType;
        $this->criteriaInterfaceFactory = $criteriaInterfaceFactory;
        $this->stockItemRepository = $stockItemRepository;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * Override: Update stock status of configurable product based on children products stock status
     *
     * @param int $productId
     * @return void
     */
    protected function processStockForParent(int $productId): void
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $StockState = $objectManager->get(\Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku::class);

        $criteria = $this->criteriaInterfaceFactory->create();
        $criteria->setScopeFilter($this->stockConfiguration->getDefaultScopeId());
        $criteria->setProductsFilter($productId);

        $stockItemCollection = $this->stockItemRepository->getList($criteria);
        $allItems = $stockItemCollection->getItems();
        if (empty($allItems)) {
            return;
        }

        $parentStockItem = array_shift($allItems);

        $childrenIds = $this->configurableType->getChildrenIds($productId);
        $criteria->setProductsFilter($childrenIds);
        $stockItemCollection = $this->stockItemRepository->getList($criteria);
        $allItems = $stockItemCollection->getItems();

        $childrenIsInStock = false;

        foreach ($allItems as $childItem) {
            $qtyOfAllVarient = 0;
            $pid = $childItem->getData('product_id');
            $prModel = $objectManager->create(\Magento\Catalog\Model\Product::class)->load($pid);

            $isManageStock = $prModel->getExtensionAttributes()->getStockItem()->getManageStock();
            if ($isManageStock == 1) {
                $qtysrc = $StockState->execute($prModel->getSku());
                $tqty = 0;
                foreach ($qtysrc as $src) {
                    if ($src['stock_id'] != 1) {
                        $tqty += $src['qty'];
                    }
                }
                $qtyOfAllVarient += $tqty;
            } else {
                $qtyOfAllVarient = 1;
            }

            if ($qtyOfAllVarient > 0) {
                $childrenIsInStock = true;
                break;
            }
        }

        if ($this->isNeedToUpdateParent($parentStockItem, $childrenIsInStock)) {
            $parentStockItem->setIsInStock($childrenIsInStock);
            $parentStockItem->setStockStatusChangedAuto(1);
            $parentStockItem->setStockStatusChangedAutomaticallyFlag(true);
            $this->stockItemRepository->save($parentStockItem);
        }
    }
}
