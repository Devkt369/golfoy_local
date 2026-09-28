<?php
namespace ArtM\MsiPriority\Observer;

use ArtM\MsiPriority\Service\SyncManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductSaveSyncObserver implements ObserverInterface
{
    /**
     * @var SyncManager
     */
    private $syncManager;

    public function __construct(
        SyncManager $syncManager,
)
    {
        $this->syncManager = $syncManager;
    }

    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        if ($product->getTypeId() === \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE) {
            $this->syncManager->syncProduct($product, (int) $product->getStoreId());
            $this->syncManager->mapProductChannel($product, [], (int) $product->getStoreId());
        }
    }
}
