<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Cron;

use Exception;
use Magestore\Webpos\Model\Request\ActionLog;
use Magestore\Webpos\Model\ResourceModel\Request\ActionLog\Collection as ActionLogCollection;
use Magestore\Webpos\Model\ResourceModel\Request\ActionLog\CollectionFactory as ActionLogCollectionFactory;

/**
 * Cron to process webpos order
 */
class WebposOrderConverter
{
    /**
     * @var ActionLogCollectionFactory
     */
    protected $actionLogCollectionFactory;

    /**
     * WebposOrderConverter constructor.
     *
     * @param ActionLogCollectionFactory $actionLogCollectionFactory
     */
    public function __construct(
        ActionLogCollectionFactory $actionLogCollectionFactory
    ) {
        $this->actionLogCollectionFactory = $actionLogCollectionFactory;
    }

    /**
     * Process in-completed orders
     *
     * @throws Exception
     */
    public function execute()
    {
        /** @var ActionLogCollection $actionLogCollection */
        $actionLogCollection = $this->actionLogCollectionFactory->create();
        $actionLogCollection->addFieldToFilter(
            'status',
            ['neq' => ActionLog::STATUS_COMPLETED]
        );
        $actionLogCollection->processActionLog();
    }
}
