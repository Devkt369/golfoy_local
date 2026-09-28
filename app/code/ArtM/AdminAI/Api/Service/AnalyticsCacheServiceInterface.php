<?php
namespace ArtM\AdminAI\Api\Service;

interface AnalyticsCacheServiceInterface
{
    public function getCachedResult(string $intent, array $params): ?array;

    public function saveCache(string $intent, array $params, array $result): void;

    public function saveChatResult(
        string $sessionId,
        string $intent,
        array $params,
        array $result,
        string $source
    ): void;

    public function incrementHitCount(int $cacheId): void;
}
