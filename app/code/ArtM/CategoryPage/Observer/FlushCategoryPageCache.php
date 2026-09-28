<?php
namespace ArtM\CategoryPage\Observer;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class FlushCategoryPageCache implements ObserverInterface
{
    private const CACHE_TAG = 'ARTM_CATEGORYPAGE';

    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function execute(Observer $observer)
    {
        $this->cache->clean([self::CACHE_TAG]);
    }
}
