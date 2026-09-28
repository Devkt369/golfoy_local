<?php

declare(strict_types=1);

namespace ArtM\GraphqlCache\Controller;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\GraphQl\Exception\ExceptionFormatter;
use Magento\Framework\GraphQl\Query\Fields as QueryFields;
use Magento\Framework\GraphQl\Query\QueryParser;
use Magento\Framework\GraphQl\Query\QueryProcessor;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\Response;
use Magento\GraphQl\Helper\Query\Logger\LogData;
use Magento\GraphQl\Model\Query\ContextFactoryInterface;
use Magento\GraphQl\Model\Query\Logger\LoggerPool;
use Magento\Framework\App\CacheInterface;

use ArtM\GraphqlCache\Helper\Data as GraphqlHelper;
use Magento\Store\Model\StoreManagerInterface;

class GraphQl implements FrontControllerInterface
{
    private $response;
    private $schemaGenerator;
    private $jsonSerializer;
    private $queryProcessor;
    private $graphQlError;
    private $requestProcessor;
    private $queryFields;
    private $jsonFactory;
    private $httpResponse;
    private $contextFactory;
    private $logDataHelper;
    private $loggerPool;
    private $areaList;
    private $queryParser;
    private $cache;
    protected $request;
    protected $helper;
    protected $storeManager;

    public function __construct(
        Response $response,
        \Magento\Framework\GraphQl\Schema\SchemaGeneratorInterface $schemaGenerator,
        SerializerInterface $jsonSerializer,
        QueryProcessor $queryProcessor,
        ExceptionFormatter $graphQlError,
        ContextInterface $resolverContext, // deprecated, still passed
        \Magento\GraphQl\Controller\HttpRequestProcessor $requestProcessor,
        QueryFields $queryFields,
        JsonFactory $jsonFactory = null,
        HttpResponse $httpResponse = null,
        ContextFactoryInterface $contextFactory = null,
        LogData $logDataHelper = null,
        LoggerPool $loggerPool = null,
        AreaList $areaList = null,
        QueryParser $queryParser = null,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        GraphqlHelper $helper,
        CacheInterface $cache
    ) {
        $this->response = $response;
        $this->schemaGenerator = $schemaGenerator;
        $this->jsonSerializer = $jsonSerializer;
        $this->queryProcessor = $queryProcessor;
        $this->graphQlError = $graphQlError;
        $this->requestProcessor = $requestProcessor;
        $this->queryFields = $queryFields;
        $this->jsonFactory = $jsonFactory ?: ObjectManager::getInstance()->get(JsonFactory::class);
        $this->httpResponse = $httpResponse ?: ObjectManager::getInstance()->get(HttpResponse::class);
        $this->contextFactory = $contextFactory ?: ObjectManager::getInstance()->get(ContextFactoryInterface::class);
        $this->logDataHelper = $logDataHelper ?: ObjectManager::getInstance()->get(LogData::class);
        $this->loggerPool = $loggerPool ?: ObjectManager::getInstance()->get(LoggerPool::class);
        $this->areaList = $areaList ?: ObjectManager::getInstance()->get(AreaList::class);
        $this->queryParser = $queryParser ?: ObjectManager::getInstance()->get(QueryParser::class);
        $this->cache = $cache;
        $this->request = $request;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    public function dispatch(RequestInterface $request): ResponseInterface
    {
        // if(!isset($_SERVER['HTTP_ORIGIN'])){
        //     header('HTTP/1.1 403 Forbidden');
        //     echo json_encode(['error' => 'GraphQL access denied']);
        //     exit;
        // }
        // $origin = $_SERVER['HTTP_ORIGIN'];
        // $allowedOrigins = $this->helper->getAllowedOrigins();

        // if (!$origin || !in_array($origin, $allowedOrigins)) {
        //     header('HTTP/1.1 403 Forbidden');
        //     echo json_encode(['error' => 'URL: '.$origin.' GraphQL access denied']);
        //     exit;
        // }

        $this->areaList->getArea(Area::AREA_GRAPHQL)->load(Area::PART_TRANSLATE);

        $statusCode = 200;
        $jsonResult = $this->jsonFactory->create();
        $data = $this->getDataFromRequest($request);
        $schema = null;
        $result = [];

        try {
            $this->requestProcessor->validateRequest($request);
            $query = $data['query'] ?? '';
            // $queryMd5 = md5($query);
            // Try to load from cache
            // $cached = $this->getFromCache($queryMd5);
            // if ($cached !== null) {
            //     $jsonResult->setHttpResponseCode($statusCode);
            //     // die($queryMd5);
            //     $jsonResult->setData($this->jsonSerializer->unserialize($cached));
            //     $jsonResult->renderResult($this->httpResponse);
            //     return $this->httpResponse;
            // }

            $parsedQuery = $this->queryParser->parse($query);
            $data['parsedQuery'] = $parsedQuery;

            $this->queryFields->setQuery($parsedQuery, $data['variables'] ?? null);
            $schema = $this->schemaGenerator->generate();

            $result = $this->queryProcessor->process(
                $schema,
                $parsedQuery,
                $this->contextFactory->create(),
                $data['variables'] ?? []
            );

            // Save result to cache
            // $this->saveToCache($queryMd5, $this->jsonSerializer->serialize($result));
        } catch (\Exception $error) {
            $result['errors'] = isset($result['errors']) ? $result['errors'] : [];
            $result['errors'][] = $this->graphQlError->create($error);
            $statusCode = ExceptionFormatter::HTTP_GRAPH_QL_SCHEMA_ERROR_STATUS;
        }

        $jsonResult->setHttpResponseCode($statusCode);
        $jsonResult->setData($result);
        $jsonResult->renderResult($this->httpResponse);

        if (!isset($data['query']) || strpos($data['query'], 'IntrospectionQuery') === false) {
            $queryInformation = $this->logDataHelper->getLogData($request, $data, $schema, $this->httpResponse);
            $this->loggerPool->execute($queryInformation);
        }

        return $this->httpResponse;
    }

    private function getDataFromRequest(RequestInterface $request): array
    {
        /** @var Http $request */
        if ($request->isPost()) {
            $data = $this->jsonSerializer->unserialize($request->getContent());
        } elseif ($request->isGet()) {
            $data = $request->getParams();
            $data['variables'] = isset($data['variables']) ?
                $this->jsonSerializer->unserialize($data['variables']) : null;
            $data['variables'] = is_array($data['variables']) ? $data['variables'] : null;
        } else {
            return [];
        }

        return $data;
    }

    private function saveToCache(string $cacheId, string $queryResponse): void
    {
        $cacheKey = $this->generateCacheKey($cacheId);
        $this->cache->save($queryResponse, $cacheKey, ['GRAPHQL_RESPONSE_CACHE'], 0); 
    }

    private function getFromCache(string $cacheId): ?string
    {
        $cacheKey = $this->generateCacheKey($cacheId);
        $cached = $this->cache->load($cacheKey);
        return is_string($cached) ? $cached : null;
    }

    private function generateCacheKey(string $cacheId): string
    {
        return 'graphql_query_' . $cacheId;
    }
}
