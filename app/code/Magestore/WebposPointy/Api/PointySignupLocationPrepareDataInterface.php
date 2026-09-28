<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Api;

/**
 * Prepare signup data
 */
interface PointySignupLocationPrepareDataInterface
{
    /**
     * Prepare signup data for signup
     *
     * @param \Magestore\Webpos\Model\Location\Location $location
     * @return array
     */
    public function execute(\Magestore\Webpos\Model\Location\Location $location): array;
}
