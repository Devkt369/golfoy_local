<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Rule\Condition;

class CustomerGroup extends AbstractSimpleCondition
{
    /**
     * @var string[]
     */
    protected $attributeOptions = [
        'customer_group_id' => 'Customer Group',
    ];

    public function getValueElementType()
    {
        return 'select';
    }
}