<?php

namespace ArtM\EstimateDelivery\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class AllSource implements ArrayInterface
{
    protected $sourceRepository;
    protected $searchCriteriaBuilder;

    public function __construct(
        SourceRepositoryInterface $sourceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get All Sources Collection
     */
    public function getSourceCollection()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        return $this->sourceRepository->getList($searchCriteria)->getItems();
    }

    /**
     * Options getter for system.xml
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->getSourceCollection() as $source) {
            /** @var \Magento\InventoryApi\Api\Data\SourceInterface $source */
            $options[] = [
                'value' => $source->getSourceCode(),
                'label' => $source->getName()
            ];
        }

        return $options;
    }

    /**
     * Key-value output
     */
    public function toArray()
    {
        $array = [];

        foreach ($this->getSourceCollection() as $source) {
            $array[$source->getSourceCode()] = $source->getName();
        }

        return $array;
    }
}
