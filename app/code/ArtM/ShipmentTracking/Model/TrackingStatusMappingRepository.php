<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use ArtM\ShipmentTracking\Model\ResourceModel\TrackingStatusMapping\CollectionFactory;

/**
 * Loads the GLOBAL status mapping from DB, caches it.
 * No carrier-specific logic — one mapping applies to all carriers.
 */
class TrackingStatusMappingRepository
{
    private const CACHE_KEY      = 'artm_tracking_status_mapping';
    private const CACHE_TAG      = 'artm_tracking';
    private const CACHE_LIFETIME = 3600;

    /** @var array<string,string>|null  raw_status => display_label */
    private ?array $map = null;

    public function __construct(
        private readonly CollectionFactory   $collectionFactory,
        private readonly CacheInterface      $cache,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Resolve a normalized raw status to its display label.
     * Returns null if no mapped (is_mapped=1) row exists.
     *
     * Resolution order:
     *   1. Exact match
     *   2. Keyword / substring match (raw_status key is contained in the incoming status)
     */
    public function resolve(string $normalizedStatus): ?string
    {
        $map = $this->getMap();

        // 1. Exact match
        if (isset($map[$normalizedStatus])) {
            return $map[$normalizedStatus];
        }

        // 2. Keyword match — does the incoming status contain a known key?
        foreach ($map as $key => $label) {
            if (str_contains($normalizedStatus, $key)) {
                return $label;
            }
        }

        return null;
    }

    /**
     * Returns flat map: [ 'raw_status' => 'display_label', ... ]
     * Only rows where is_mapped=1 AND is_active=1 are included.
     */
    public function getMap(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $cached = $this->cache->load(self::CACHE_KEY);
        if ($cached) {
            $this->map = $this->serializer->unserialize($cached);
            return $this->map;
        }

        $this->map = $this->buildMap();
        $this->cache->save(
            $this->serializer->serialize($this->map),
            self::CACHE_KEY,
            [self::CACHE_TAG],
            self::CACHE_LIFETIME
        );

        return $this->map;
    }

    /**
     * Flush in-memory + persistent cache.
     * Call this after any admin save so next request picks up the change.
     */
    public function flushCache(): void
    {
        $this->map = null;
        $this->cache->remove(self::CACHE_KEY);
    }

    private function buildMap(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_active', 1);
        $collection->addFieldToFilter('is_mapped', 1);

        $map = [];
        foreach ($collection as $item) {
            $map[$item->getRawStatus()] = $item->getDisplayLabel();
        }

        return $map;
    }
}
