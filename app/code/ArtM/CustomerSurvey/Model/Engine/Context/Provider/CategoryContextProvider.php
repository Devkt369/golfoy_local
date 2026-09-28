<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Engine\Context\Provider;

use ArtM\CustomerSurvey\Model\Engine\Context\ContextProviderInterface;

class CategoryContextProvider implements ContextProviderInterface
{
    public function provide(array $context): array
    {
        return [
            'category_id' => isset($context['category_id']) ? (int)$context['category_id'] : 0,
        ];
    }
}
