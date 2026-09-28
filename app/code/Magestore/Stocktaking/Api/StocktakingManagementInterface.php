<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types = 1);

namespace Magestore\Stocktaking\Api;

use Magento\Framework\DB\Select;
use Magestore\Stocktaking\Api\Data\StocktakingInterface;
use Magestore\Stocktaking\Model\ResourceModel\Stocktaking\StocktakingItem\Collection
    as StocktakingItemCollection;

/**
 * StocktakingManagementInterface for get some external data
 */
interface StocktakingManagementInterface
{
    /**
     * GetSelectBarcodeProductListJson
     *
     * @param array $productIds
     * @return mixed
     */
    public function getSelectBarcodeProductListJson(array $productIds = []);

    /**
     * Get product json by barcode string
     *
     * @param string $barcode
     * @return string|bool
     */
    public function getProductJsonByBarcode(string $barcode);

    /**
     * Add Uncounted Products To Stocktaking
     *
     * @param StocktakingInterface $stocktaking
     */
    public function addUncountedProductsToStocktaking(StocktakingInterface $stocktaking);

    /**
     * Get Stocktaking Items To Complete Select
     *
     * @param StocktakingInterface $stocktaking
     * @return Select
     * @throws \Zend_Db_Select_Exception
     */
    public function getStocktakingItemsToCompleteSelect(StocktakingInterface $stocktaking);

    /**
     * Get Stocktaking Items To Cancel Select
     *
     * @param StocktakingInterface $stocktaking
     * @return Select
     * @throws \Zend_Db_Select_Exception
     */
    public function getStocktakingItemsToCancelSelect(StocktakingInterface $stocktaking);

    /**
     * Create Adjust Stock
     *
     * @param StocktakingInterface $stocktaking
     * @return bool
     */
    public function createAdjustStock(StocktakingInterface $stocktaking);

    /**
     * Process Change Quantity
     *
     * @param StocktakingInterface $stocktaking
     * @param StocktakingItemCollection $stocktakingItems
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function processChangeQuantity(
        StocktakingInterface $stocktaking,
        StocktakingItemCollection $stocktakingItems
    );
}
