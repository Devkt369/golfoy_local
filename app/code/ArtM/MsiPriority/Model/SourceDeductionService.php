<?php
declare(strict_types=1);

namespace ArtM\MsiPriority\Model; // Your custom module's namespace

use Magento\Framework\Exception\LocalizedException;
use Magento\Inventory\Model\SourceItem\Command\DecrementSourceItemQty;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\InventoryConfigurationInterface;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku; // Keep this one too if you are using it

// Extend the original class
class SourceDeductionService extends \Magento\InventorySourceDeductionApi\Model\SourceDeductionService
{
    protected ResourceConnection $resource;
    protected GetStockItemConfigurationInterface $getStockItemConfiguration;
    protected GetStockBySalesChannelInterface $getStockBySalesChannel;
    protected DecrementSourceItemQty $decrementSourceItem;
    protected $inventoryConfiguration;
    protected GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku; // Keep this one too if you are using iterator_apply
    
    /**
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetStockBySalesChannelInterface $getStockBySalesChannel
     * @param DecrementSourceItemQty $decrementSourceItem
     * @param InventoryConfigurationInterface $inventoryConfiguration
     */
    public function __construct(
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        GetStockBySalesChannelInterface $getStockBySalesChannel,
        DecrementSourceItemQty $decrementSourceItem,
        ResourceConnection $resource,
        InventoryConfigurationInterface $inventoryConfiguration
    ) {
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getStockBySalesChannel = $getStockBySalesChannel;
        $this->decrementSourceItem = $decrementSourceItem;
        $this->resource = $resource;
        $this->inventoryConfiguration = $inventoryConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function execute(SourceDeductionRequestInterface $sourceDeductionRequest): void
    {
        // echo "<pre>";
        // print_r($sourceDeductionRequest);die;
        $sourceCode = $sourceDeductionRequest->getSourceCode();
        $orderId = $sourceDeductionRequest->getOrderId();
        $salesChannel = $sourceDeductionRequest->getSalesChannel();
        $stockId = $this->getStockBySalesChannel->execute($salesChannel)->getStockId();
        $sourceItemDecrementData = [];
        foreach ($sourceDeductionRequest->getItems() as $item) {
            $itemSku = $item->getSku();
            $qty = $item->getQty();
            $stockItemConfiguration = $this->getStockItemConfiguration->execute(
                $itemSku,
                $stockId
            );

            try{
                if($sourceDeductionRequest->getVinForceShipment() && $this->checkReservationTable($itemSku, $qty, $orderId)){
                    if ($stockItemConfiguration->isManageStock()) {
                        continue;
                    }
                }
            }catch(\Exception $e){

            }
            if (!$stockItemConfiguration->isManageStock()) {
                continue;
            }

            $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute($sourceCode, $itemSku);
            if (($sourceItem->getQuantity() - $qty) >= 0) {
                $sourceItem->setQuantity($sourceItem->getQuantity() - $qty);
                $stockStatus = $this->getSourceStockStatus( // This method is private in the parent, so you need to replicate it if you don't call parent::execute()
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

        if (!empty($sourceItemDecrementData)) {
            $this->decrementSourceItem->execute($sourceItemDecrementData);
        }
    }

    /**
     * Get source item stock status after quantity deduction.
     *
     * IMPORTANT: This method is private in the parent. If you completely override `execute` and don't call `parent::execute()`,
     * you will need to copy this method into your custom class if `execute` relies on it.
     *
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @param SourceItemInterface $sourceItem
     * @return int
     */
    private function getSourceStockStatus(
        StockItemConfigurationInterface $stockItemConfiguration,
        SourceItemInterface $sourceItem
    ): int {
        // You'll need to define this constant here as well if you don't call parent::execute()
        // or if you need to modify its value
        $ZERO_STOCK_QUANTITY = 0.0; // Define locally or as a class constant if needed

        $sourceItemQty = $sourceItem->getQuantity() ?: $ZERO_STOCK_QUANTITY;
        $currentStatus = (int)$stockItemConfiguration->getExtensionAttributes()->getIsInStock();
        $calculatedStatus =  SourceItemInterface::STATUS_IN_STOCK;

        if ($sourceItemQty === $stockItemConfiguration->getMinQty() && !$stockItemConfiguration->getBackorders()) {
            $calculatedStatus = SourceItemInterface::STATUS_OUT_OF_STOCK;
        }

        if ($this->inventoryConfiguration->isCanBackInStock() && $sourceItemQty > $stockItemConfiguration->getMinQty()
            && $currentStatus === SourceItemInterface::STATUS_OUT_OF_STOCK
        ) {
            return SourceItemInterface::STATUS_IN_STOCK;
        }

        return $currentStatus === SourceItemInterface::STATUS_OUT_OF_STOCK ? $currentStatus : $calculatedStatus;
    }

    /**
     * Returns true if reservation exists but is not yet released (based on order ID)
     *
     * @param string $sku
     * @param float|int $qty
     * @param int|string $orderId
     * @return bool
     */
    public function checkReservationTable($sku, $qty, $orderId): bool
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('inventory_reservation');

        $searchString = sprintf('"object_increment_id":"%s"', $orderId);
        // die('asdfsdf');
        $select = $connection->select()
            ->from($tableName, ['quantity'])
            ->where('sku = ?', $sku)
            ->where('metadata LIKE ?', '%' . $searchString . '%');

        $quantities = $connection->fetchCol($select);
        // var_dump($select);die;
        $reserved = false;
        $released = false;

        foreach ($quantities as $rowQty) {
            if ($rowQty <= -abs($qty)) {
                $reserved = true;
            }
            if ($rowQty >= abs($qty)) {
                $released = true;
            }
        }

        return $reserved && !$released;
    }
}