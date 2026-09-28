<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Engine\Context;

interface ContextProviderInterface
{
    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function provide(array $context): array;
}
