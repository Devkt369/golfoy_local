<?php
namespace ArtM\AdminAI\Api;

use ArtM\AdminAI\Model\QueryCache;

interface QueryCacheRepositoryInterface
{
    public function create(): QueryCache;

    public function save(QueryCache $cache): QueryCache;

    public function getValidByIntentAndHash(string $intentCode, string $parameterHash): ?QueryCache;

    public function incrementHitCount(int $cacheId): void;
}
