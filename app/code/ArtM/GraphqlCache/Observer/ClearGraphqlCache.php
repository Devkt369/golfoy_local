<?php
namespace ArtM\GraphqlCache\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;

class ClearGraphqlCache implements ObserverInterface
{
    protected $cache;
    protected $logger;

    public function __construct(
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            // Clear GraphQL cache with proper tags
            $tags = [
                'GRAPHQL_CATEGORY',
                'GRAPHQL_PRODUCT',
                'GRAPHQL_INVENTORY'
            ];
            
            $this->cache->clean($tags);
            
            $this->logger->info('GraphQL cache cleared for tags: ' . implode(', ', $tags));
        } catch (\Exception $e) {
            $this->logger->error('Error clearing GraphQL cache: ' . $e->getMessage());
        }
    }
}
