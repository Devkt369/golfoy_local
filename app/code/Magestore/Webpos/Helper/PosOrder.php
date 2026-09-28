<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Helper;

/**
 * Helper webpos order
 */
class PosOrder extends Data
{
    const CONFIG_MAXIMUM_NUMBER_OF_TRIALS = "webpos/order_converter/maximum_number_of_trials";
    const CONFIG_NUMBER_OF_THREAD_WORKER = "webpos/order_converter/number_of_thread_worker";

    /**
     * Get Maximum Number Of Trials
     *
     * @return int
     */
    public function getMaximumNumberOfTrials()
    {
        return (int) $this->getStoreConfig(self::CONFIG_MAXIMUM_NUMBER_OF_TRIALS);
    }

    /**
     * Get Number Of Thread Worker
     *
     * @return int
     */
    public function getNumberOfThreadWorker()
    {
        return (int) $this->getStoreConfig(self::CONFIG_NUMBER_OF_THREAD_WORKER);
    }
}
