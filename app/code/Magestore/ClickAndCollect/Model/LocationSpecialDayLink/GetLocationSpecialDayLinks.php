<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\LocationSpecialDayLink;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink\Collection
    as LocationSpecialDayLinkCollection;
use Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink\CollectionFactory
    as LocationSpecialDayLinkCollectionFactory;
use Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkSearchResultsInterface;
use Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkSearchResultsInterfaceFactory;
use Magestore\ClickAndCollectApi\Api\GetLocationSpecialDayLinksInterface;

/**
 * @inheritdoc
 */
class GetLocationSpecialDayLinks implements GetLocationSpecialDayLinksInterface
{
    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var LocationSpecialDayLinkCollectionFactory
     */
    private $locationSpecialDayLinkCollectionFactory;

    /**
     * @var StockSourceLinkSearchResultsInterfaceFactory
     */
    private $locationSpecialDayLinkSearchResultsFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param CollectionProcessorInterface $collectionProcessor
     * @param LocationSpecialDayLinkCollectionFactory $locationSpecialDayLinkCollectionFactory
     * @param LocationSpecialDayLinkSearchResultsInterfaceFactory $locationSpecialDayLinkSearchResultsFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        CollectionProcessorInterface $collectionProcessor,
        LocationSpecialDayLinkCollectionFactory $locationSpecialDayLinkCollectionFactory,
        LocationSpecialDayLinkSearchResultsInterfaceFactory $locationSpecialDayLinkSearchResultsFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->collectionProcessor = $collectionProcessor;
        $this->locationSpecialDayLinkCollectionFactory = $locationSpecialDayLinkCollectionFactory;
        $this->locationSpecialDayLinkSearchResultsFactory = $locationSpecialDayLinkSearchResultsFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function execute(SearchCriteriaInterface $searchCriteria): LocationSpecialDayLinkSearchResultsInterface
    {
        /** @var LocationSpecialDayLinkCollection $collection */
        $collection = $this->locationSpecialDayLinkCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var LocationSpecialDayLinkSearchResultsInterface $searchResult */
        $searchResult = $this->locationSpecialDayLinkSearchResultsFactory->create();

        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setSearchCriteria($searchCriteria);

        return $searchResult;
    }
}
