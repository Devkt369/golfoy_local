<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magestore\InventoryMovementApi\Api\FilterSourceItemInterface;

/**
 * Filter source item by sku and source code
 *
 * Class FilterSourceItem
 */
class FilterSourceItem implements FilterSourceItemInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var SourceItemRepositoryInterface
     */
    protected $sourceItemRepository;

    /**
     * FilterSourceItem constructor.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemRepositoryInterface $sourceItemRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemRepositoryInterface $sourceItemRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemRepository = $sourceItemRepository;
    }

    /**
     * Get source item by sku and source code
     *
     * @param array $skus
     * @param array $sourceCodes
     * @return SourceItemInterface[]
     */
    public function getBySkusAndSourceCodes(array $skus, array $sourceCodes): array
    {
        return $this->sourceItemRepository->getList(
            $this->searchCriteriaBuilder->addFilter(
                SourceItemInterface::SKU,
                $skus,
                'in'
            )->addFilter(
                SourceItemInterface::SOURCE_CODE,
                $sourceCodes,
                'in'
            )->create()
        )->getItems();
    }
}
