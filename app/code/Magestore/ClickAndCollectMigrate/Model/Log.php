<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Model;

use Magento\Framework\Model\AbstractModel;
use Magestore\ClickAndCollectMigrate\Api\Data\LogInterface;
use Magestore\ClickAndCollectMigrate\Model\ResourceModel\Log as ResourceModel;

/**
 * Migrate log model
 */
class Log extends AbstractModel implements LogInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Get log id
     *
     * @return int
     */
    public function getLogId(): int
    {
        return (int) $this->getData(self::LOG_ID);
    }

    /**
     * Set log id
     *
     * @param int $logId
     * @return $this
     */
    public function setLogId(int $logId)
    {
        $this->setData(self::LOG_ID, $logId);
        return $this;
    }

    /**
     * Get logged at
     *
     * @return string|null
     */
    public function getLoggedAt()
    {
        return $this->getData(self::LOGGED_AT);
    }

    /**
     * Get message
     *
     * @return string|null
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message)
    {
        $this->setData(self::MESSAGE, $message);
        return $this;
    }
}
