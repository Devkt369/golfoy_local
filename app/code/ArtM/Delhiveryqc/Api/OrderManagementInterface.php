<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\Delhiveryqc\Api;

interface OrderManagementInterface
{

    /**
     * POST for Order api
     * @param string $param
     * @return string
     */
    public function getorderdetailsforqc(array $packageIds);
}

