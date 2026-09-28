<?php

declare(strict_types=1);

namespace ArtM\MassProducts\Api;

use ArtM\MassProducts\Api\Data\ProductImageResponseInterface;

/**
 * Interface ProductImageManagementInterface
 *
 * Accepts a SKU and a list of image filenames (relative to the configured
 * import directory). The Magento root path is resolved automatically.
 *
 * - First image  → base_image, small_image, thumbnail
 * - Other images → gallery only (no roles)
 */
interface ProductImageManagementInterface
{
    /**
     * Set one or more images for the given product SKU.
     *
     * Pass only the filename(s) — the import directory path is resolved
     * automatically from the Magento installation root.
     *
     * @param  string   $sku          Product SKU.
     * @param  string[] $imageNames   Filenames inside pub/media/import/product_images/
     *                                e.g. ["1.jpg", "2.jpg", "3.png"]
     *
     * @return \ArtM\MassProducts\Api\Data\ProductImageResponseInterface
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setProductImage(string $sku, array $imageNames): ProductImageResponseInterface;
}
