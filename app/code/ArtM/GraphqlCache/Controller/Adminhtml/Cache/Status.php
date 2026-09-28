<?php
namespace ArtM\GraphqlCache\Controller\Adminhtml\Cache;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\GraphqlCache\Model\Cache\GraphqlCache;
use Magento\Framework\App\CacheInterface;

class Status extends Action
{
    protected $resultJsonFactory;
    protected $graphqlCache;
    protected $cache;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        GraphqlCache $graphqlCache,
        CacheInterface $cache
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->graphqlCache = $graphqlCache;
        $this->cache = $cache;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            // Test cache functionality
            $testKey = 'artm_cache_test_' . time();
            $testData = ['test' => true, 'timestamp' => time()];
            
            // Test save
            $this->cache->save(
                json_encode($testData),
                $testKey,
                ['TEST_TAG'],
                300
            );
            
            // Test load
            $cached = $this->cache->load($testKey);
            $cacheWorks = $cached !== false;
            
            // Clean test
            $this->cache->remove($testKey);
            
            $data = [
                'cache_enabled' => $cacheWorks,
                'cache_type' => GraphqlCache::CACHE_TYPE,
                'test_result' => $cached ? json_decode($cached, true) : null,
                'cache_frontend' => get_class($this->cache)
            ];
            
        } catch (\Exception $e) {
            $data = [
                'error' => $e->getMessage(),
                'cache_enabled' => false
            ];
        }
        
        return $result->setData($data);
    }
}