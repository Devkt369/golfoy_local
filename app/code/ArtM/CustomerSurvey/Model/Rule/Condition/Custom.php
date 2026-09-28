<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Rule\Condition;

class Custom extends AbstractSimpleCondition
{
    /**
     * @var string[]
     */
    protected $attributeOptions = [
        'custom_key' => 'Custom Key',
    ];

    public function getValueElementType()
    {
        return 'text';
    }
}