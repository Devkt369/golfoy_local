<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magestore\ClickAndCollect\Helper\SearchCriteriaGraphQl;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Api\Filter;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * GetProductsInfoOnStore field resolver, used for GraphQL request processing.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetProductsInfoOnStore implements ResolverInterface
{
    const TYPE_FIELD_NAME = 'products';

    /**
     * @var Builder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var StockItemRepository
     */
    protected $stockItemRepository;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    protected $criteriaFactory;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var SearchCriteriaGraphQl
     */
    protected $searchCriteriaGraphQLHelper;

    /**
     * GetProductsInfoOnStore constructor.
     * @param Builder $searchCriteriaBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param LocationRepositoryInterface $locationRepository
     * @param ProductRepositoryInterface $productRepository
     * @param Logger $logger
     * @param StockItemRepository $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $criteriaFactory
     * @param ObjectManagerInterface $objectManager
     * @param SearchCriteriaGraphQl $searchCriteriaGraphQLHelper
     */
    public function __construct(
        Builder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        LocationRepositoryInterface $locationRepository,
        ProductRepositoryInterface $productRepository,
        Logger $logger,
        StockItemRepository $stockItemRepository,
        StockItemCriteriaInterfaceFactory $criteriaFactory,
        ObjectManagerInterface $objectManager,
        SearchCriteriaGraphQl $searchCriteriaGraphQLHelper
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->locationRepository = $locationRepository;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->stockItemRepository = $stockItemRepository;
        $this->criteriaFactory = $criteriaFactory;
        $this->_objectManager = $objectManager;
        $this->searchCriteriaGraphQLHelper = $searchCriteriaGraphQLHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        if (!isset($args['storeId'])) {
            throw new GraphQlInputException(__('Required parameter "storeId" is missing.'));
        }

        $location = $this->locationRepository->getById($args['storeId']);

        if ($location) {
            $stockId = $location->getStockId();

            /** @var \Magento\CatalogInventory\Api\StockItemCriteriaInterface $criteria */
            $criteria = $this->criteriaFactory->create();
            $criteria->addFilter('stock', 'stock_id', ['eq' => $stockId]);
            $searchResultStockItem = $this->stockItemRepository->getList($criteria);
            $productIds = [];
            foreach ($searchResultStockItem->getItems() as $item) {
                $productIds[] = $item->getProductId();
            }
            // set field name to reuse getProductAttributeFilterFields function of magento
            $info->fieldName = self::TYPE_FIELD_NAME;
            $searchCriteria = $this->searchCriteriaGraphQLHelper->buildSearchCriteria($args, $info);

            $this->addFilterByProductId($searchCriteria, $productIds);

            $searchResult = $this->productRepository->getList($searchCriteria);
            return [
                'total_count' => $searchResult->getTotalCount(),
                'items' => $this->formatProductsArray($searchResult->getItems()),
                'page_info' => [
                    'page_size' => $searchCriteria->getPageSize(),
                    'current_page' => $searchCriteria->getCurrentPage(),
                    'total_pages' => $this->searchCriteriaGraphQLHelper->getMaxPage($searchCriteria, $searchResult)
                ],
                'search_result' => $searchResult,
                'layer_type' => isset($args['search']) ?
                    Resolver::CATALOG_LAYER_SEARCH : Resolver::CATALOG_LAYER_CATEGORY,
            ];

        } else {
            throw new GraphQlInputException(__('Location is not available'));
        }
    }

    /**
     * Format product models for graphql schema
     *
     * @param ProductInterface[] $productsModel
     * @return array
     */
    public function formatProductsArray(array $productsModel)
    {
        $productArray = [];
        /** @var Product $product */
        foreach ($productsModel as $product) {
            $productArray[$product->getId()] = $product->getData();
            $productArray[$product->getId()]['model'] = $product;
        }
        return $productArray;
    }

    /**
     * Function addFilterByProductId
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param array $productIds
     */
    public function addFilterByProductId(&$searchCriteria, $productIds)
    {
        $filter = $this->_objectManager->create(Filter::class);
        $addNewFilterGroup = $this->_objectManager->create(FilterGroup::class);

        $filter->setField('entity_id');
        $filter->setValue($productIds);
        $filter->setConditionType('in');
        $addNewFilterGroup->setFilters([$filter]);

        $filterGroup = $searchCriteria->getFilterGroups();
        $filterGroup[] = $addNewFilterGroup;
        $searchCriteria->setFilterGroups($filterGroup);
    }
}
