<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\Appadmin\Api\DataProvider;

use Magento\Framework\Exception\LocalizedException;
use Magestore\Appadmin\Api\Data\Staff\StaffInterface;

interface GetStaffByIdInterface
{
    const STAFF_DATA = 'staff_data';

    /**
     * Get staff by id
     *
     * @param int $id
     * @return StaffInterface|null
     * @throws LocalizedException
     */
    public function execute(int $id);
}
