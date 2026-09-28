<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Engine\Context\Provider;

use ArtM\CustomerSurvey\Model\Engine\Context\ContextProviderInterface;

class CartContextProvider implements ContextProviderInterface
{
    public function provide(array $context): array
    {
        return [
            'cart_items_count' => isset($context['cart_items_count']) ? (int)$context['cart_items_count'] : 0,
            'cart_subtotal' => isset($context['cart_subtotal']) ? (float)$context['cart_subtotal'] : 0.0,
            'has_coupon' => !empty($context['has_coupon']),
        ];
    }
}
