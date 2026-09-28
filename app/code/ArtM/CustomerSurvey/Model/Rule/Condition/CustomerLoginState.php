<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Rule\Condition;

class CustomerLoginState extends AbstractSimpleCondition
{
    /**
     * @var string[]
     */
    protected $attributeOptions = [
        'customer_login_state' => 'Customer Login State',
    ];

    public function getValueElementType()
    {
        return 'select';
    }

    public function getValueSelectOptions()
    {
        return [
            ['value' => 'logged_in', 'label' => __('Logged In')],
            ['value' => 'guest', 'label' => __('Guest')],
        ];
    }
}