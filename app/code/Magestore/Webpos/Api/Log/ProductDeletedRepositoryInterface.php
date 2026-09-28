<?php
/**
 * Copyright © 2021 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Api\Log;

/**
 * Repository deleted product
 */
interface ProductDeletedRepositoryInterface
{
    /**
     * Insert By Product Id
     *
     * @param int $productId
     * @return void
     */
    public function insertByProductId($productId);

    /**
     * Delete By Product Id
     *
     * @param int $productId
     * @return void
     */
    public function deleteByProductId($productId);

    /**
     * Delete By Product
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteByProduct($product);

    /**
     * Insert By Product Id
     *
     * @param int $productId
     * @param array $stockIds
     * @return void
     */
    public function insertByProductIdAndStock($productId, $stockIds = []);

    /**
     * Delete By Product Id
     *
     * @param int $productId
     * @param array $stockIds
     * @return void
     */
    public function deleteByProductIdAndStock($productId, $stockIds = []);
}
