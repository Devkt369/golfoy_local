<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\RewardpointsGraphQl\Model\Resolver;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Search\Model\Query;
use Magento\Store\Model\ScopeInterface;
use Magestore\Rewardpoints\Api\Data\Transaction\TransactionInterface;
use Magestore\Rewardpoints\Api\TransactionRepositoryInterface;

/**
 * Reward point transaction field resolver, used for GraphQL request processing.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerRewardPointTransactionHistory implements ResolverInterface
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
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * CustomerRewardPointTransactionHistory constructor.
     * @param Builder $searchCriteriaBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param TransactionRepositoryInterface $transactionRepository
     * @param CustomerRegistry $customerRegistry
     */
    public function __construct(
        Builder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        TransactionRepositoryInterface $transactionRepository,
        CustomerRegistry $customerRegistry
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->transactionRepository = $transactionRepository;
        $this->customerRegistry = $customerRegistry;
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
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __('The current customer isn\'t authorized.')
            );
        }

        /** @var Customer $customer */
        $currentCustomer = $this->customerRegistry->retrieve($context->getUserId());
        if (!$currentCustomer && !$currentCustomer->getId()) {
            throw new GraphQlInputException(
                __('Something went wrong while loading the customer.')
            );
        }

        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        $searchCriteria = $this->buildSearchCriteria($args, $info, $currentCustomer);
        $searchResult = $this->transactionRepository->getList($searchCriteria);

        return [
            'total_count' => $searchResult->getTotalCount(),
            'items' => $this->formatTransactionsArray($searchResult->getItems()),
            'page_info' => [
                'page_size' => $searchCriteria->getPageSize(),
                'current_page' => $searchCriteria->getCurrentPage(),
                'total_pages' => $this->getMaxPage($searchCriteria, $searchResult)
            ]
        ];
    }

    /**
     * Build search criteria from query input args
     *
     * @param array $args
     * @param ResolveInfo $info
     * @param Customer $currentCustomer
     * @return SearchCriteriaInterface
     */
    public function buildSearchCriteria(
        array $args,
        ResolveInfo $info,
        Customer $currentCustomer
    ): SearchCriteriaInterface {
        if (!empty($args['filter'])) {
            $args['filter'] = $this->formatFilters($args['filter']);
        }
        $args['filter'][TransactionInterface::CUSTOMER_ID] = ['eq' => $currentCustomer->getId()];

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
     * @param TransactionInterface[] $transactions
     * @return array
     */
    public function formatTransactionsArray(array $transactions)
    {
        $transactionsArray = [];
        foreach ($transactions as $transaction) {
            $transactionsArray[$transaction->getTransactionId()] = $transaction->getData();
        }
        return $transactionsArray;
    }
}
