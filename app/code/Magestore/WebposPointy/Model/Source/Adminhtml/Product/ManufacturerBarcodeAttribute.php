<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\WebposPointy\Model\Source\Adminhtml\Product;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;

/**
 * Source manufacturer barcode attribute
 */
class ManufacturerBarcodeAttribute
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * ManufacturerBarcodeAttribute constructor.
     * @param Collection $collection
     */
    public function __construct(
        Collection $collection
    ) {
        $this->collection = $collection;
    }

    /**
     * To option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $collection = $this->collection->addFieldToFilter('is_unique', 1);
        $array = [];
        $array[] = ['value' => '', 'label' => '-- Select Manufacturer Attribute --'];
        foreach ($collection as $value) {
            $array[] = ['value' => $value->getAttributeCode(), 'label' => $value->getFrontendLabel()];
        }
        return $array;
    }
}
