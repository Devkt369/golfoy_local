<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\Webpos\Api\DataProvider;

use Magento\Framework\Exception\LocalizedException;

interface GetCurrentStaffIdBySessionInterface
{
    const STAFF_ID = 'staff_id';

    /**
     * Get staff by session
     *
     * @param string $session
     * @return int|null
     * @throws LocalizedException
     */
    public function execute($session);
}
