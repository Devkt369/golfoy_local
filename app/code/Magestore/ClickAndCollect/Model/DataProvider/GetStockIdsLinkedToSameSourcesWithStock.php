<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\DataProvider;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magestore\ClickAndCollectApi\Api\DataProvider\GetStockIdsLinkedToSameSourcesWithStockInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

/**
 * Get Stock Ids Linked To Same Sources With Stock Data Provider Data Provider
 */
class GetStockIdsLinkedToSameSourcesWithStock implements
    GetStockIdsLinkedToSameSourcesWithStockInterface
{

    /**
     * @var GetStockSourceLinksInterface
     */
    protected $getStockSourceLinks;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * GetListStocksForFilterLocationsFromWebsiteStock constructor.
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        GetStockSourceLinksInterface $getStockSourceLinks,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(int $stockId): array
    {
        /* Get sources linked to that stock */
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId);
        $stockSourceLinks = $this->getStockSourceLinks->execute($searchCriteriaBuilder->create());
        $sourceCodes = [];
        foreach ($stockSourceLinks->getItems() as $item) {
            $sourceCodes[] = $item->getSourceCode();
        }

        if (!count($sourceCodes)) {
            return [];
        }

        /* Get stocks that link to same sources */
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(StockSourceLinkInterface::SOURCE_CODE, $sourceCodes, 'in');
        $stockSourceLinks = $this->getStockSourceLinks->execute($searchCriteriaBuilder->create());
        $stockIds = [];
        foreach ($stockSourceLinks->getItems() as $item) {
            $stockIds[] = $item->getStockId();
        }
        return $stockIds;
    }
}
