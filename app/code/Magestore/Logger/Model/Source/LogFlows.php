<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model\Source;

/**
 * Class LogFlows
 *
 * Declare the log flows types
 */
class LogFlows
{
    /**
     * @var array
     */
    protected $flows;

    /**
     * LogFlows constructor.
     *
     * @param array $flows
     */
    public function __construct(
        array $flows = []
    ) {
        $this->flows = $flows;
    }

    /**
     * GEt all log flows for loggers
     *
     * @return array
     */
    public function getAllLogFlows()
    {
        return $this->flows;
    }
}
