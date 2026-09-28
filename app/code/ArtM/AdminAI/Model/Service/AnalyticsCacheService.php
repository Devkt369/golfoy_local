<?php
namespace ArtM\AdminAI\Model\Service;

use ArtM\AdminAI\Api\ChatResultRepositoryInterface;
use ArtM\AdminAI\Api\QueryCacheRepositoryInterface;
use ArtM\AdminAI\Api\Service\AnalyticsCacheServiceInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

class AnalyticsCacheService implements AnalyticsCacheServiceInterface
{
    /** @var QueryCacheRepositoryInterface */
    private $queryCacheRepository;

    /** @var ChatResultRepositoryInterface */
    private $chatResultRepository;

    /** @var DateTime */
    private $dateTime;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        QueryCacheRepositoryInterface $queryCacheRepository,
        ChatResultRepositoryInterface $chatResultRepository,
        DateTime $dateTime,
        LoggerInterface $logger
    ) {
        $this->queryCacheRepository = $queryCacheRepository;
        $this->chatResultRepository = $chatResultRepository;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    public function getCachedResult(string $intent, array $params): ?array
    {
        $intent = trim($intent);
        if ($intent === '') {
            return null;
        }

        $parameterHash = $this->generateParameterHash($params);
        $cache = $this->queryCacheRepository->getValidByIntentAndHash($intent, $parameterHash);

        if ($cache === null) {
            $this->logger->info('AdminAI analytics cache MISS', [
                'intent' => $intent,
                'parameter_hash' => $parameterHash
            ]);
            return null;
        }

        $this->incrementHitCount((int)$cache->getId());
        $this->logger->info('AdminAI analytics cache HIT', [
            'intent' => $intent,
            'parameter_hash' => $parameterHash,
            'cache_id' => (int)$cache->getId()
        ]);

        $decoded = json_decode((string)$cache->getData('result_json'), true);
        return is_array($decoded) ? $decoded : null;
    }

    public function saveCache(string $intent, array $params, array $result): void
    {
        $intent = trim($intent);
        if ($intent === '') {
            return;
        }

        $cache = $this->queryCacheRepository->create();
        $cache->setData('intent_code', $intent);
        $cache->setData('parameter_hash', $this->generateParameterHash($params));
        $cache->setData('parameters_json', $this->toJson($params));
        $cache->setData('result_json', $this->toJson($result));
        $cache->setData('expires_at', $this->resolveExpiryDate($intent));
        $cache->setData('hit_count', 0);

        $this->queryCacheRepository->save($cache);
    }

    public function saveChatResult(
        string $sessionId,
        string $intent,
        array $params,
        array $result,
        string $source
    ): void {
        $sessionId = trim($sessionId);
        $intent = trim($intent);
        $source = trim($source);

        if ($sessionId === '' || $intent === '') {
            return;
        }

        $chatResult = $this->chatResultRepository->create();
        $chatResult->setData('session_id', $sessionId);
        $chatResult->setData('intent_code', $intent);
        $chatResult->setData('parameters_json', $this->toJson($params));
        $chatResult->setData('result_json', $this->toJson($result));
        $chatResult->setData('source', $source !== '' ? $source : 'manual');

        $this->chatResultRepository->save($chatResult);
    }

    public function incrementHitCount(int $cacheId): void
    {
        $this->queryCacheRepository->incrementHitCount($cacheId);
    }

    private function resolveExpiryDate(string $intent): string
    {
        $ttlMap = [
            'executive_sales_summary' => 1800,
            'product_performance' => 3600,
            'customer_risk_summary' => 3600,
            'inactive_customers' => 21600,
            'stock_projection' => 900,
            'customer_segment_list' => 1800,
        ];

        $ttlSeconds = $ttlMap[$intent] ?? 1800;
        $baseTime = strtotime($this->dateTime->gmtDate('Y-m-d H:i:s'));
        $expiresAt = $baseTime !== false ? $baseTime + $ttlSeconds : time() + $ttlSeconds;

        return gmdate('Y-m-d H:i:s', $expiresAt);
    }

    private function generateParameterHash(array $params): string
    {
        $normalized = $this->normalizeParams($params);
        return md5($this->toJson($normalized));
    }

    private function normalizeParams(array $params): array
    {
        ksort($params);
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $params[$key] = $this->normalizeParams($value);
            }
        }

        return $params;
    }

    private function toJson(array $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json : '{}';
    }
}
