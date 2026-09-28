<?php
namespace ArtM\AdminAI\Api;

interface AnalyticsCacheServiceInterface
{
    /**
     * Get a cache payload by key when not expired.
     *
     * @param string $cacheKey
     * @param string|null $expectedDataVersion
     * @return array|null
     */
    public function get(string $cacheKey, ?string $expectedDataVersion = null): ?array;

    /**
     * Set cache payload with TTL.
     *
     * @param string $cacheKey
     * @param string $intentCode
     * @param array $payload
     * @param string $dataVersion
     * @param int $ttlSeconds
     * @return void
     */
    public function set(
        string $cacheKey,
        string $intentCode,
        array $payload,
        string $dataVersion,
        int $ttlSeconds
    ): void;

    /**
     * Delete a cache row by key.
     *
     * @param string $cacheKey
     * @return void
     */
    public function delete(string $cacheKey): void;

    /**
     * Delete all expired cache rows.
     *
     * @return int Number of deleted rows
     */
    public function deleteExpired(): int;
}
