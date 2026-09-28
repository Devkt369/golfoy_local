<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Api\Request;

/**
 * Pointy inventory constant
 */
interface PointyInventoryInterface
{
    const RECORDS = 'records';
    const VALIDATION_OPTIONS = 'validation_options';
    const DISABLE_CODE_VALIDATION = 'disable_code_validation';

    const CODE = 'code';
    const PRICE = 'price';
    const PURCHASE_COST = 'purchase_cost';
    const QUANTITY_ON_HAND = 'quantity_on_hand';
    const STOCKTAKE = 'stocktake';
    const STOCKTAKE_TIMESTAMP = 'stocktake_timestamp';
    const ITEM_ID = 'item_id';
}
