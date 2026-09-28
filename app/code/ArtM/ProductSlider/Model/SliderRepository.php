<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model;

use ArtM\ProductSlider\Api\Data\SliderInterface;
use ArtM\ProductSlider\Api\Data\SliderSearchResultsInterface;
use ArtM\ProductSlider\Api\Data\SliderSearchResultsInterfaceFactory;
use ArtM\ProductSlider\Api\SliderRepositoryInterface;
use ArtM\ProductSlider\Model\ResourceModel\Slider as SliderResource;
use ArtM\ProductSlider\Model\ResourceModel\Slider\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class SliderRepository implements SliderRepositoryInterface
{
    public function __construct(
        private readonly SliderResource $resource,
        private readonly SliderFactory $sliderFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly SliderSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    public function save(SliderInterface $slider): SliderInterface
    {
        try {
            $this->resource->save($slider);
        } catch (\Throwable $exception) {
            throw new CouldNotSaveException(__('Unable to save slider.'), $exception);
        }

        return $slider;
    }

    public function getById(int $sliderId): SliderInterface
    {
        $slider = $this->sliderFactory->create();
        $this->resource->load($slider, $sliderId);

        if (!$slider->getId()) {
            throw new NoSuchEntityException(__('Slider with ID "%1" does not exist.', $sliderId));
        }

        return $slider;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SliderSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(SliderInterface $slider): bool
    {
        try {
            $this->resource->delete($slider);
        } catch (\Throwable $exception) {
            throw new CouldNotDeleteException(__('Unable to delete slider.'), $exception);
        }

        return true;
    }

    public function deleteById(int $sliderId): bool
    {
        return $this->delete($this->getById($sliderId));
    }
}
