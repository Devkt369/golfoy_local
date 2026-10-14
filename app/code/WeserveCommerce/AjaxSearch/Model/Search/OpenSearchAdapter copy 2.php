<?php
namespace WeserveCommerce\AjaxSearch\Model\Search;

use Magento\Framework\HTTP\Client\CurlFactory;
use Psr\Log\LoggerInterface;
use WeserveCommerce\AjaxSearch\Api\ProductIndexResolverInterface;
use WeserveCommerce\AjaxSearch\Api\SearchEngineAdapterInterface;
use WeserveCommerce\AjaxSearch\Model\Config;

class OpenSearchAdapter implements SearchEngineAdapterInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ProductIndexResolverInterface
     */
    private $indexResolver;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Config $config,
        ProductIndexResolverInterface $indexResolver,
        CurlFactory $curlFactory,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->indexResolver = $indexResolver;
        $this->curlFactory = $curlFactory;
        $this->logger = $logger;
    }

    public function search(
        string $query,
        int $storeId,
        array $filters = [],
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->executeQuery($query, $storeId, $filters, $limit, $offset, false);
    }

    public function autocomplete(
        string $query,
        int $storeId,
        array $filters = [],
        int $limit = 8
    ): array {
        return $this->executeQuery($query, $storeId, $filters, $limit, 0, true);
    }

    public function isConnected(): bool
    {
        try {
            $curl = $this->createClient(3, 0);
            $curl->get(rtrim($this->config->getServerBaseUrl(0), '/') . '/');
            return $curl->getStatus() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function executeQuery(
        string $queryText,
        int $storeId,
        array $filters,
        int $limit,
        int $offset,
        bool $isAutocomplete
    ): array {
        $indexName = $this->indexResolver->resolve($storeId);
        $endpointUrl = sprintf(
            '%s/%s/_search',
            rtrim($this->config->getServerBaseUrl($storeId), '/'),
            rawurlencode($indexName)
        );

        $payload = [
            'from' => $offset,
            'size' => max(1, $limit),
            'track_total_hits' => true,
            'query' => $this->buildQuery($queryText, $filters, $isAutocomplete, $storeId)
        ];

        try {
            $curl = $this->createClient($this->config->getTimeout($storeId), $storeId);
            $curl->addHeader('Content-Type', 'application/json');
            $curl->post($endpointUrl, json_encode($payload));

            $status = (int)$curl->getStatus();
            $body = (string)$curl->getBody();

            if ($status >= 200 && $status < 300) {
                $response = json_decode($body, true);
                if (is_array($response)) {
                    $total = $response['hits']['total']['value'] ?? count($response['hits']['hits'] ?? []);
                    return [
                        'total' => (int)$total,
                        'hits' => $response['hits']['hits'] ?? [],
                        'took_ms' => (int)($response['took'] ?? 0),
                        'index' => $indexName
                    ];
                }
            }

            $error = sprintf('OpenSearch HTTP %d: %s', $status, $body);
            $this->logger->error('FastSearch ' . $error);
            return $this->emptyResult($indexName, $error);
        } catch (\Exception $e) {
            $this->logger->critical('FastSearch OpenSearch exception: ' . $e->getMessage());
            return $this->emptyResult($indexName, $e->getMessage());
        }
    }

    /**
     * The field storing the indexed selling price. Change this if your
     * OpenSearch mapping uses a different/scoped field name
     * (e.g. Magento's default catalogsearch index sometimes uses a
     * website/customer-group scoped field like "price_0_1" rather than a
     * flat "price"). This adapter assumes a flat field, consistent with
     * the other flat fields (name, brand, sku) already used above.
     */
    private const PRICE_FIELD = 'price';

    private function buildQuery(string $queryText, array $filters, bool $isAutocomplete, int $storeId): array
    {
        $queryText = $this->normalizeQuery($queryText);

        ['query' => $queryText, 'priceFilter' => $priceFilter] = $this->extractPriceFilter($queryText);
        $textQuery = $this->removeSingleCharacterTerms($queryText);
        if ($textQuery === '') {
            $textQuery = $queryText;
        }

        $bool = [];

        if ($queryText !== '') {
            $bool['should'] = $this->buildShouldClauses($textQuery, $isAutocomplete, $storeId);
            $configuredAttributeFields = $this->getConfiguredAttributeFields($storeId);
            if ($configuredAttributeFields) {
                $bool['should'][] = [
                    'multi_match' => [
                        'query' => $queryText,
                        'type' => 'best_fields',
                        'operator' => 'or',
                        'fields' => $configuredAttributeFields
                    ]
                ];
            }
            $bool['minimum_should_match'] = 1;
        } elseif ($priceFilter === null) {
            $bool['must'][] = ['match_all' => (object)[]];
        }
        // If the query was ONLY a price phrase (e.g. "under 900" with no
        // product term left), we deliberately don't fall back to match_all -
        // the price filter below is enough to scope the results.

        $bool['filter'] = $this->buildFilters($storeId, $filters, $priceFilter);

        $query = ['bool' => $bool];
        if ($queryText === '') {
            return $query;
        }

        return [
            'function_score' => [
                'query' => $query,
                'functions' => $this->buildPriorityFunctions($queryText, $textQuery, $storeId),
                'score_mode' => 'max',
                'boost_mode' => 'sum'
            ]
        ];
    }

    /**
     * Pulls a price constraint out of natural-language query text, e.g.
     * "tshirt under 900", "shoes over 2000", "jacket between 1000 and 3000",
     * "bag 500-1500". Returns the query text with that phrase stripped out
     * (so the remaining text is the actual product term) plus an OpenSearch
     * range filter, or null if no price phrase was found.
     *
     * @return array{query: string, priceFilter: array|null}
     */
    private function extractPriceFilter(string $queryText): array
    {
        $currency = '(?:rs\.?|inr|rupees|₹|\$)';
        $number = '([\d,]+(?:\.\d+)?)';

        // "between X and Y" / "X to Y"
        if (preg_match(
            '/\b(?:between\s+)?' . $currency . '?\s*' . $number . '\s*(?:to|and|-)\s*' . $currency . '?\s*' . $number . '\s*' . $currency . '?\b/i',
            $queryText,
            $matches
        )) {
            $low = (float)str_replace(',', '', $matches[1]);
            $high = (float)str_replace(',', '', $matches[2]);
            if ($high < $low) {
                [$low, $high] = [$high, $low];
            }
            return [
                'query' => $this->stripMatchedPhrase($queryText, $matches[0]),
                'priceFilter' => ['range' => [self::PRICE_FIELD => ['gte' => $low, 'lte' => $high]]]
            ];
        }

        // "under / below / less than / cheaper than / max 900"
        if (preg_match(
            '/\b(?:under|below|less\s*than|cheaper\s*than|max(?:imum)?)\b\s*' . $currency . '?\s*' . $number . '\s*' . $currency . '?\b/i',
            $queryText,
            $matches
        )) {
            return [
                'query' => $this->stripMatchedPhrase($queryText, $matches[0]),
                'priceFilter' => ['range' => [self::PRICE_FIELD => ['lte' => (float)str_replace(',', '', $matches[1])]]]
            ];
        }

        // "over / above / more than / min 900"
        if (preg_match(
            '/\b(?:over|above|more\s*than|min(?:imum)?)\b\s*' . $currency . '?\s*' . $number . '\s*' . $currency . '?\b/i',
            $queryText,
            $matches
        )) {
            return [
                'query' => $this->stripMatchedPhrase($queryText, $matches[0]),
                'priceFilter' => ['range' => [self::PRICE_FIELD => ['gte' => (float)str_replace(',', '', $matches[1])]]]
            ];
        }

        return ['query' => $queryText, 'priceFilter' => null];
    }

    private function stripMatchedPhrase(string $queryText, string $matchedPhrase): string
    {
        $remaining = str_replace($matchedPhrase, ' ', $queryText);
        return $this->normalizeQuery($remaining);
    }

    /**
     * Collapses accidental double spaces / leading-trailing whitespace so that
     * e.g. "  running   shoes" behaves the same as "running shoes".
     */
    private function normalizeQuery(string $queryText): string
    {
        $queryText = trim($queryText);
        $queryText = (string)preg_replace('/\s+/', ' ', $queryText);
        return $queryText;
    }

    /**
     * Always scope results to the current store, and layer any additional
     * caller-supplied filters (category, price, etc.) on top of it instead
     * of replacing it, so a custom filter can never accidentally leak
     * results from other stores.
     */
    private function buildFilters(int $storeId, array $filters, ?array $priceFilter = null): array
    {
        $filter = [['term' => ['store_id' => $storeId]]];

        if (!empty($filters)) {
            $filter = array_merge($filter, $filters);
        }

        if ($priceFilter !== null) {
            $filter[] = $priceFilter;
        }

        return $filter;
    }

    /**
     * Builds a relevance-first, typo-tolerant set of "should" clauses.
     *
     * Three tiers, ranked by boost:
     *  1. Prefix matches (best for autocomplete / "starts typing" UX)
     *  2. Whole-word relevance matches, where a query does NOT need every
     *     word to match exactly (minimum_should_match) - so "red running
     *     shoe" still finds "red running shoes" or "running shoe" even if
     *     one word doesn't line up, instead of returning zero results.
     *  3. Fuzzy matches, so common typos ("iphon", "nikee") still surface
     *     results, when fuzzy search is enabled for the store.
     */
    /**
     * Relative relevance boosts for base text matching. Tier weights in
     * buildPriorityFunctions() keep matches in higher-priority fields ahead
     * of lower-priority matches.
     */
    private function getPriorityFields(int $storeId): array
    {
        $priority = [
            'name' => 60,
            'attribute_set_name' => 50,
            'category_paths' => 40,
            'brand' => 30
        ];

        $fields = [];
        foreach ($priority as $field => $boost) {
            $fields[] = $field . '^' . $boost;
        }

        foreach ($this->getConfiguredAttributeFields($storeId) as $attributeCode) {
            $fields[] = $attributeCode . '^20';
        }

        $fields[] = 'description^10';
        $fields[] = 'short_description^10';
        $fields[] = 'sku^5';

        return $fields;
    }

    /**
     * Each function assigns a tier bonus to documents matching that field
     * group. score_mode=max makes the highest matching tier determine the
     * bonus; the original query score then ranks matches within that tier.
     */
    private function buildPriorityFunctions(string $queryText, string $textQuery, int $storeId): array
    {
        $tiers = [
            ['fields' => ['name'], 'weight' => 600, 'query' => $textQuery],
            ['fields' => ['attribute_set_name'], 'weight' => 500, 'query' => $textQuery],
            ['fields' => ['category_paths'], 'weight' => 400, 'query' => $textQuery],
            ['fields' => ['brand'], 'weight' => 300, 'query' => $textQuery],
            ['fields' => $this->getConfiguredAttributeFields($storeId), 'weight' => 200, 'query' => $queryText],
            ['fields' => ['description', 'short_description'], 'weight' => 100, 'query' => $textQuery],
            ['fields' => ['sku'], 'weight' => 50, 'query' => $textQuery]
        ];

        $functions = [];
        foreach ($tiers as $tier) {
            if (!$tier['fields']) {
                continue;
            }

            $functions[] = [
                'filter' => [
                    'bool' => [
                        'should' => $this->buildTierMatchClauses($tier['query'], $tier['fields'], $storeId),
                        'minimum_should_match' => 1
                    ]
                ],
                'weight' => $tier['weight']
            ];
        }

        return $functions;
    }

    /**
     * A one-character token such as the size shorthand "S" is too broad
     * when matched against names and descriptions ("5-S" irons, "S" putters).
     * Keep it in the full query for configured size attributes, but omit it
     * from general text matching whenever the query has longer terms.
     */
    private function removeSingleCharacterTerms(string $queryText): string
    {
        $terms = preg_split('/\s+/u', trim($queryText), -1, PREG_SPLIT_NO_EMPTY);
        if (!$terms || count($terms) < 2) {
            return $queryText;
        }

        $remainingTerms = array_values(array_filter($terms, static function ($term) {
            return mb_strlen($term) > 1;
        }));

        return implode(' ', $remainingTerms);
    }

    private function buildTierMatchClauses(string $queryText, array $fields, int $storeId): array
    {
        $clauses = [
            [
                'multi_match' => [
                    'query' => $queryText,
                    'type' => 'best_fields',
                    'operator' => 'or',
                    'fields' => $fields
                ]
            ],
            [
                'multi_match' => [
                    'query' => $queryText,
                    'type' => 'phrase_prefix',
                    'fields' => $fields
                ]
            ]
        ];

        if ($this->config->isFuzzySearchEnabled($storeId)) {
            $clauses[] = [
                'multi_match' => [
                    'query' => $queryText,
                    'type' => 'best_fields',
                    'operator' => 'or',
                    'fuzziness' => 'AUTO',
                    'prefix_length' => 1,
                    'fields' => $fields
                ]
            ];
        }

        return $clauses;
    }

    /**
     * Text EAV fields selected for indexing, excluding fields handled by
     * named priority tiers or non-text/non-search catalog metadata.
     */
    private function getConfiguredAttributeFields(int $storeId): array
    {
        $excluded = [
            'name',
            'sku',
            'attribute_set_name',
            'category_paths',
            'category_ids',
            'brand',
            'description',
            'short_description',
            'price',
            'special_price',
            'url_key',
            'image',
            'small_image',
            'thumbnail',
            'status',
            'visibility',
            'store_id',
            'id'
        ];

        return array_values(array_diff(
            $this->config->getQuickSearchAttributes($storeId),
            $excluded
        ));
    }

    private function buildShouldClauses(string $queryText, bool $isAutocomplete, int $storeId): array
    {
        $priorityFields = $this->getPriorityFields($storeId);

        $should = [
            [
                'multi_match' => [
                    'query' => $queryText,
                    'type' => 'phrase_prefix',
                    'fields' => $priorityFields
                ]
            ],
            [
                'multi_match' => [
                    'query' => $queryText,
                    'type' => 'best_fields',
                    'operator' => 'or',
                    'minimum_should_match' => 1,
                    'fields' => $priorityFields
                ]
            ]
        ];

        if ($this->config->isFuzzySearchEnabled($storeId)) {
            $should[] = [
                'multi_match' => [
                    'query' => $queryText,
                    'type' => 'best_fields',
                    'operator' => 'or',
                    'minimum_should_match' => '60%',
                    'fuzziness' => 'AUTO',
                    'prefix_length' => 1,
                    'fields' => $priorityFields
                ]
            ];
        }

        // Compound-word terms ("tshirt" vs "t-shirt") tokenize completely
        // differently under a standard analyzer - hyphen is a token
        // separator, so "t-shirt" indexes as two tokens ["t", "shirt"]
        // while "tshirt" is a single token. Fuzziness/edit-distance won't
        // bridge that gap, so we explicitly search alternate spellings too.
        foreach ($this->buildCompoundWordVariants($queryText, $storeId) as $variant) {
            $should[] = [
                'multi_match' => [
                    'query' => $variant,
                    'type' => 'best_fields',
                    'operator' => 'or',
                    'minimum_should_match' => '75%',
                    'boost' => 4,
                    'fields' => $priorityFields
                ]
            ];
        }

        return $should;
    }

    /**
     * Product-term spelling variants where the space/hyphen is inconsistent
     * between how shoppers type and how the catalog is indexed (e.g.
     * "tshirt" vs "t shirt" vs "t-shirt"). Sourced from the admin-configured
     * Config::getCompoundWordSynonyms() (Stores > Configuration > Fast
     * Search & Autocomplete > Search Synonyms) instead of being hardcoded
     * here, so merchants can add new mappings without a deploy.
     *
     * The ideal long-term fix is an index-side normalizer (a char_filter
     * that strips hyphens, or a synonym filter) so this isn't needed at
     * query time at all - but that requires changing the index mapping and
     * reindexing, which this adapter alone can't do.
     *
     * @return string[]
     */
    private function buildCompoundWordVariants(string $queryText, int $storeId): array
    {
        $variants = [];
        $lower = mb_strtolower($queryText);

        $knownCompounds = $this->config->getCompoundWordSynonyms($storeId);
        if (isset($knownCompounds[$lower]) && $knownCompounds[$lower] !== $lower) {
            $variants[] = $knownCompounds[$lower];
        }

        // Generic hyphen handling for anything not in the list above: try
        // the query both joined together and spaced out, since we don't
        // know which form matches how the catalog was indexed.
        if (strpos($queryText, '-') !== false) {
            $spaced = trim(str_replace('-', ' ', $queryText));
            $joined = str_replace('-', '', $queryText);
            $variants[] = $spaced;
            $variants[] = $joined;
        }

        return array_values(array_unique(array_diff($variants, [$queryText])));
    }

    private function createClient(int $timeout, int $storeId)
    {
        $curl = $this->curlFactory->create();
        $curl->setTimeout($timeout);
        if ($this->config->isAuthEnabled($storeId)) {
            $username = $this->config->getUsername($storeId);
            $password = $this->config->getPassword($storeId);
            if ($username !== '') {
                $curl->setCredentials($username, $password);
            }
        }
        return $curl;
    }

    private function emptyResult(string $indexName, string $error): array
    {
        return [
            'total' => 0,
            'hits' => [],
            'took_ms' => 0,
            'index' => $indexName,
            'error' => $error
        ];
    }
}