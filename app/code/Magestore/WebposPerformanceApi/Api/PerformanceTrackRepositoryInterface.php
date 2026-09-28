<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPerformanceApi\Api;

interface PerformanceTrackRepositoryInterface
{
    /**
     * Save webpos performance.
     *
     * @param \Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackInterface[] $performanceTrack
     * @return boolean
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(array $performanceTrack);
}
