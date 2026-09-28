<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Cron;

use Magestore\Webpos\Model\ResourceModel\Checkout\PosOrder;

/**
 * Class WebposOrderDeleteTableBackup
 */
class DeleteWebposOrderBackupTable
{
    /**
     * @var PosOrder
     */
    protected $posOrderResource;

    /**
     * WebposOrderConverter constructor.
     *
     * @param PosOrder $posOrderResource
     */
    public function __construct(
        PosOrder $posOrderResource
    ) {
        $this->posOrderResource = $posOrderResource;
    }

    /**
     * Process delete orders and drop table temp
     */
    public function execute()
    {
        $this->posOrderResource->deleteWebposBackupTable();
    }
}
