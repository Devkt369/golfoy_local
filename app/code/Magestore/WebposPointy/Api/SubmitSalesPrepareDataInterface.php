<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Api;

/**
 * Prepare data for submit sales
 */
interface SubmitSalesPrepareDataInterface
{
    /**
     * Prepare data for submit sales
     *
     * @param \Magestore\Webpos\Model\Location\Location $location
     * @return array
     */
    public function execute(\Magestore\Webpos\Model\Location\Location $location): array;
}
