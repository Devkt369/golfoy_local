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
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magestore\ClickAndCollect\Model\ResourceModel\Package as PackageResourceModel;
use Magestore\ClickAndCollect\Model\ResourceModel\Package\Collection;
use Magestore\ClickAndCollect\Model\ResourceModel\Package\CollectionFactory;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterfaceFactory;
use Magestore\ClickAndCollectApi\Api\Data\PackageSearchResultsInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageSearchResultsInterfaceFactory;
use Magestore\ClickAndCollectApi\Api\PackageRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class PackageRepository
 *
 * Use for CRUD Package
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PackageRepository implements PackageRepositoryInterface
{
    /**
     * @var CollectionFactory
     */
    protected $packageCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var PackageSearchResultsInterfaceFactory
     */
    protected $packageSearchResultsInterfaceFactory;

    /**
     * @var PackageResourceModel
     */
    protected $packageResource;

    /**
     * @var PackageInterfaceFactory
     */
    protected $packageInterfaceFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * PackageRepository constructor.
     *
     * @param CollectionFactory $packageCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface $collectionProcessor
     * @param PackageSearchResultsInterfaceFactory $packageSearchResultsInterfaceFactory
     * @param PackageResourceModel $packageResource
     * @param PackageInterfaceFactory $packageInterfaceFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $packageCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor,
        PackageSearchResultsInterfaceFactory $packageSearchResultsInterfaceFactory,
        PackageResourceModel $packageResource,
        PackageInterfaceFactory $packageInterfaceFactory,
        LoggerInterface $logger
    ) {
        $this->packageCollectionFactory = $packageCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collectionProcessor = $collectionProcessor;
        $this->packageSearchResultsInterfaceFactory = $packageSearchResultsInterfaceFactory;
        $this->packageResource = $packageResource;
        $this->packageInterfaceFactory = $packageInterfaceFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function save(PackageInterface $package): PackageInterface
    {
        try {
            $this->packageResource->save($package);
            return $package;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not save package'), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function get(int $packageId): PackageInterface
    {
        /** @var PackageInterface $source */
        $package = $this->packageInterfaceFactory->create();
        $this->packageResource->load($package, $packageId, PackageInterface::PACKAGE_ID);

        if (null === $package->getPackageId()) {
            throw new NoSuchEntityException(
                __('Package with id "%value" does not exist.', ['value' => $packageId])
            );
        }
        return $package;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $packageId): void
    {
        /** @var PackageInterface $package */
        $package = $this->packageInterfaceFactory->create();
        $this->packageResource->load($package, $packageId, PackageInterface::PACKAGE_ID);

        if (!$package->getPackageId()) {
            throw new NoSuchEntityException(
                __(
                    'There is no package with "%fieldValue" for "%fieldName". Verify and try again.',
                    [
                        'fieldName' => PackageInterface::PACKAGE_ID,
                        'fieldValue' => $packageId
                    ]
                )
            );
        }

        try {
            $this->packageResource->delete($package);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotDeleteException(__('Could not delete package'), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(?SearchCriteriaInterface $searchCriteria = null): PackageSearchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->packageCollectionFactory->create();

        if (null === $searchCriteria) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        } else {
            $this->collectionProcessor->process($searchCriteria, $collection);
        }

        /** @var PackageSearchResultsInterface $searchResult */
        $searchResult = $this->packageSearchResultsInterfaceFactory->create();
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;
    }
}
