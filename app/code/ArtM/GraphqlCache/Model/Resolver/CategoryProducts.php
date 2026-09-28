<?php
namespace ArtM\GraphqlCache\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use ArtM\GraphqlCache\Model\Cache\GraphqlCache;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch;
use Psr\Log\LoggerInterface;

class CategoryProducts implements ResolverInterface
{
    protected $graphqlCache;
    protected $productSearch;
    protected $logger;

    public function __construct(
        GraphqlCache $graphqlCache,
        ProductSearch $productSearch,
        LoggerInterface $logger
    ) {
        $this->graphqlCache = $graphqlCache;
        $this->productSearch = $productSearch;
        $this->logger = $logger;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        try {
            // Validate required arguments
            if (empty($args['filter']['category_id']['eq'])) {
                throw new GraphQlInputException(__('Category ID is required'));
            }

            // Try cache first
            $cached = $this->graphqlCache->load($args, $context);
            if ($cached !== false) {
                return $cached;
            }

            // Get products using existing DataProvider
            $result = $this->getProducts($args, $context, $info);

            // Generate cache tags
            $categoryId = $args['filter']['category_id']['eq'];
            $tags = [
                'GRAPHQL_CATEGORY',
                'GRAPHQL_CATEGORY_' . $categoryId,
                'GRAPHQL_PRODUCT'
            ];

            // Save to cache
            $this->graphqlCache->save($args, $context, $result, $tags);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('CategoryProducts resolver error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getProducts($args, $context, ResolveInfo $info)
    {
        // Use the existing product search data provider
        $searchCriteria = $this->buildSearchCriteria($args);
        
        return $this->productSearch->getList(
            $searchCriteria,
            $info,
            $context
        );
    }

    private function buildSearchCriteria($args)
    {
        // Build search criteria from GraphQL arguments
        // This is simplified - you may need to adapt based on your exact needs
        return [
            'filter' => $args['filter'] ?? [],
            'sort' => $args['sort'] ?? [],
            'currentPage' => $args['currentPage'] ?? 1,
            'pageSize' => $args['pageSize'] ?? 20
        ];
    }
}
