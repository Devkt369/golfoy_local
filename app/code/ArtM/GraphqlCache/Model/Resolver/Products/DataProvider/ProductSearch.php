<?php
declare(strict_types=1);

namespace ArtM\GraphqlCache\Model\Resolver\Products\DataProvider;

use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionPostProcessorInterface;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierFactory;
use Magento\Framework\Api\Search\SearchCriteriaInterfaceFactory as SearchCriteriaFactory;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Product field data provider for product search, used for GraphQL resolver processing.
 */
class ProductSearch extends \Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ProductSearch
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ProductSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionPreProcessor;

    /**
     * @var CollectionPostProcessorInterface
     */
    private $collectionPostProcessor;

    /**
     * @var SearchResultApplierFactory;
     */
    private $searchResultApplierFactory;

    /**
     * @var SearchCriteriaFactory
     */
    private $searchCriteriaFactory;

    /**
     * @param CollectionFactory $collectionFactory
     * @param ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionPreProcessor
     * @param CollectionPostProcessorInterface $collectionPostProcessor
     * @param SearchResultApplierFactory $searchResultsApplierFactory
     * @param SearchCriteriaFactory $searchCriteriaFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ProductSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionPreProcessor,
        CollectionPostProcessorInterface $collectionPostProcessor,
        SearchResultApplierFactory $searchResultsApplierFactory,
        SearchCriteriaFactory $searchCriteriaFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionPreProcessor = $collectionPreProcessor;
        $this->collectionPostProcessor = $collectionPostProcessor;
        $this->searchResultApplierFactory = $searchResultsApplierFactory;
        $this->searchCriteriaFactory = $searchCriteriaFactory;
    }

    /**
     * Get list of product data with full data set. Adds eav attributes to result set from passed in array
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param SearchResultInterface $searchResult
     * @param array $attributes
     * @param ContextInterface|null $context
     * @return SearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria,
        SearchResultInterface $searchResult,
        array $attributes = [],
        ?ContextInterface $context = null
    ): SearchResultsInterface {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        // Check for crecommend DESC sort order
        $sortOrders = $searchCriteria->getSortOrders();
        $crecommendSort = false;
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                if ($sortOrder->getField() === 'crecommend' && $sortOrder->getDirection() === 'DESC') {
                    $crecommendSort = true;
                    break;
                }
            }
        }

        if ($crecommendSort) {
            // Join catalog_category_product and sort by sorting_points DESC
            $collection->getSelect()->joinLeft(
                ['ccp' => 'catalog_category_product'],
                'e.entity_id = ccp.product_id',
                []
            )->order('ccp.sorting_points DESC');
        }

        $collection->getSelect()->group('e.entity_id');

        //Apply CatalogSearch results from search and join table
        $searchResultsApplier = $this->searchResultApplierFactory->create(
            [
                'collection' => $collection,
                'searchResult' => $searchResult,
            ]
        );
        $searchResultsApplier->apply();

        //Empty search criteria for backward compatibility.
        //Search criteria must be already applied to the search result.
        $emptySearchCriteria = $this->searchCriteriaFactory->create();
        $this->collectionPreProcessor->process($collection, $emptySearchCriteria, $attributes, $context);
        $collection->load();
        $this->collectionPostProcessor->process($collection, $attributes, $context);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($searchResult->getTotalCount());

        return $searchResults;
    }
}
