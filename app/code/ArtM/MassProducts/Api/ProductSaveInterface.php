<?php
declare(strict_types=1);

namespace ArtM\MassProducts\Api;

interface ProductSaveInterface
{
    /**
     * Persist a partial set of product attribute changes for the given SKU.
     *
     * @param string $sku
     * @param array $attributes
     * @param int|null $storeId
     * @return array
     */
    public function save(string $sku, array $attributes = [], ?int $storeId = null): array;
}