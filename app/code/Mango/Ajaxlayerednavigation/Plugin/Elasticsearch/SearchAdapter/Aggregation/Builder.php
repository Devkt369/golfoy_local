<?php
namespace Mango\Ajaxlayerednavigation\Plugin\Elasticsearch\SearchAdapter\Aggregation;

use Magento\Elasticsearch\SearchAdapter\QueryContainer;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Dynamic\DataProviderInterface;
use Magento\Elasticsearch\SearchAdapter\Aggregation\Builder\BucketBuilderInterface;

class Builder
{
    /**
     * @var DataProviderInterface[]
     */
    protected $dataProviderContainer;
    /**
     * @var BucketBuilderInterface[]
     */
    protected $aggregationContainer;
    /**
     * @var DataProviderFactory
     */
    private $dataProviderFactory;
    /**
     * @var QueryContainer
     */
    private $query = null;
    
    
    protected $boolExpressionFactory;
    protected $requestFactory;
    protected $mapper;
    
    protected $connectionManager;
    
    protected $helper;
    /**
     * @param  DataProviderInterface[] $dataProviderContainer
     * @param  BucketBuilderInterface[] $aggregationContainer
     * @param DataProviderFactory|null $dataProviderFactory
     */
    public function __construct(
        array $dataProviderContainer,
        array $aggregationContainer,
        #DataProviderContainer $dataProviderContainer,
        #AggregationContainer $aggregationContainer,
        \Magento\Framework\Search\Request\Query\BoolExpressionFactory $booleanExpressionFactory,
        \Magento\Framework\Search\RequestFactory $requestFactory,
        \Magento\Elasticsearch\Elasticsearch5\SearchAdapter\Mapper $mapper,
        \Magento\Elasticsearch\SearchAdapter\ConnectionManager $connectionManager,
        \Mango\Ajaxlayerednavigation\Helper\Data $helper,
        \Magento\Elasticsearch\SearchAdapter\Aggregation\DataProviderFactory $dataProviderFactory = null
    ) {
        $this->dataProviderContainer = array_map(
            function (\Magento\Framework\Search\Dynamic\DataProviderInterface $dataProvider) {
                return $dataProvider;
            },
            $dataProviderContainer
        );
        $this->aggregationContainer = array_map(
            function (\Magento\Elasticsearch\SearchAdapter\Aggregation\Builder\BucketBuilderInterface $bucketBuilder) {
                return $bucketBuilder;
            },
            $aggregationContainer
        );
        
            $this->dataProviderFactory = $dataProviderFactory
            ?: ObjectManager::getInstance()->get(\Magento\Elasticsearch\SearchAdapter\Aggregation\DataProviderFactory::class);
        
        
        $this->boolExpressionFactory = $booleanExpressionFactory;
        $this->requestFactory = $requestFactory;
        $this->mapper = $mapper;
        $this->helper = $helper;
        $this->connectionManager = $connectionManager;
    }
    /**
     * Builds aggregations from the search request.
     *
     * This method iterates through buckets and builds all aggregations one by one, passing buckets and relative
     * data into bucket aggregation builders which are responsible for aggregation calculation.
     *
     * @param RequestInterface $request
     * @param array $queryResult
     * @return array
     * @throws \LogicException thrown by DataProviderFactory for validation issues
     * @see \Magento\Elasticsearch\SearchAdapter\Aggregation\DataProviderFactory
     */
    public function aroundBuild(
        \Magento\Elasticsearch\SearchAdapter\Aggregation\Builder $subject,
        \Closure $proceed,
        \Magento\Framework\Search\RequestInterface $request,
        array $queryResult
    ) {
        
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $proceed($request, $queryResult);
        }
        #to create custom query result, use the same object...
        
        
        
        $client = $this->connectionManager->getConnection();
        
        
    #$e = new \Exception();
#echo '<pre>';
#print_r($e->getTraceAsString());
#exit;
        
        
        $aggregations = [];
        $buckets = $request->getAggregation();
        
   #     echo "request class . "  . get_class($request) . "<br/>";
        
       # echo "<pre style='background:#ff99aa;'>";
        
       # print_r($buckets);
        #echo "</pre>";
        
        
        $query = $request->getQuery();
    #    echo "after query from request";
        
     #   echo "<pre style='background:#ffAA99;'>";
      #  print_r($query);
       # echo "</pre>";
        
        
        ##
        $processedQueryResult = false;
        
        
        # old parameters!!
        #$table = false; ## replaced in new version with processqueryresult
        #$aggregations = [];
        #$buckets = $request->getAggregation();
        #$dataProvider = $this->dataProviderContainer->get($request->getIndex());  // where is this used
        #$query = $request->getQuery();
     
        
        #echo "::::" . $request->getIndex() . "<br/>";
        $dataProvider = $this->dataProviderFactory->create(
            $this->dataProviderContainer[$request->getIndex()],
            $this->query
        );
        foreach ($buckets as $bucket) {
            
            /* need to create new request object */
            $use_new_temp_table = false;
            $field = $bucket->getField();
            $must = $query->getMust();
            $should = $query->getShould();
            
            
            /* decimal/price attributes */
            
            $_is_decimal_attribute = (  $bucket instanceof \Magento\Framework\Search\Request\Aggregation\DynamicBucket );
            #echo  $_is_decimal_attribute ? ( "<br/>" . $_is_decimal_attribute  . " decimal ..<br/>") : "";
            
            if ($_is_decimal_attribute) {
                if (isset($must[$field])) {
                    unset($must[$field]);
                    $use_new_temp_table = true;
                }
                
                if (isset($must[$field . "_query"])) {
                    unset($must[$field . "_query"]);
                    $use_new_temp_table = true;
                }
            } elseif (in_array($field, ['category_ids'])) {
                if (isset($must["category"])) {
                    unset($must["category"]);
                    $use_new_temp_table = true;
                }
            } else {/* regular attributes */
                
                # check if "AND" attribute
                $isAndCondition = $this->helper->isAndCondition($field);
                if (!$isAndCondition && (isset($should[$field . "_query"]) || isset($must[$field . "_query"]))) {
                
                #echo "has to remove should+must field " . $field . "..<br/>";
                #echo"<pre style='background:#ff5566;'>";
                #print_r($should);
                #print_r($must);
                
                
                    if (isset($should[$field . "_query"])) {
                        unset($should[$field . "_query"]);
                        $use_new_temp_table = true;
                    }
                /* must test this*/
                    if (isset($must[$field . "_query"])) {
                        unset($must[$field . "_query"]);
                        $use_new_temp_table = true;
                    }
                #echo "after removing must<br/>";
                #print_r($must);
                #echo "</pre>";
                }
                
            }
            
            
            
            if ($use_new_temp_table) { # || count($this->helper->getMultiselectAndFilter())) {
                $query_temp = $this->boolExpressionFactory->create(
                    [
                            'name' => $query->getName(),
                            'boost' => $query->getBoost(),
                            'must' => $must,
                            'should' => $should,
                            'not' => $query->getMustNot()
                        ]
                );
                $request_temp = $this->requestFactory->create(
                    ['name' => $request->getName(),
                            'indexName' => $request->getIndex(),
                            'query' => $query_temp,
                            'from' => $request->getFrom(),
                            'size' => $request->getSize(),
                            'dimensions' => $request->getDimensions(),
                            'buckets' => $request->getAggregation()]
                );
                
                
                #$select = $this->mapper->buildQuery($request_temp);
                
                /*if($this->helper->getMultiselectAndFilter()){
                    $select->where('entity_id in (?)', $this->helper->getMultiselectAndFilter() );
                }*/
              #echo "use temp table for " . $field . " and build query <br/>"  ;
             #   $temporaryStorage = $this->temporaryStorageFactory->create();
              #  $table = $temporaryStorage->storeDocumentsFromSelect($select);
              
              
                $tempQuery = $this->mapper->buildQuery($request_temp);
              #$processedQueryResult = $queryResult;
            /*  echo "<pre style='background:#ffbbff;'>";
              print_r($tempQuery);
              echo "</pre>";
              */
                $processedQueryResult = $client->query($tempQuery);
              
              
            } else {
                #echo "don't use temp table for " . $field . " <br/>"  ;
                
               # $table = $documentsTable;
                $processedQueryResult = $queryResult;
            }
            
            
            # a new "table" source with re calculated data
            # should be created and passed to the aggregations builder
            
            
            $bucketAggregationBuilder = $this->aggregationContainer[$bucket->getType()];
            
            #echo "agg builder " .  get_class($bucketAggregationBuilder ) . " <br/>";
            
            # echo  " bucketdata : " . $bucket->getName() . "  . " .  $bucket->getType() . "::" . get_class($bucketAggregationBuilder) . "<br/>";
            
            ### older function with mango ajax layered navigation
            /*
            $aggregations[$bucket->getName()] = $aggregationBuilder->build(
                    $dataProvider,  -
                    $request->getDimensions(), -
                    $bucket, -
                    $table  <<<--- queryresult!!!
                );
            */
            
            
            if ($field == "price") {
                #$info = $dataProvider->getAggregations($this->entityStorageFactory->create($table));
                /* return min-max value instead of ranges... */
                
                #echo "<pre>";
                
                #print_r( $bucket );
                
                #print_r($request->getDimensions());
                
                #print_r($processedQueryResult);
                
                
                #$info = $bucketAggregationBuilder->build(
                #    $bucket,
                #    $request->getDimensions(),
                #    $processedQueryResult,
                #    $dataProvider
                #);
                
                #print_r($info);
                #echo "data provider<br/>";
                #echo get_class($dataProvider);
                
                
                #echo "</pre>";
                
               # exit;
                
                $info = $processedQueryResult['aggregations']['price_bucket'];
                
                if( !isset($info['min']) ){
                    $info['min'] = 0;
                }
                if( !isset($info['max']) ){
                    $info['max'] = 0;
                }
                if( !isset($info['count']) ){
                    $info['count'] = 0;
                }
                $aggregations[$bucket->getName()] = [
                    "min" => [
                        'value' => 'min',
                        $field => (int) $info['min']
                    ],
                    "max" => [
                        'value' => 'max',
                        $field => (int) $info['max']
                    ],
                    "count" => [
                        'value' => 'count',
                        'count' => (int) $info['count']
                    ]
                ];
                
                #print_r($aggregations[$bucket->getName()]);
                
                
            } elseif ($_is_decimal_attribute) { //$field == "price") {
               # $info = $this->decimalDataProvider->getAggregationsDecimal($this->entityStorageFactory->create($table), $field);
        #echo $field . "**" . $bucket->getName();
        #exit;
                #echo "<pre>";
                #print_r($processedQueryResult);
                #echo "</pre>";
                
                $info = $processedQueryResult['aggregations'][$bucket->getName()];
                /* return min-max value instead of ranges... */
                $aggregations[$bucket->getName()] = [
                    "min" => [
                        'value' => 'min',
                        $field => (int) $info['min']
                    ],
                    "max" => [
                        'value' => 'max',
                        $field => (int) $info['max']
                    ],
                    "count" => [
                        'value' => 'count',
                        'count' => (int) $info['count']
                    ]
                ];
            } else {
                #$aggregations[$bucket->getName()] = $aggregationBuilder->build(
                #    $dataProvider,
                #    $request->getDimensions(),
                #    $bucket,
                #    $table
                #);
                $aggregations[$bucket->getName()] = $bucketAggregationBuilder->build(
                    $bucket,
                    $request->getDimensions(),
                    $processedQueryResult,
                    $dataProvider
                );
            }
            
            
            
            
            
            #$aggregations[$bucket->getName()] = $bucketAggregationBuilder->build(
            #    $bucket,
            #    $request->getDimensions(),
            #    $processedQueryResult,
            #    $dataProvider
            #);
            
            
        }
        $this->query = null;
        
        #print_r($aggregations);
        #exit;
        
        return $aggregations;
    }
    /**
     * Sets the QueryContainer instance to the internal property in order to use it in build process
     *
     * @param QueryContainer $query
     * @return $this
     */
    public function aroundSetQuery(
        \Magento\Elasticsearch\SearchAdapter\Aggregation\Builder $subject,
        \Closure $proceed,
        QueryContainer $query
    ) {
        $this->query = $query;
        return $proceed($query);
        
        #return $this;
    }
}
