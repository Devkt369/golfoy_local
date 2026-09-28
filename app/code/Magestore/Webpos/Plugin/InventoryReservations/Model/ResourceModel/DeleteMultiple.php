<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Webpos\Plugin\InventoryReservations\Model\ResourceModel;

/**
 * Webpos - DeleteMultiple
 */
class DeleteMultiple
{
    /**
     * @var \Magestore\Webpos\Model\ResourceModel\Inventory\Stock\Item
     */
    protected $stockItemResource;

    /**
     * DeleteMultiple constructor.
     * @param \Magestore\Webpos\Model\ResourceModel\Inventory\Stock\Item $stockItemResource
     */
    public function __construct(
        \Magestore\Webpos\Model\ResourceModel\Inventory\Stock\Item $stockItemResource
    ) {
        $this->stockItemResource = $stockItemResource;
    }

    /**
     * After Execute
     *
     * @param \Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple $subject
     * @param void|null $result
     * @param \Magento\InventoryApi\Api\Data\SourceItemInterface[] $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        $subject,
        $result,
        array $sourceItems
    ) {
        $skus = [];
        foreach ($sourceItems as $sourceItem) {
            $skus[] = $sourceItem->getSku();
        }
        if (!empty($skus)) {
            $this->stockItemResource->updateUpdatedTimeBySku($skus);
        }
    }
}
