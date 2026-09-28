<?php

namespace ArtM\MsiPriority\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class Brands implements ArrayInterface
{
    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Return array of brand options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [['value' => '', 'label' => __('-- Please Select --')]];
        
        try {
            $brandAttribute = $this->attributeRepository->get(\Magento\Catalog\Model\Product::ENTITY, 'brand');
            
            if ($brandAttribute && $brandAttribute->getOptions()) {
                foreach ($brandAttribute->getOptions() as $option) {
                    if ($option->getValue() && $option->getLabel()) {
                        $options[] = [
                            'value' => $option->getValue(),
                            'label' => $option->getLabel()
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // If brand attribute doesn't exist, return basic options
            $options[] = ['value' => 'brand1', 'label' => 'Brand 1'];
            $options[] = ['value' => 'brand2', 'label' => 'Brand 2'];
        }

        return $options;
    }
}
