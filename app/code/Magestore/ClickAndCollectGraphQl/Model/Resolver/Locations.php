<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder;
use Magento\Search\Model\Query;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magestore\ClickAndCollectApi\Api\DataProvider\GetStockIdsLinkedToSameSourcesWithStockInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;

/**
 * Locations field resolver, used for GraphQL request processing.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Locations implements ResolverInterface
{
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
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    protected $filterGroupBuilder;

    /**
     * @var GetStockIdsLinkedToSameSourcesWithStockInterface
     */
    protected $getStockIdsLinkedToSameSourcesWithStock;

    /**
     * Locations constructor.
     * @param Builder $searchCriteriaBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param LocationRepositoryInterface $locationRepository
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param GetStockIdsLinkedToSameSourcesWithStockInterface $getStockIdsLinkedToSameSourcesWithStock
     */
    public function __construct(
        Builder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        LocationRepositoryInterface $locationRepository,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        GetStockIdsLinkedToSameSourcesWithStockInterface $getStockIdsLinkedToSameSourcesWithStock
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->locationRepository = $locationRepository;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->getStockIdsLinkedToSameSourcesWithStock = $getStockIdsLinkedToSameSourcesWithStock;
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

        if (isset($args['stockId']) && $args['stockId'] < 1) {
            throw new GraphQlInputException(__('stockId value must be greater than 0.'));
        }

        $searchCriteria = $this->buildSearchCriteria($args, $info);

        if (isset($args['stockId'])) {
            $searchCriteria = $this->addStockFilter($searchCriteria, $args['stockId']);
        }

        $searchResult = $this->locationRepository->getList($searchCriteria);

        $data = [
            'total_count' => $searchResult->getTotalCount(),
            'items' => $this->formatLocationsArray($searchResult->getItems()),
            'page_info' => [
                'page_size' => $searchCriteria->getPageSize(),
                'current_page' => $searchCriteria->getCurrentPage(),
                'total_pages' => $this->getMaxPage($searchCriteria, $searchResult)
            ]
        ];

        return $data;
    }

    /**
     * Build search criteria from query input args
     *
     * @param array $args
     * @param ResolveInfo $info
     * @return SearchCriteriaInterface
     * @throws InputException
     */
    public function buildSearchCriteria(array $args, ResolveInfo $info): SearchCriteriaInterface
    {
        if (!empty($args['filter'])) {
            $args['filter'] = $this->formatFilters($args['filter']);
        }

        $criteria = $this->searchCriteriaBuilder->build($info->fieldName, $args);
        $criteria->setCurrentPage($args['currentPage']);
        $criteria->setPageSize($args['pageSize']);

        return $criteria;
    }

    /**
     * Reformat filters
     *
     * @param array $filters
     * @return array
     * @throws InputException
     */
    public function formatFilters(array $filters): array
    {
        $formattedFilters = [];
        $minimumQueryLength = $this->scopeConfig->getValue(
            Query::XML_PATH_MIN_QUERY_LENGTH,
            ScopeInterface::SCOPE_STORE
        );

        foreach ($filters as $field => $filter) {
            foreach ($filter as $condition => $value) {
                if ($condition === 'match') {
                    // reformat 'match' filter so MySQL filtering behaves like SearchAPI filtering
                    $condition = 'like';
                    $value = str_replace('%', '', trim($value));
                    if (strlen($value) < $minimumQueryLength) {
                        throw new InputException(__('Invalid match filter'));
                    }
                    $value = '%' . preg_replace('/ +/', '%', $value) . '%';
                }
                $formattedFilters[$field] = [$condition => $value];
            }
        }

        return $formattedFilters;
    }

    /**
     * Get maximum number of pages.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param SearchResultsInterface $searchResult
     * @return int
     * @throws GraphQlInputException
     */
    public function getMaxPage(
        SearchCriteriaInterface $searchCriteria,
        SearchResultsInterface $searchResult
    ): int {
        if ($searchCriteria->getPageSize()) {
            $maxPages = ceil($searchResult->getTotalCount() / $searchCriteria->getPageSize());
        } else {
            $maxPages = 0;
        }

        $currentPage = $searchCriteria->getCurrentPage();
        if ($searchCriteria->getCurrentPage() > $maxPages && $searchResult->getTotalCount() > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$currentPage, $maxPages]
                )
            );
        }

        return (int)$maxPages;
    }

    /**
     * Format location models for graphql schema
     *
     * @param LocationInterface[] $locations
     * @return array
     */
    public function formatLocationsArray(array $locations)
    {
        $locationsArray = [];
        foreach ($locations as $location) {
            $locationsArray[$location->getLocationId()] = $location->getData();
            $locationsArray[$location->getLocationId()]['model'] = $location;
        }
        return $locationsArray;
    }

    /**
     * Add Stock Filter
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param int $stockId
     * @return SearchCriteriaInterface
     */
    public function addStockFilter(SearchCriteriaInterface $searchCriteria, int $stockId) : SearchCriteriaInterface
    {
        if (!$stockId) {
            return $searchCriteria;
        }

        $stockIds = $this->getStockIdsLinkedToSameSourcesWithStock->execute($stockId);
        if ($stockIds && count($stockIds)) {
            $stockFilter = $this->filterBuilder
                ->setField(LocationInterface::STOCK_ID)
                ->setValue($stockIds)
                ->setConditionType('in')
                ->create();
            $this->filterGroupBuilder->addFilter($stockFilter);
            $filterGroups = $searchCriteria->getFilterGroups();
            $filterGroups[] = $this->filterGroupBuilder->create();
            $searchCriteria->setFilterGroups($filterGroups);
        }
        return $searchCriteria;
    }
}
