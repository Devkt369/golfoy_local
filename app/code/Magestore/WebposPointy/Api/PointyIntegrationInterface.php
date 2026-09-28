<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Api;

/**
 * Pointy constant
 */
interface PointyIntegrationInterface
{
    const VALUE_ENABLED = 1;
    const VALUE_DISABLED = 0;

    const POINTY_ENABLE = 'pointy_enable';
    const POINTY_RETAILER_AUTH_KEY = 'pointy_retailer_auth_key';
    const POINTY_LAST_SUBMIT_INVENTORY = 'pointy_last_submit_inventory';
    const POINTY_LAST_SUBMIT_SALES = 'pointy_last_submit_sales';
    const POINTY_SHOP_ID = 'pointy_shop_id';
}
