<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Rule\Condition;

class Product extends AbstractSimpleCondition
{
    /**
     * @var string[]
     */
    protected $attributeOptions = [
        'sku' => 'SKU',
        'name' => 'Name',
        'attribute_set_id' => 'Attribute Set',
        'type_id' => 'Product Type',
        'price' => 'Price',
    ];

    public function getValueElementType()
    {
        if ($this->getAttribute() === 'attribute_set_id' || $this->getAttribute() === 'type_id') {
            return 'select';
        }

        return parent::getValueElementType();
    }
}