<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Api;

/**
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface LogRepositoryInterface
{
    /**
     * Save Log.
     *
     * @param \Magestore\ClickAndCollectMigrate\Api\Data\LogInterface $log
     * @return \Magestore\ClickAndCollectMigrate\Api\Data\LogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Magestore\ClickAndCollectMigrate\Api\Data\LogInterface $log);

    /**
     * Retrieve Log.
     *
     * @param int $logId
     * @return \Magestore\ClickAndCollectMigrate\Api\Data\LogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById(int $logId);

    /**
     * Retrieve log matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magestore\ClickAndCollectMigrate\Api\Data\LogSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Get log collection.
     *
     * @return \Magestore\ClickAndCollectMigrate\Api\Data\LogInterface[]
     */
    public function getAllLog();
}
