<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Api;

/**
 * Signup validate location
 */
interface PointySignupLocationValidationInterface
{
    /**
     * Validate location data
     *
     * @param \Magestore\Webpos\Model\Location\Location $location
     * @return array
     */
    public function execute(\Magestore\Webpos\Model\Location\Location $location): array;
}
