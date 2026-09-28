<?php
namespace ArtM\GraphqlCache\Model\Cache;

use Magento\Framework\App\CacheInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class GraphqlCache
{
    const CACHE_PREFIX = 'artm_graphql_';
    const TTL = 3600; // 1 hour
    const CACHE_TYPE = 'artm_graphql';

    protected $cache;
    protected $serializer;
    protected $logger;

    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function load(array $args, ContextInterface $context)
    {
        $startTime = microtime(true);
        try {
            $key = $this->getKey($args, $context);
            $cached = $this->cache->load($key);

            if ($cached) {
                $loadTime = round((microtime(true) - $startTime) * 1000, 2);
                $this->logger->info('GraphQL CACHE HIT - Key: ' . substr($key, -20) . ' - Load time: ' . $loadTime . 'ms');
                return $this->serializer->unserialize($cached);
            }
            
            $this->logger->info('GraphQL CACHE MISS - Key: ' . substr($key, -20));
        } catch (\Exception $e) {
            $this->logger->error('GraphQL cache load error: ' . $e->getMessage());
        }

        return false;
    }

    public function save(array $args, ContextInterface $context, array $data, array $tags = [])
    {
        $startTime = microtime(true);
        try {
            $key = $this->getKey($args, $context);
            
            // Add default cache type tag
            $tags[] = self::CACHE_TYPE;
            
            $result = $this->cache->save(
                $this->serializer->serialize($data),
                $key,
                $tags,
                self::TTL
            );
            
            $saveTime = round((microtime(true) - $startTime) * 1000, 2);
            $dataSize = strlen($this->serializer->serialize($data));
            $this->logger->info('GraphQL CACHE SAVE - Key: ' . substr($key, -20) . ' - Size: ' . round($dataSize/1024, 2) . 'KB - Save time: ' . $saveTime . 'ms - Tags: ' . implode(',', $tags));
            
        } catch (\Exception $e) {
            $this->logger->error('GraphQL cache save error: ' . $e->getMessage());
        }
    }

    public function clean(array $tags = [])
    {
        try {
            if (empty($tags)) {
                $tags = [self::CACHE_TYPE];
            }
            $this->cache->clean($tags);
        } catch (\Exception $e) {
            $this->logger->error('GraphQL cache clean error: ' . $e->getMessage());
        }
    }

    private function getKey(array $args, ContextInterface $context)
    {
        $keyData = [
            'args' => $args,
            'store' => $context->getExtensionAttributes()->getStore()->getId(),
        ];

        // Add customer group if available
        $extensionAttributes = $context->getExtensionAttributes();
        if (method_exists($extensionAttributes, 'getCustomerGroupId') && $extensionAttributes->getCustomerGroupId()) {
            $keyData['customerGroup'] = $extensionAttributes->getCustomerGroupId();
        }

        // Add currency if available
        if (method_exists($extensionAttributes, 'getCurrency') && $extensionAttributes->getCurrency()) {
            $keyData['currency'] = $extensionAttributes->getCurrency()->getCode();
        }

        return self::CACHE_PREFIX . hash('sha256', $this->serializer->serialize($keyData));
    }

    /**
     * Get cache instance for direct testing
     */
    public function getCacheInstance()
    {
        return $this->cache;
    }
}
