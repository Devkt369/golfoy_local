<?php
namespace ArtM\GraphqlCache\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder as SortOrderFactory;

class SortByRecommend implements ResolverInterface
{
    protected $productRepository;
    protected $searchCriteriaBuilder;
    protected $sortOrderFactory;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderFactory $sortOrderFactory
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderFactory = $sortOrderFactory;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $searchCriteria = $this->searchCriteriaBuilder->create();

        if (isset($args['sort'])) {
            $sortField = $args['sort'];
            if (isset($sortField['crecommend'])) {
                $this->applyCustomSortOrder($searchCriteria, 'sorting_points', $sortField['crecommend']);
            }

            if (isset($sortField['position'])) {
                $this->applyCustomSortOrder($searchCriteria, 'position', $sortField['position']);
            }

            if (isset($sortField['crating'])) {
                $this->applyCustomSortOrder($searchCriteria, 'crating', $sortField['crating']);
            }

            if (isset($sortField['discount'])) {
                $this->applyCustomSortOrder($searchCriteria, 'discount', $sortField['discount']);
            }

            if (isset($sortField['created'])) {
                
                $this->applyCustomSortOrder($searchCriteria, 'created_at', $sortField['created']);
            }
        }

        $products = $this->productRepository->getList($searchCriteria);

        return [
            'total_count' => $products->getTotalCount(),
            'items' => $products->getItems(),
        ];
    }

    protected function applyCustomSortOrder($searchCriteria, $field, $direction)
    {
        $sortOrder = $this->sortOrderFactory->create()
            ->setField($field)
            ->setDirection($direction);

        $existingSortOrders = $searchCriteria->getSortOrders() ?? [];
        $existingSortOrders[] = $sortOrder;

        $searchCriteria->setSortOrders($existingSortOrders);
    }
}
