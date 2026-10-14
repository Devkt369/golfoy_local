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
            $bool['must'] = $this->buildRequiredTermClauses($queryText, $storeId);
            // $textQuery (single-char terms like a bare "S" size code
            // removed) covers general/core matching. The FULL $queryText,
            // single-char terms included, still reaches configured
            // attribute fields via getPriorityFields() inside
            // buildShouldClauses() below - so "S" still correctly matches
            // a shirt_size attribute, it's just excluded from noisy
            // matching against name/description.
            $bool['should'] = $this->buildShouldClauses($textQuery, $queryText, $isAutocomplete, $storeId);
            $bool['minimum_should_match'] = 1;
        } elseif ($priceFilter === null) {
            $bool['must'][] = ['match_all' => (object)[]];
        }
        // If the query was ONLY a price phrase (e.g. "under 900" with no
        // product term left), we deliberately don't fall back to match_all -
        // the price filter below is enough to scope the results.

        $bool['filter'] = $this->buildFilters($storeId, $filters, $priceFilter);

        return ['bool' => $bool];
    }

    /**
     * Require every meaningful query term to match somewhere in the
     * searchable fields. A configured synonym is an alternate match for
     * its source term, not a replacement for the other shopper terms.
     */
    private function buildRequiredTermClauses(string $queryText, int $storeId): array
    {
        $terms = $this->getSearchTerms($queryText);
        if (count($terms) < 2) {
            return [];
        }

        // Requiring each term against every configured EAV field grows
        // multiplicatively with long queries and can exceed OpenSearch's
        // clause limit. Keep short attribute searches broad, but constrain
        // long product-title searches to the small core field set.
        $fields = count($terms) > 4
            ? $this->getCoreFields()
            : $this->getPriorityFields($storeId);
        $synonyms = $this->config->getCompoundWordSynonyms($storeId);
        $must = [];

        foreach ($terms as $term) {
            $alternatives = [
                [
                    'multi_match' => [
                        'query' => $term,
                        'type' => 'best_fields',
                        'operator' => 'or',
                        'fields' => $fields
                    ]
                ]
            ];

            $inflectedTerm = $this->getSimpleInflection($term);
            if ($inflectedTerm !== null) {
                $alternatives[] = [
                    'multi_match' => [
                        'query' => $inflectedTerm,
                        'type' => 'best_fields',
                        'operator' => 'or',
                        'fields' => $fields
                    ]
                ];
            }

            $normalizedTerm = mb_strtolower($term);
            if (isset($synonyms[$normalizedTerm])) {
                $alternatives[] = [
                    'multi_match' => [
                        'query' => $synonyms[$normalizedTerm],
                        'type' => 'best_fields',
                        'operator' => 'and',
                        'fields' => $fields
                    ]
                ];
            }

            $must[] = [
                'bool' => [
                    'should' => $alternatives,
                    'minimum_should_match' => 1
                ]
            ];
        }

        if (count($terms) > 4) {
            return [
                [
                    'bool' => [
                        'should' => [
                            ['bool' => ['must' => $must]],
                            [
                                'multi_match' => [
                                    'query' => implode(' ', $terms),
                                    'type' => 'best_fields',
                                    'operator' => 'or',
                                    'minimum_should_match' => '80%',
                                    'fields' => $this->getPriorityFields($storeId)
                                ]
                            ]
                        ],
                        'minimum_should_match' => 1
                    ]
                ]
            ];
        }

        return $must;
    }

    private function getSearchTerms(string $queryText): array
    {
        $terms = preg_split('/\s+/u', trim($queryText), -1, PREG_SPLIT_NO_EMPTY);
        if (!$terms) {
            return [];
        }

        return array_values(array_filter($terms, static function ($term) {
            return preg_match('/[\pL\pN]/u', $term) === 1;
        }));
    }

    private function getSimpleInflection(string $term): ?string
    {
        if (mb_strlen($term) <= 2) {
            return null;
        }

        if (preg_match('/ies$/iu', $term)) {
            return mb_substr($term, 0, -3) . 'y';
        }

        if (preg_match('/s$/iu', $term)) {
            return mb_substr($term, 0, -1);
        }

        return $term . 's';
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
     * Core identity fields: cheap to query exhaustively (phrase_prefix,
     * fuzzy) because there are few of them. Priority order is encoded
     * directly via the boost suffix, so no separate scoring pass is
     * needed - this IS the relevance tiering.
     *
     * Category paths are indexed as readable text and are prioritized
     * immediately after product names.
     */
    private function getCoreFields(): array
    {
        return [
            'name^9',
            'category_paths^8',
            'attribute_set_name^7',
            'brand^6',
            'sku^5',
            'description^3',
            'short_description^2'
        ];
    }

    /**
     * Core fields plus every configured attribute at a flat, low boost.
     * Safe to use with plain best_fields matching (bounded by the number
     * of QUERY terms), but NOT with phrase_prefix or fuzzy - those expand
     * per matching INDEX term per field (phrase_prefix defaults to up to
     * 50 expansions PER FIELD), so running them across 40+ attribute
     * fields is what caused too_many_nested_clauses here.
     */
    private function getPriorityFields(int $storeId): array
    {
        $fields = $this->getCoreFields();

        foreach ($this->getConfiguredAttributeFields($storeId) as $attributeCode) {
            $fields[] = $attributeCode . '^1';
        }

        return $fields;
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

    /**
     * @param string $coreQueryText single-char terms (e.g. a bare "S" size
     *        code) stripped out - used against name/description/etc. so a
     *        lone letter doesn't noisily match everywhere.
     * @param string $fullQueryText original text, single-char terms kept -
     *        used against the full attribute set so "S" still correctly
     *        matches a shirt_size attribute, where it's precise rather
     *        than noisy.
     */
    private function buildShouldClauses(string $coreQueryText, string $fullQueryText, bool $isAutocomplete, int $storeId): array
    {
        $coreFields = $this->getCoreFields();
        $allFields = count($this->getSearchTerms($fullQueryText)) > 4
            ? $coreFields
            : $this->getPriorityFields($storeId);

        $should = [
            // Prefix matching for autocomplete UX. Deliberately restricted
            // to the small core field set, with max_expansions capped low:
            // phrase_prefix expands the last term into up to 50 index
            // terms PER FIELD by default, so running this across 40+
            // attribute fields alone can exceed OpenSearch's 1024-clause
            // limit (too_many_nested_clauses) before anything else runs.
            [
                'multi_match' => [
                    'query' => $coreQueryText,
                    'type' => 'phrase_prefix',
                    'max_expansions' => 10,
                    'fields' => $coreFields
                ]
            ],
            // Full relevance match across EVERY configured attribute, full
            // query text (single-char terms included). Safe here because
            // plain term matching is bounded by the number of query terms,
            // not by how many terms exist in the index - unlike
            // phrase_prefix/fuzzy above and below.
            [
                'multi_match' => [
                    'query' => $fullQueryText,
                    'type' => 'best_fields',
                    'operator' => 'or',
                    'minimum_should_match' => 1,
                    'fields' => $allFields
                ]
            ]
        ];

        if ($this->isSkuLikeQuery($fullQueryText)) {
            array_unshift($should, [
                'constant_score' => [
                    'filter' => [
                        'term' => ['sku.keyword' => $fullQueryText]
                    ],
                    'boost' => 1000
                ]
            ], [
                'match_phrase' => [
                    'sku' => [
                        'query' => $fullQueryText,
                        'boost' => 75
                    ]
                ]
            ]);
        }

        if ($this->config->isFuzzySearchEnabled($storeId)) {
            // Typo tolerance - same clause-count reasoning as
            // phrase_prefix above, so also kept to core fields only with
            // a capped expansion count.
            $should[] = [
                'multi_match' => [
                    'query' => $coreQueryText,
                    'type' => 'best_fields',
                    'operator' => 'or',
                    'minimum_should_match' => '60%',
                    'fuzziness' => 'AUTO',
                    'prefix_length' => 1,
                    'max_expansions' => 10,
                    'fields' => $coreFields
                ]
            ];
        }

        // Compound-word terms ("tshirt" vs "t-shirt") tokenize completely
        // differently under a standard analyzer - hyphen is a token
        // separator, so "t-shirt" indexes as two tokens ["t", "shirt"]
        // while "tshirt" is a single token. Fuzziness/edit-distance won't
        // bridge that gap, so we explicitly search alternate spellings too.
        // Kept to core fields: best_fields itself is cheap, but there can
        // be up to 3 variants and this runs on top of everything above.
        foreach ($this->buildCompoundWordVariants($coreQueryText, $storeId) as $variant) {
            $should[] = [
                'multi_match' => [
                    'query' => $variant,
                    'type' => 'best_fields',
                    'operator' => 'or',
                    'minimum_should_match' => '75%',
                    'boost' => 4,
                    'fields' => $coreFields
                ]
            ];
        }

        return $should;
    }

    private function isSkuLikeQuery(string $queryText): bool
    {
        return preg_match('/^[\pL\pN][\pL\pN._\/-]*$/u', $queryText) === 1
            && mb_strlen($queryText) >= 3;
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

        foreach ($knownCompounds as $term => $replacement) {
            if (count($variants) >= 3) {
                break;
            }

            if ($term === $lower || $replacement === '') {
                continue;
            }

            $pattern = '/(?<![\pL\pN_])' . preg_quote($term, '/') . '(?![\pL\pN_])/iu';
            $variant = preg_replace($pattern, $replacement, $queryText, 1, $replacements);
            if ($replacements > 0) {
                $variants[] = $variant;
            }
        }

        // Generic hyphen handling for anything not in the list above: try
        // the query both joined together and spaced out, since we don't
        // know which form matches how the catalog was indexed.
        if (strpos($queryText, '-') !== false && count($variants) < 3) {
            $spaced = trim(str_replace('-', ' ', $queryText));
            $joined = str_replace('-', '', $queryText);
            $variants[] = $spaced;
            $variants[] = $joined;
        }

        return array_slice(
            array_values(array_unique(array_diff($variants, [$queryText]))),
            0,
            3
        );
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