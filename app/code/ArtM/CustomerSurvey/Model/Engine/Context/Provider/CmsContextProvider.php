<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Engine\Context\Provider;

use ArtM\CustomerSurvey\Model\Engine\Context\ContextProviderInterface;

class CmsContextProvider implements ContextProviderInterface
{
    public function provide(array $context): array
    {
        return [
            'cms_page_id' => isset($context['cms_page_id']) ? (int)$context['cms_page_id'] : 0,
            'cms_identifier' => (string)($context['cms_identifier'] ?? ''),
        ];
    }
}
