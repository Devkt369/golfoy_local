<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterfaceFactory;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magestore\ClickAndCollectApi\Api\Order\IsEnoughQtyInStockInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolver OrderPickupStatusResolver
 */
class IsEnoughQtyInStock implements IsEnoughQtyInStockInterface
{
    /**
     * @var GetStockItemConfigurationInterface
     */
    protected $getStockItemConfiguration;

    /**
     * @var GetStockItemDataInterface
     */
    protected $getStockItemData;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    protected $isSourceItemManagementAllowedForProductType;

    /**
     * @var IsProductSalableForRequestedQtyInterface
     */
    protected $isProductSalableForRequestedQty;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * IsEnoughQtyInStock constructor.
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetStockItemDataInterface $getStockItemData
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        GetStockItemDataInterface $getStockItemData,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        LoggerInterface $logger
    ) {
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getStockItemData = $getStockItemData;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->isProductSalableForRequestedQty = $isProductSalableForRequestedQty;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        $order,
        LocationInterface $store
    ): bool {
        $isEnoughQty = true;
        $items = $order->getAllItems();
        $stockId = $store->getStockId();
        try {
            foreach ($items as $item) {
                $isSourceItemManageAllowed = $this->isSourceItemManagementAllowedForProductType->execute(
                    $item->getProductType()
                );
                if ($isSourceItemManageAllowed) {
                    $result = $this->isProductSalableForRequestedQty->execute(
                        $item->getSku(),
                        $stockId,
                        (float) $item->getQtyToShip()
                    );
                    if (!$result->isSalable()) {
                        $isEnoughQty = false;
                        break;
                    }
                }
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $isEnoughQty;
    }
}
