<?php
namespace WeserveCommerce\AjaxSearch\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class SearchableProductAttributes implements OptionSourceInterface
{
    private $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_searchable', 1);
        $collection->setOrder('frontend_label', 'ASC');

        foreach ($collection as $attribute) {
            $code = (string)$attribute->getAttributeCode();
            if ($code !== '') {
                $options[] = [
                    'value' => $code,
                    'label' => sprintf('%s (%s)', $attribute->getFrontendLabel() ?: $code, $code)
                ];
            }
        }

        return $options;
    }
}