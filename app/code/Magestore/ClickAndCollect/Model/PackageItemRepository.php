<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magestore\ClickAndCollect\Model\ResourceModel\PackageItem as PackageItemResourceModel;
use Magestore\ClickAndCollect\Model\ResourceModel\PackageItem\Collection as PackageItemCollection;
use Magestore\ClickAndCollect\Model\ResourceModel\PackageItem\CollectionFactory as PackageItemCollectionFactory;
use Magestore\ClickAndCollectApi\Api\Data\PackageItemInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageItemSearchResultsInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageItemSearchResultsInterfaceFactory;
use Magestore\ClickAndCollectApi\Api\PackageItemRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class PackageItemRepository
 *
 * Use for CRUD Package
 */
class PackageItemRepository implements PackageItemRepositoryInterface
{
    /**
     * @var PackageItemResourceModel
     */
    protected $packageItemResource;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PackageItemCollectionFactory
     */
    protected $packageItemCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var PackageItemSearchResultsInterfaceFactory
     */
    protected $packageItemSearchResultsInterfaceFactory;

    /**
     * PackageItemRepository constructor.
     *
     * @param PackageItemResourceModel $packageItemResource
     * @param LoggerInterface $logger
     * @param PackageItemCollectionFactory $packageItemCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface $collectionProcessor
     * @param PackageItemSearchResultsInterfaceFactory $packageItemSearchResultsInterfaceFactory
     */
    public function __construct(
        PackageItemResourceModel $packageItemResource,
        LoggerInterface $logger,
        PackageItemCollectionFactory $packageItemCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor,
        PackageItemSearchResultsInterfaceFactory $packageItemSearchResultsInterfaceFactory
    ) {
        $this->packageItemResource = $packageItemResource;
        $this->logger = $logger;
        $this->packageItemCollectionFactory = $packageItemCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collectionProcessor = $collectionProcessor;
        $this->packageItemSearchResultsInterfaceFactory = $packageItemSearchResultsInterfaceFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(PackageItemInterface $packageItem): PackageItemInterface
    {
        try {
            $this->packageItemResource->save($packageItem);
            return $packageItem;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not save package item'), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria = null): PackageItemSearchResultsInterface
    {
        /** @var PackageItemCollection $collection */
        $collection = $this->packageItemCollectionFactory->create();

        if (null === $searchCriteria) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        } else {
            $this->collectionProcessor->process($searchCriteria, $collection);
        }

        /** @var PackageItemSearchResultsInterface $searchResult */
        $searchResult = $this->packageItemSearchResultsInterfaceFactory->create();
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;
    }

    /**
     * @inheritdoc
     */
    public function getItemsByPackageId(int $packageId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(PackageItemInterface::PACKAGE_ID, $packageId)
            ->create();
        $searchResult = $this->getList($searchCriteria);
        return $searchResult->getItems();
    }
}
