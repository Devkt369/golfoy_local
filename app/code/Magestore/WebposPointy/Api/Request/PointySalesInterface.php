<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Api\Request;

/**
 * Pointy sales constant
 */
interface PointySalesInterface
{
    const RECORDS = 'records';
    const CODE = 'code';
    const UNIT_PRICE = 'unit_price';
    const QUANTITY = 'quantity';
    const TIMESTAMP = 'timestamp';
    const ITEM_ID = 'item_id';

    const VALIDATION_OPTIONS = 'validation_options';
    const DISABLE_CODE_VALIDATION = 'disable_code_validation';
}
