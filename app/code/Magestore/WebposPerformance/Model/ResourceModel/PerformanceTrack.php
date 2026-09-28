<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackInterface;

/**
 * Class PerformanceTrack
 *
 * Used for PerformanceTrack resource model
 */
class PerformanceTrack extends AbstractDb
{
    /**
     * Resource Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('webpos_performance_track', PerformanceTrackInterface::LOG_ID);
    }
}
