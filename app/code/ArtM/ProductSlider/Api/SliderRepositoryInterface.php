<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Api;

use ArtM\ProductSlider\Api\Data\SliderInterface;
use ArtM\ProductSlider\Api\Data\SliderSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface SliderRepositoryInterface
{
    /**
     * @throws CouldNotSaveException
     */
    public function save(SliderInterface $slider): SliderInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $sliderId): SliderInterface;

    public function getList(SearchCriteriaInterface $searchCriteria): SliderSearchResultsInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(SliderInterface $slider): bool;

    /**
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $sliderId): bool;
}
