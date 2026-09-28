<?php

namespace ArtM\BrandInventory\Block\Adminhtml\Data;

use Magento\Framework\View\Element\Template;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Updates extends Template
{
     protected $sourceRepository;
    protected $searchCriteriaBuilder;

    public function __construct(
        Template\Context $context,
        SourceRepositoryInterface $sourceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $data = []
    ) {
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context, $data);
    }

    public function getSources()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $sources = $this->sourceRepository->getList($searchCriteria)->getItems();

        return $sources;
    }
}