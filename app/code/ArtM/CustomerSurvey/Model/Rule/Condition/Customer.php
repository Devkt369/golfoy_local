<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Rule\Condition;

class Customer extends AbstractSimpleCondition
{
    /**
     * @var string[]
     */
    protected $attributeOptions = [
        'customer_email' => 'Customer Email',
        'customer_firstname' => 'Customer First Name',
        'customer_lastname' => 'Customer Last Name',
        'customer_dob' => 'Customer Date of Birth',
    ];
}