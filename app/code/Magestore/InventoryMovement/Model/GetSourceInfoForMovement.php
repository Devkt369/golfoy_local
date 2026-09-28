<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\GetSourceInfoForMovementInterface;

/**
 * Service for get source info for movement (source name)
 *
 * @api
 */
class GetSourceInfoForMovement implements GetSourceInfoForMovementInterface
{
    /**
     * @var SourceRepositoryInterface
     */
    protected $sourceRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * GetProductInfoForMovement constructor.
     *
     * @param SourceRepositoryInterface $sourceRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        SourceRepositoryInterface $sourceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Execute service
     *
     * @param array $movements
     * @return array
     */
    public function execute(array $movements): array
    {
        $sourceNames= [];

        $sourceCollection = $this->sourceRepository->getList(
            $this->searchCriteriaBuilder->addFilter(
                SourceInterface::SOURCE_CODE,
                array_unique(array_column($movements, InventoryMovementInterface::SOURCE_CODE)),
                'in'
            )->create()
        )->getItems();

        foreach ($sourceCollection as $source) {
            $sourceNames[$source->getSourceCode()] = $source->getName();
        }

        return [
            'source_names' => $sourceNames
        ];
    }
}
