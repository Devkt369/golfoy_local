<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Core\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magestore\Core\Api\SourceManagementInterface;

/**
 * Source Management Get Source Item Info
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
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * SourceManagement constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param SourceItemRepositoryInterface $sourceItemRepository
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SourceItemRepositoryInterface $sourceItemRepository
    ) {
        $this->objectManager = $objectManager;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->sourceItemRepository = $sourceItemRepository;
    }

    /**
     * @inheritDoc
     */
    public function getSourceItemsMap(string $sku, array $sourceCodes)
    {
        $searchCriteria = $this->searchCriteriaBuilderFactory
            ->create()
            ->addFilter(ProductInterface::SKU, $sku)
            ->addFilter('source_code', $sourceCodes, 'in')
            ->create();

        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
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
    public function getListProductInSource(string $sourceCode, array $skus = [])
    {
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

        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        $sourceItemsMap = [];
        if ($sourceItems) {
            foreach ($sourceItems as $sourceItem) {
                $sourceItemsMap[$sourceItem->getSku()] = $sourceItem;
            }
        }
        return $sourceItemsMap;
    }
}
