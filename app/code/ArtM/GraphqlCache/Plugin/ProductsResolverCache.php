<?php
namespace ArtM\GraphqlCache\Plugin;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use ArtM\GraphqlCache\Model\Cache\GraphqlCache;
use Psr\Log\LoggerInterface;

class ProductsResolverCache
{
    protected $graphqlCache;
    protected $logger;

    public function __construct(
        GraphqlCache $graphqlCache,
        LoggerInterface $logger
    ) {
        $this->graphqlCache = $graphqlCache;
        $this->logger = $logger;
    }

    public function aroundResolve(
        $subject,
        callable $proceed,
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $totalStartTime = microtime(true);
        
        // Only cache if this is a category-based product query
        if (!isset($args['filter']['category_id'])) {
            $this->logger->info('GraphQL ProductsResolverCache: No category_id filter found, skipping cache');
            return $proceed($field, $context, $info, $value, $args);
        }

        try {
            $this->logger->info('GraphQL ProductsResolverCache: Starting cache check for products query');
            
            // Try cache first
            $cached = $this->graphqlCache->load($args, $context);
            if ($cached !== false) {
                $totalTime = round((microtime(true) - $totalStartTime) * 1000, 2);
                $this->logger->info('GraphQL ProductsResolverCache: CACHE HIT - Total time: ' . $totalTime . 'ms');
                return $cached;
            }

            $this->logger->info('GraphQL ProductsResolverCache: CACHE MISS - Executing original resolver');
            $resolverStartTime = microtime(true);
            
            // Execute original resolver
            $result = $proceed($field, $context, $info, $value, $args);
            
            $resolverTime = round((microtime(true) - $resolverStartTime) * 1000, 2);

            // Generate cache tags
            $categoryId = is_array($args['filter']['category_id']) 
                ? $args['filter']['category_id']['eq'] ?? $args['filter']['category_id']['in'][0] ?? 'unknown'
                : $args['filter']['category_id'];
                
            $tags = [
                'GRAPHQL_CATEGORY',
                'GRAPHQL_CATEGORY_' . $categoryId,
                'GRAPHQL_PRODUCT'
            ];

            // Save to cache
            $this->graphqlCache->save($args, $context, $result, $tags);
            
            $totalTime = round((microtime(true) - $totalStartTime) * 1000, 2);
            $this->logger->info('GraphQL ProductsResolverCache: CACHE MISS - Resolver time: ' . $resolverTime . 'ms - Total time: ' . $totalTime . 'ms');

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('ProductsResolverCache plugin error: ' . $e->getMessage());
            // Fallback to original resolver
            return $proceed($field, $context, $info, $value, $args);
        }
    }
}