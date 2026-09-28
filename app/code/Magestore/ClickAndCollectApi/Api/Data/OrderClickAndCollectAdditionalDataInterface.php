<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Data;

/**
 * Interface for additional data about click and collect
 *
 * @api
 */
interface OrderClickAndCollectAdditionalDataInterface
{
    /**
     * Constants for additional data.
     */
    const LOCATION_PICKUP_ID = 'location_pickup_id';
    const PICKUP_DATE_TIME = 'pickup_date_time';
    const PICKUP_DATE_ONLY = 'pickup_date_only';
    const PICKUP_STATUS = 'pickup_status';
    /**#@-*/
}
