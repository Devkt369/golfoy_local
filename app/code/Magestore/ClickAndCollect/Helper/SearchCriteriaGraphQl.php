<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\InputException;
use Magento\Search\Model\Query;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder;

/**
 * Class SearchCriteriaGraphql
 *
 * Used for helper Search Criteria Graphql
 */
class SearchCriteriaGraphQl extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Builder
     */
    protected $searchCriteriaBuilder;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Builder $searchCriteriaBuilder
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Builder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
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
}
