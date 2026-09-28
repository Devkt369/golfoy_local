<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Api;

use ArtM\ProductSlider\Api\Data\SliderGroupInterface;
use ArtM\ProductSlider\Api\Data\SliderGroupSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface SliderGroupRepositoryInterface
{
    /**
     * @throws CouldNotSaveException
     */
    public function save(SliderGroupInterface $sliderGroup): SliderGroupInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $groupId): SliderGroupInterface;

    public function getList(SearchCriteriaInterface $searchCriteria): SliderGroupSearchResultsInterface;

    /**
     * @throws CouldNotDeleteException
     */
    public function delete(SliderGroupInterface $sliderGroup): bool;

    /**
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $groupId): bool;
}
