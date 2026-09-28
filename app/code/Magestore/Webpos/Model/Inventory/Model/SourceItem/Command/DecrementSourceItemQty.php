<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Webpos\Model\Inventory\Model\SourceItem\Command;

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magestore\Webpos\Model\ResourceModel\Inventory\SourceItem\DecrementQtyForMultipleSourceItem;
use Magento\Inventory\Model\SourceItem\Validator\SourceItemsValidator;
use Psr\Log\LoggerInterface;

/**
 * Decrement quantity for source item
 */
class DecrementSourceItemQty
{
    /**
     * @var SourceItemsValidator
     */
    private $sourceItemsValidator;

    /**
     * @var DecrementQtyForMultipleSourceItem
     */
    private $decrementSourceItem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetSourceItemIds
     */
    private $getSourceItemIds;

    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @var SynchronizeLegacyStockAfterDecrementStock
     */
    private $synchronizeLegacyStockAfterDecrementStock;

    /**
     * DecrementSourceItemQty constructor.
     *
     * @param SourceItemsValidator $sourceItemsValidator
     * @param DecrementQtyForMultipleSourceItem $decrementSourceItem
     * @param LoggerInterface $logger
     * @param GetSourceItemIds $getSourceItemIds
     * @param SourceItemIndexer $sourceItemIndexer
     * @param SynchronizeLegacyStockAfterDecrementStock $synchronizeLegacyStockAfterDecrementStock
     */
    public function __construct(
        SourceItemsValidator $sourceItemsValidator,
        DecrementQtyForMultipleSourceItem $decrementSourceItem,
        LoggerInterface $logger,
        GetSourceItemIds $getSourceItemIds,
        SourceItemIndexer $sourceItemIndexer,
        SynchronizeLegacyStockAfterDecrementStock $synchronizeLegacyStockAfterDecrementStock
    ) {
        $this->sourceItemsValidator = $sourceItemsValidator;
        $this->decrementSourceItem = $decrementSourceItem;
        $this->logger = $logger;
        $this->getSourceItemIds = $getSourceItemIds;
        $this->sourceItemIndexer = $sourceItemIndexer;
        $this->synchronizeLegacyStockAfterDecrementStock = $synchronizeLegacyStockAfterDecrementStock;
    }

    /**
     * Decrement quantity for Multiple Source
     *
     * @param array $sourceItemDecrementData
     * @return void
     * @throws InputException
     * @throws ValidationException
     * @throws CouldNotSaveException
     */
    public function execute(array $sourceItemDecrementData): void
    {
        $this->validateSourceItems($sourceItemDecrementData);
        try {
            $this->decrementSourceItem->execute($sourceItemDecrementData);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not save Source Item'), $e);
        }
        $sourceItems = array_column($sourceItemDecrementData, 'source_item');
        $sourceItemIds = $this->getSourceItemIds->execute($sourceItems);
        if (count($sourceItemIds)) {
            $this->sourceItemIndexer->executeList($sourceItemIds);
        }
        $this->synchronizeLegacyStockAfterDecrementStock->execute($sourceItemDecrementData);
    }

    /**
     * Validate source items data
     *
     * @param array $sourceItemDecrementData
     * @return void
     * @throws InputException
     * @throws ValidationException
     */
    private function validateSourceItems(array $sourceItemDecrementData): void
    {
        $sourceItems = array_column($sourceItemDecrementData, 'source_item');
        if (empty($sourceItems)) {
            throw new InputException(__('Input data is empty'));
        }
        $validationResult = $this->sourceItemsValidator->validate($sourceItems);
        if (!$validationResult->isValid()) {
            $error = current($validationResult->getErrors());
            throw new ValidationException(__('Validation Failed: ' . $error), null, 0, $validationResult);
        }
    }
}
