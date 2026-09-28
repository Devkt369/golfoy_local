<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\Webpos\Api\DataProvider\Session;

use Magento\Framework\Exception\LocalizedException;
use Magestore\Webpos\Api\Data\Staff\SessionInterface;

interface GetSessionByIdInterface
{
    const SESSION_DATA = 'session_data';

    /**
     * Execute get by session id
     *
     * @param string|null $sessionId
     * @return SessionInterface|null
     * @throws LocalizedException
     */
    public function execute($sessionId);
}
