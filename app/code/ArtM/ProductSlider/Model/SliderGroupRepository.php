<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model;

use ArtM\ProductSlider\Api\Data\SliderGroupInterface;
use ArtM\ProductSlider\Api\Data\SliderGroupSearchResultsInterface;
use ArtM\ProductSlider\Api\Data\SliderGroupSearchResultsInterfaceFactory;
use ArtM\ProductSlider\Api\SliderGroupRepositoryInterface;
use ArtM\ProductSlider\Model\ResourceModel\SliderGroup as SliderGroupResource;
use ArtM\ProductSlider\Model\ResourceModel\SliderGroup\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class SliderGroupRepository implements SliderGroupRepositoryInterface
{
    public function __construct(
        private readonly SliderGroupResource $resource,
        private readonly SliderGroupFactory $sliderGroupFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly SliderGroupSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    public function save(SliderGroupInterface $sliderGroup): SliderGroupInterface
    {
        try {
            $this->resource->save($sliderGroup);
        } catch (\Throwable $exception) {
            throw new CouldNotSaveException(__('Unable to save slider group.'), $exception);
        }

        return $sliderGroup;
    }

    public function getById(int $groupId): SliderGroupInterface
    {
        $sliderGroup = $this->sliderGroupFactory->create();
        $this->resource->load($sliderGroup, $groupId);

        if (!$sliderGroup->getId()) {
            throw new NoSuchEntityException(__('Slider group with ID "%1" does not exist.', $groupId));
        }

        return $sliderGroup;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SliderGroupSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(SliderGroupInterface $sliderGroup): bool
    {
        try {
            $this->resource->delete($sliderGroup);
        } catch (\Throwable $exception) {
            throw new CouldNotDeleteException(__('Unable to delete slider group.'), $exception);
        }

        return true;
    }

    public function deleteById(int $groupId): bool
    {
        return $this->delete($this->getById($groupId));
    }
}
