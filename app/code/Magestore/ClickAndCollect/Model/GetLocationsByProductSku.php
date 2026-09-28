<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Model\GetSourceCodesBySkusInterface;
use Magestore\ClickAndCollectApi\Api\GetLocationsByProductSkuInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;

/**
 * Get location list by product sku
 */
class GetLocationsByProductSku implements GetLocationsByProductSkuInterface
{
    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var GetSourceCodesBySkusInterface
     */
    protected $getSourceCodesBySkus;

    /**
     * @var GetStockSourceLinksInterface
     */
    protected $getStockSourceLinks;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    protected $searchCriteriaBuilderFactory;

    /**
     * GetLocationsByProductSku constructor.
     * @param LocationRepositoryInterface $locationRepository
     * @param GetSourceCodesBySkusInterface $getSourceCodesBySkus
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     */
    public function __construct(
        LocationRepositoryInterface $locationRepository,
        GetSourceCodesBySkusInterface $getSourceCodesBySkus,
        GetStockSourceLinksInterface $getStockSourceLinks,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
    ) {
        $this->locationRepository = $locationRepository;
        $this->getSourceCodesBySkus = $getSourceCodesBySkus;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $productSku): array
    {
        $sourceCodes = $this->getSourceCodesBySkus->execute([$productSku]);

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(
            StockSourceLinkInterface::SOURCE_CODE,
            $sourceCodes,
            'in'
        );
        $stockSourcesLink = $this->getStockSourceLinks->execute($searchCriteriaBuilder->create());
        $stockIds = [];
        foreach ($stockSourcesLink->getItems() as $item) {
            $stockIds[] = $item->getStockId();
        }
        $stockIds = array_unique($stockIds);

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(
            LocationInterface::STOCK_ID,
            $stockIds,
            'in'
        );
        $searchCriteriaBuilder->addFilter('is_allow_pickup', 1);
        $searchResult = $this->locationRepository->getList($searchCriteriaBuilder->create());

        return $searchResult->getItems();
    }
}
