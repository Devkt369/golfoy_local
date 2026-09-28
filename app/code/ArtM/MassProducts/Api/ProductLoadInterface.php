<?php
declare(strict_types=1);

namespace ArtM\MassProducts\Api;

interface ProductLoadInterface
{
    /**
     * Load product payload for admin tools.
     *
     * @param string $identifier SKU or entity ID value
     * @param string $type Input type, supported: sku, id
     * @param int|null $storeId
     * @return array
     */
    public function load(string $identifier, string $type = 'sku', ?int $storeId = null): array;
}
