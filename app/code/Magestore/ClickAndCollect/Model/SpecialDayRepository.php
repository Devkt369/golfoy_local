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
use Magestore\ClickAndCollect\Model\ResourceModel\SpecialDay as SpecialDayResourceModel;
use Magestore\ClickAndCollect\Model\ResourceModel\SpecialDay\Collection;
use Magestore\ClickAndCollect\Model\ResourceModel\SpecialDay\CollectionFactory;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterfaceFactory;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDaySearchResultsInterface;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDaySearchResultsInterfaceFactory;
use Magestore\ClickAndCollectApi\Api\SpecialDayRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class SpecialDayRepository
 *
 * Use for CRUD Special Day
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SpecialDayRepository implements SpecialDayRepositoryInterface
{
    /**
     * @var CollectionFactory
     */
    protected $specialDayCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var SpecialDaySearchResultsInterfaceFactory
     */
    protected $specialDaySearchResultsInterfaceFactory;

    /**
     * @var SpecialDayResourceModel
     */
    protected $specialDayResourceModel;

    /**
     * @var SpecialDayInterfaceFactory
     */
    protected $specialDayInterfaceFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SpecialDayRepository constructor.
     *
     * @param CollectionFactory $specialDayCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface $collectionProcessor
     * @param SpecialDaySearchResultsInterfaceFactory $specialDaySearchResultsInterfaceFactory
     * @param SpecialDayResourceModel $specialDayResourceModel
     * @param SpecialDayInterfaceFactory $specialDayInterfaceFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $specialDayCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor,
        SpecialDaySearchResultsInterfaceFactory $specialDaySearchResultsInterfaceFactory,
        SpecialDayResourceModel $specialDayResourceModel,
        SpecialDayInterfaceFactory $specialDayInterfaceFactory,
        LoggerInterface $logger
    ) {
        $this->specialDayCollectionFactory = $specialDayCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collectionProcessor = $collectionProcessor;
        $this->specialDaySearchResultsInterfaceFactory = $specialDaySearchResultsInterfaceFactory;
        $this->specialDayResourceModel = $specialDayResourceModel;
        $this->specialDayInterfaceFactory = $specialDayInterfaceFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function save(SpecialDayInterface $specialDay): SpecialDayInterface
    {
        try {
            $this->specialDayResourceModel->save($specialDay);
            return $specialDay;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not save Special Day'), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function get(int $specialDayId): SpecialDayInterface
    {
        /** @var SpecialDayInterface $specialDay */
        $specialDay = $this->specialDayInterfaceFactory->create();
        $this->specialDayResourceModel->load($specialDay, $specialDayId, SpecialDayInterface::SPECIAL_DAY_ID);

        if (null === $specialDay->getSpecialDayId()) {
            throw new NoSuchEntityException(
                __(
                    'Special Day with id "%value" does not exist.',
                    ['value' => $specialDayId]
                )
            );
        }
        return $specialDay;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $specialDayId): void
    {
        /** @var SpecialDayInterface $specialDay */
        $specialDay = $this->specialDayInterfaceFactory->create();
        $this->specialDayResourceModel->load($specialDay, $specialDayId, SpecialDayInterface::SPECIAL_DAY_ID);

        if (!$specialDay->getSpecialDayId()) {
            throw new NoSuchEntityException(
                __(
                    'There is no Special Day with "%fieldValue" for "%fieldName". Verify and try again.',
                    [
                        'fieldName' => SpecialDayInterface::SPECIAL_DAY_ID,
                        'fieldValue' => $specialDayId
                    ]
                )
            );
        }

        try {
            $this->specialDayResourceModel->delete($specialDay);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotDeleteException(__('Could not delete Special Day'), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(?SearchCriteriaInterface $searchCriteria = null): SpecialDaySearchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->specialDayCollectionFactory->create();

        if (null === $searchCriteria) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        } else {
            $this->collectionProcessor->process($searchCriteria, $collection);
        }

        /** @var SpecialDaySearchResultsInterface $searchResult */
        $searchResult = $this->specialDaySearchResultsInterfaceFactory->create();
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;
    }
}
