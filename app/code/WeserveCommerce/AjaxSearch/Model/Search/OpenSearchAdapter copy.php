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

    private function buildQuery(string $queryText, array $filters, bool $isAutocomplete, int $storeId): array
    {
        $queryText = trim($queryText);
        $bool = [];

        if ($queryText !== '') {
            $should = [
                [
                    'match_phrase_prefix' => [
                        'attribute_set_name' => [
                            'query' => $queryText,
                            'boost' => 12
                        ]
                    ]
                ],
                [
                    'match_phrase_prefix' => [
                        'name' => [
                            'query' => $queryText,
                            'boost' => 10
                        ]
                    ]
                ],
                [
                    'match_phrase_prefix' => [
                        'brand' => [
                            'query' => $queryText,
                            'boost' => 9
                        ]
                    ]
                ],
                [
                    'match_phrase_prefix' => [
                        'sku' => [
                            'query' => $queryText,
                            'boost' => 8
                        ]
                    ]
                ],
                [
                    'match' => [
                        'attribute_set_name' => [
                            'query' => $queryText,
                            'operator' => 'and',
                            'boost' => 7
                        ]
                    ]
                ],
                [
                    'match' => [
                        'name' => [
                            'query' => $queryText,
                            'operator' => 'and',
                            'boost' => 6
                        ]
                    ]
                ],
                [
                    'match' => [
                        'brand' => [
                            'query' => $queryText,
                            'operator' => 'and',
                            'boost' => 5
                        ]
                    ]
                ],
                [
                    'match' => [
                        'description' => [
                            'query' => $queryText,
                            'operator' => 'and',
                            'boost' => 2
                        ]
                    ]
                ],
                [
                    'match' => [
                        'short_description' => [
                            'query' => $queryText,
                            'operator' => 'and',
                            'boost' => 2
                        ]
                    ]
                ]
            ];

            if ($this->config->isFuzzySearchEnabled($storeId)) {
                foreach (['attribute_set_name', 'name', 'brand', 'sku', 'description', 'short_description'] as $field) {
                    $should[] = [
                        'match' => [
                            $field => [
                                'query' => $queryText,
                                'fuzziness' => 'AUTO',
                                'prefix_length' => 1,
                                'boost' => 1
                            ]
                        ]
                    ];
                }
            }

            $bool['should'] = $should;
            $bool['minimum_should_match'] = 1;
        } else {
            $bool['must'][] = ['match_all' => (object)[]];
        }

        $bool['filter'][] = ['term' => ['store_id' => $storeId]];

        if (!empty($filters)) {
            $bool['filter'] = $filters;
        }

        return ['bool' => $bool];
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
