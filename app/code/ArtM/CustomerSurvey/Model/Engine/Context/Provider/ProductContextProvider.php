<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Engine\Context\Provider;

use ArtM\CustomerSurvey\Model\Engine\Context\ContextProviderInterface;

class ProductContextProvider implements ContextProviderInterface
{
    public function provide(array $context): array
    {
        return [
            'product_id' => isset($context['product_id']) ? (int)$context['product_id'] : 0,
            'product_sku' => (string)($context['product_sku'] ?? ''),
        ];
    }
}
