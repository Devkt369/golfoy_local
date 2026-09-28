<?php
namespace ArtM\AdminAI\Model\Repository;

use ArtM\AdminAI\Api\QueryCacheRepositoryInterface;
use ArtM\AdminAI\Model\QueryCache;
use ArtM\AdminAI\Model\QueryCacheFactory;
use ArtM\AdminAI\Model\ResourceModel\QueryCache as QueryCacheResource;
use ArtM\AdminAI\Model\ResourceModel\QueryCache\CollectionFactory as QueryCacheCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class QueryCacheRepository implements QueryCacheRepositoryInterface
{
    /** @var QueryCacheFactory */
    private $queryCacheFactory;

    /** @var QueryCacheResource */
    private $queryCacheResource;

    /** @var QueryCacheCollectionFactory */
    private $queryCacheCollectionFactory;

    /** @var DateTime */
    private $dateTime;

    public function __construct(
        QueryCacheFactory $queryCacheFactory,
        QueryCacheResource $queryCacheResource,
        QueryCacheCollectionFactory $queryCacheCollectionFactory,
        DateTime $dateTime
    ) {
        $this->queryCacheFactory = $queryCacheFactory;
        $this->queryCacheResource = $queryCacheResource;
        $this->queryCacheCollectionFactory = $queryCacheCollectionFactory;
        $this->dateTime = $dateTime;
    }

    public function create(): QueryCache
    {
        return $this->queryCacheFactory->create();
    }

    public function save(QueryCache $cache): QueryCache
    {
        $this->queryCacheResource->save($cache);
        return $cache;
    }

    public function getValidByIntentAndHash(string $intentCode, string $parameterHash): ?QueryCache
    {
        $collection = $this->queryCacheCollectionFactory->create();
        $collection->addFieldToFilter('intent_code', $intentCode);
        $collection->addFieldToFilter('parameter_hash', $parameterHash);
        $collection->addFieldToFilter('expires_at', ['gt' => $this->dateTime->gmtDate('Y-m-d H:i:s')]);
        $collection->setOrder('cache_id', 'DESC');
        $collection->setPageSize(1);

        $cache = $collection->getFirstItem();
        if (!$cache->getId()) {
            return null;
        }

        return $cache;
    }

    public function incrementHitCount(int $cacheId): void
    {
        if ($cacheId <= 0) {
            return;
        }

        $cache = $this->queryCacheFactory->create();
        $this->queryCacheResource->load($cache, $cacheId);
        if (!$cache->getId()) {
            return;
        }

        $hits = (int)$cache->getData('hit_count');
        $cache->setData('hit_count', $hits + 1);
        $this->queryCacheResource->save($cache);
    }
}
