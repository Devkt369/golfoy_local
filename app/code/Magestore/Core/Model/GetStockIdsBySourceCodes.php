<?php
/**
 * Copyright © 2021 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Core\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magestore\Core\Api\GetStockIdsBySourceCodesInterface;

/**
 * Get stock ids by source service
 */
class GetStockIdsBySourceCodes implements GetStockIdsBySourceCodesInterface
{
    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * GetStockIdsBySourceCodes constructor.
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        GetStockSourceLinksInterface $getStockSourceLinks,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $sourceCodes)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::SOURCE_CODE, $sourceCodes, 'in')
            ->create();
        $stockLinks = $this->getStockSourceLinks->execute($searchCriteria);
        $stockIds = [];
        foreach ($stockLinks->getItems() as $stockSourceLink) {
            $stockIds[] = $stockSourceLink->getStockId();
        }
        return $stockIds;
    }
}
