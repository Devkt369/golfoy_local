<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\TransferStock\Model\MultiSourceInventory;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magestore\TransferStock\Api\MultiSourceInventory\SourceManagementInterface;

/**
 * Class SourceManagement
 *
 * Use for source management service
 */
class SourceManagement implements SourceManagementInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->objectManager = $objectManager;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * Get source items map by sku and source code
     *
     * @param string $sku
     * @param array $sourceCodes
     * @return SourceItemInterface[]
     */
    public function getSourceItemsMap($sku, $sourceCodes)
    {
        $searchCriteria = $this->searchCriteriaBuilderFactory
            ->create()
            ->addFilter(ProductInterface::SKU, (string) $sku)
            ->addFilter('source_code', $sourceCodes, 'in')
            ->create();
        /** @var SourceItemRepositoryInterface $sourceItemRepository */
        $sourceItemRepository = $this->objectManager->get(
            SourceItemRepositoryInterface::class
        );
        $sourceItems = $sourceItemRepository->getList($searchCriteria)->getItems();
        $sourceItemsMap = [];
        if ($sourceItems) {
            foreach ($sourceItems as $sourceItem) {
                $sourceItemsMap[$sourceItem->getSourceCode()] = $sourceItem;
            }
        }
        return $sourceItemsMap;
    }

    /**
     * @inheritDoc
     */
    public function getListProductInSource($sourceCode, $skus = [])
    {
        foreach ($skus as $index => $sku) {
            $skus[$index] = (string) $sku;
        }
        if (count($skus)) {
            $searchCriteria = $this->searchCriteriaBuilderFactory
                ->create()
                ->addFilter('source_code', $sourceCode)
                ->addFilter(ProductInterface::SKU, $skus, 'in')
                ->create();
        } else {
            $searchCriteria = $this->searchCriteriaBuilderFactory
                ->create()
                ->addFilter('source_code', $sourceCode)
                ->create();
        }
        /** @var SourceItemRepositoryInterface $sourceItemRepository */
        $sourceItemRepository = $this->objectManager->get(
            SourceItemRepositoryInterface::class
        );
        $sourceItems = $sourceItemRepository->getList($searchCriteria)->getItems();
        $sourceItemsMap = [];
        if ($sourceItems) {
            foreach ($sourceItems as $sourceItem) {
                $sourceItemsMap[$sourceItem->getSku()] = $sourceItem;
            }
        }
        return $sourceItemsMap;
    }
}
