<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Console\Command;

/**
 * Abstract class for Enable and Disable commands to consolidate common logic
 */
abstract class AbstractLogTypeChangeStatusCommand extends AbstractLogStatusCommand
{
    /**
     * Input argument types
     */
    const INPUT_KEY_TYPES = 'types';

    /**
     * Get available log types
     *
     * @return array
     */
    public function getAvailableType()
    {
        return array_keys($this->config->getAllType());
    }
}
