<?php

namespace Magestore\Webpos\Plugin\InventorySourceDeductionApi\Model;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magento\Sales\Model\OrderRepository;
use Magestore\Webpos\Api\MultiSourceInventory\StockManagementInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionService as CoreSourceDeductionService;
use Magestore\Webpos\Model\Inventory\Model\SourceItem\Command\DecrementSourceItemQty;
use Magento\Framework\App\ObjectManager;

/**
 * Plugin Source Deduction Service
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SourceDeductionService
{
    const ZERO_STOCK_QUANTITY = 0.0;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;
    /**
     * @var GetSourceItemBySourceCodeAndSku
     */
    private $getSourceItemBySourceCodeAndSku;
    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;
    /**
     * @var GetStockBySalesChannelInterface
     */
    private $getStockBySalesChannel;
    /**
     * @var StockManagementInterface
     */
    protected $stockManagement;
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var array
     */
    protected $stockItemConfiguration;

    /**
     * @var DecrementSourceItemQty
     */
    private $decrementSourceItem;

    /**
     * SourceDeductionService constructor.
     *
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetStockBySalesChannelInterface $getStockBySalesChannel
     * @param StockManagementInterface $stockManagement
     * @param OrderRepository $orderRepository
     * @param DecrementSourceItemQty $decrementSourceItem
     */
    public function __construct(
        SourceItemsSaveInterface $sourceItemsSave,
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        GetStockBySalesChannelInterface $getStockBySalesChannel,
        StockManagementInterface $stockManagement,
        OrderRepository $orderRepository,
        DecrementSourceItemQty $decrementSourceItem
    ) {
        $this->sourceItemsSave = $sourceItemsSave;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getStockBySalesChannel = $getStockBySalesChannel;
        $this->stockManagement = $stockManagement;
        $this->orderRepository = $orderRepository;
        if ($this->isMSIFixedDeductionService()) {
            $this->decrementSourceItem = ObjectManager::getInstance()->get(
                // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
                'Magento\Inventory\Model\SourceItem\Command\DecrementSourceItemQty'
            );
        } else {
            $this->decrementSourceItem = $decrementSourceItem;
        }
    }

    /**
     * Around execute
     *
     * @param CoreSourceDeductionService $subject
     * @param callable $proceed
     * @param SourceDeductionRequestInterface $sourceDeductionRequest
     * @return bool
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Validation\ValidationException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function aroundExecute(
        CoreSourceDeductionService $subject,
        callable $proceed,
        SourceDeductionRequestInterface $sourceDeductionRequest
    ) {
        $sourceItemDecrementData = [];
        $sourceCode = $sourceDeductionRequest->getSourceCode();
        $salesEvent = $sourceDeductionRequest->getSalesEvent();
        $salesEventType = $salesEvent->getType();
        $salesEventObjectType = $salesEvent->getObjectType();

        if ((
                $salesEventType == SalesEventInterface::EVENT_ORDER_CANCELED ||
                $salesEventType == SalesEventInterface::EVENT_SHIPMENT_CREATED ||
                $salesEventType == SalesEventInterface::EVENT_CREDITMEMO_CREATED
            ) && $salesEventObjectType == SalesEventInterface::OBJECT_TYPE_ORDER) {
            /* integrated with POS */
            $orderId = $salesEvent->getObjectId();
            $order = $this->orderRepository->get($orderId);
            $stockId = $this->stockManagement->getStockIdFromOrder($order);
            $locationStockId = $this->stockManagement->getStockId();
            if (!$stockId) {
                if ($salesEventType == SalesEventInterface::EVENT_CREDITMEMO_CREATED) {
                    if ($locationStockId) {
                        $stockId = $locationStockId;
                    }
                }
                if (!$stockId) {
                    return $proceed($sourceDeductionRequest);
                }
            }
            foreach ($sourceDeductionRequest->getItems() as $item) {
                $itemSku = $item->getSku();
                $qty = $item->getQty();
                try {
                    if (!$this->isManageStockSku($itemSku, $stockId)) {
                        continue;
                    }
                } catch (Exception $e) {
                    if ($salesEventType != SalesEventInterface::EVENT_CREDITMEMO_CREATED) {
                        throw $e;
                    }
                    if (!$locationStockId) {
                        throw $e;
                    }
                    $sources = $this->stockManagement->getLinkedSourceCodesByStockId($locationStockId);
                    if (empty($sources)) {
                        throw $e;
                    }
                    $sourceCode = $sources[0];
                    $this->stockManagement->createSourceItem($itemSku, $sourceCode);
                }
                if ($salesEventType == SalesEventInterface::EVENT_CREDITMEMO_CREATED) {
                    if ($locationStockId) {
                        $sources = $this->stockManagement->getLinkedSourceCodesByStockId($locationStockId);
                        if (!empty($sources)) {
                            $sourceCode = $sources[0];
                        }
                    }
                }
                $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute($sourceCode, $itemSku);
                $stockItemConfiguration = $this->getCurrentStockItemConfiguration($itemSku, $stockId);
                if ($locationStockId && $salesEventType == SalesEventInterface::EVENT_SHIPMENT_CREATED) {
                    $sourceItem->setQuantity($sourceItem->getQuantity() - $qty);
                    $stockStatus = $this->getSourceStockStatus(
                        $stockItemConfiguration,
                        $sourceItem
                    );
                    $sourceItem->setStatus($stockStatus);
                    $sourceItemDecrementData[] = [
                        'source_item' => $sourceItem,
                        'qty_to_decrement' => $qty
                    ];
                } else {
                    if (($sourceItem->getQuantity() - $qty) >= 0) {
                        $sourceItem->setQuantity($sourceItem->getQuantity() - $qty);
                        $stockStatus = $this->getSourceStockStatus(
                            $stockItemConfiguration,
                            $sourceItem
                        );
                        $sourceItem->setStatus($stockStatus);
                        $sourceItemDecrementData[] = [
                            'source_item' => $sourceItem,
                            'qty_to_decrement' => $qty
                        ];
                    } else {
                        throw new LocalizedException(
                            __('Not all of your products are available in the requested quantity.')
                        );
                    }
                }
            }
            if (!empty($sourceItemDecrementData)) {
                $this->decrementSourceItem->execute($sourceItemDecrementData);
            }
            return true;
        }
        return $proceed($sourceDeductionRequest);
    }

    /**
     * Is Manage Stock Sku
     *
     * @param string $itemSku
     * @param int $stockId
     * @return bool
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     */
    public function isManageStockSku($itemSku, $stockId)
    {
        $stockItemConfiguration = $this->getCurrentStockItemConfiguration(
            $itemSku,
            $stockId
        );
        if (!$stockItemConfiguration->isManageStock()) {
            //We don't need to Manage Stock
            return false;
        }
        return true;
    }

    /**
     * Get Stock Item Configuration
     *
     * @param string $itemSku
     * @param int $stockId
     * @return StockItemConfigurationInterface|mixed
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     */
    public function getCurrentStockItemConfiguration(string $itemSku, int $stockId)
    {
        if (!isset($this->stockItemConfiguration[$itemSku . '-' . $stockId])) {
            $stockItemConfiguration = $this->getStockItemConfiguration->execute(
                $itemSku,
                $stockId
            );
            $this->stockItemConfiguration[$itemSku . '-' . $stockId] = $stockItemConfiguration;
        }
        return $this->stockItemConfiguration[$itemSku . '-' . $stockId];
    }

    /**
     * Get source item stock status after quantity deduction.
     *
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @param SourceItemInterface $sourceItem
     * @return int
     */
    public function getSourceStockStatus(
        StockItemConfigurationInterface $stockItemConfiguration,
        SourceItemInterface $sourceItem
    ): int {
        $sourceItemQty = $sourceItem->getQuantity() ?: self::ZERO_STOCK_QUANTITY;
        $currentStatus = (int)$stockItemConfiguration->getExtensionAttributes()->getIsInStock();
        $calculatedStatus = $sourceItemQty === $stockItemConfiguration->getMinQty()
        && !$stockItemConfiguration->getBackorders()
            ? SourceItemInterface::STATUS_OUT_OF_STOCK
            : SourceItemInterface::STATUS_IN_STOCK;

        return $currentStatus === SourceItemInterface::STATUS_OUT_OF_STOCK ? $currentStatus : $calculatedStatus;
    }

    /**
     * Check if MSI apply deduction service patch or not
     *
     * @return bool
     */
    private function isMSIFixedDeductionService()
    {
        // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
        if (class_exists('Magento\Inventory\Model\SourceItem\Command\DecrementSourceItemQty')) {
            return true;
        } else {
            return false;
        }
    }
}
