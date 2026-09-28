<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Engine\Context\Provider;

use ArtM\CustomerSurvey\Model\Engine\Context\ContextProviderInterface;

class CustomerContextProvider implements ContextProviderInterface
{
    public function provide(array $context): array
    {
        $customerId = isset($context['customer_id']) ? (int)$context['customer_id'] : 0;

        return [
            'customer_id' => $customerId,
            'customer_group_id' => isset($context['customer_group_id']) ? (int)$context['customer_group_id'] : 0,
            'is_logged_in' => isset($context['is_logged_in'])
                ? (bool)$context['is_logged_in']
                : $customerId > 0,
        ];
    }
}
