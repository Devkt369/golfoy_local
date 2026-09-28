<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Rule\Condition;

class StoreView extends AbstractSimpleCondition
{
    /**
     * @var string[]
     */
    protected $attributeOptions = [
        'store_id' => 'Store View',
    ];

    public function getValueElementType()
    {
        return 'select';
    }
}