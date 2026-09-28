<?php 
namespace ArtM\Sortbyoptions\Plugin\Catalog\Model;

class Config
{
    /**
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param array $options
     * @return array
     */
    public function afterGetAttributeUsedForSortByArray(\Magento\Catalog\Model\Config $catalogConfig, $options)
    {
        $customOptions = [
            'position'      => __('Featured'),
            'crecommend'    => __('Recommended'),
            'discount'      => __('Discount'),
            'p_low_to_high' => __('Price Low to High'),
            'p_high_to_low' => __('Price High to Low'),
            'created'       => __('New Arrivals'),
            'crating'       => __('Rating')
        ];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $request = $objectManager->get(\Magento\Framework\App\Request\Http::class);
        
        if ($request->getFullActionName() == 'catalogsearch_result_index') {
            $customOptions = array_merge(['relevance' => __('Relevance')], $customOptions);
        }
        return $customOptions;
    }
}