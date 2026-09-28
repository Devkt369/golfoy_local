<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Core\Api;

/**
 * Get stock ids by source codes
 */
interface GetStockIdsBySourceCodesInterface
{
    /**
     * Get Stock Ids By Source Codes Interface
     *
     * @param array $sourceCodes
     * @return array
     */
    public function execute(array $sourceCodes);
}
