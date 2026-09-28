<?php

namespace ArtM\MsiPriority\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;

class BrandOptions implements ArrayInterface
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Constructor
     *
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get brand options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        
        try {
            // Try to get the brand attribute
            $brandAttribute = $this->attributeRepository->get('brand');
            
            if ($brandAttribute && $brandAttribute->usesSource()) {
                $sourceModel = $brandAttribute->getSource();
                $attributeOptions = $sourceModel->getAllOptions();
                
                foreach ($attributeOptions as $option) {
                    if ($option['value'] != '') {
                        $options[] = [
                            'value' => $option['value'],
                            'label' => $option['label']
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // If brand attribute doesn't exist, check for manufacturer attribute
            try {
                $manufacturerAttribute = $this->attributeRepository->get('manufacturer');
                if ($manufacturerAttribute && $manufacturerAttribute->usesSource()) {
                    $sourceModel = $manufacturerAttribute->getSource();
                    $attributeOptions = $sourceModel->getAllOptions();
                    
                    foreach ($attributeOptions as $option) {
                        if ($option['value'] != '') {
                            $options[] = [
                                'value' => $option['value'],
                                'label' => $option['label']
                            ];
                        }
                    }
                }
            } catch (\Exception $e2) {
                // If neither brand nor manufacturer attribute exists, provide golf-specific default options
                $options = [
                    ['value' => 'callaway', 'label' => __('Callaway')],
                    ['value' => 'taylormade', 'label' => __('TaylorMade')],
                    ['value' => 'titleist', 'label' => __('Titleist')],
                    ['value' => 'ping', 'label' => __('Ping')],
                    ['value' => 'cobra', 'label' => __('Cobra')],
                    ['value' => 'mizuno', 'label' => __('Mizuno')],
                    ['value' => 'wilson', 'label' => __('Wilson')],
                    ['value' => 'nike', 'label' => __('Nike Golf')],
                    ['value' => 'adidas', 'label' => __('Adidas Golf')],
                    ['value' => 'footjoy', 'label' => __('FootJoy')],
                    ['value' => 'srixon', 'label' => __('Srixon')],
                    ['value' => 'cleveland', 'label' => __('Cleveland')],
                    ['value' => 'odyssey', 'label' => __('Odyssey')],
                    ['value' => 'scotty_cameron', 'label' => __('Scotty Cameron')]
                ];
            }
        }
        
        return $options;
    }
}
