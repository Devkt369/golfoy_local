<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\BranchRequest\Observer\Product;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\App\RequestInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalog\Model\GetDefaultSourceItemBySku;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magestore\BranchRequest\Ui\DataProvider\Product\Form\Modifier\Racket;
use Psr\Log\LoggerInterface;

/**
 * Class BranchRequest ProductSaveAfter
 */
class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var IsSingleSourceModeInterface
     */
    protected $isSingleSourceMode;

    /**
     * @var GetDefaultSourceItemBySku
     */
    private $getDefaultSourceItemBySku;

    /**
     * @var SourceItemsSaveInterface
     */
    protected $sourceItemsSave;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ProductSaveAfter constructor.
     *
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param RequestInterface $request
     * @param GetDefaultSourceItemBySku $getDefaultSourceItemBySku
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param LoggerInterface $logger
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        RequestInterface $request,
        GetDefaultSourceItemBySku $getDefaultSourceItemBySku,
        SourceItemsSaveInterface $sourceItemsSave,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->getDefaultSourceItemBySku = $getDefaultSourceItemBySku;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->logger = $logger;
    }

    /**
     * Update Racket to source_inventory_item table
     *
     * @param EventObserver $observer
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function execute(EventObserver $observer)
    {
        // TODO: Implement execute() method.
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getProduct();
        $productSku = $product->getSku();
        if ($productSku) {
            // Check if website use single source
            if ($this->isSingleSourceMode->execute() === true) {
                $racket = $this->request->getParam('product')[Racket::RACKET] ?? '';
                // Update racket to inventory_source_item
                if ($racket) {
                    $sourceItem = $this->getDefaultSourceItemBySku->execute($productSku);
                    if ($sourceItem !== null) {
                        /** @var SourceItemInterface $sourceItem */
                        try {
                            $sourceItem->setRacket($racket)->save();
                            $this->sourceItemsSave->execute([$sourceItem]);
                        } catch (\Exception $e) {
                            $this->logger->critical($e);
                        }
                    }
                }
            }
        }
    }
}
