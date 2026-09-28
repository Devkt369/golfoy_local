<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model;

use Magento\Framework\Model\AbstractModel;
use Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackInterface;
use Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackExtensionInterface;
use Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack as PerformanceTrackResource;

/**
 * Class PerformanceTrack
 *
 * Used for PerformanceTrack model
 */
class PerformanceTrack extends AbstractModel implements PerformanceTrackInterface
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(PerformanceTrackResource::class);
    }

    /**
     * Get log id
     *
     * @return int|null
     */
    public function getLogId(): ?int
    {
        return $this->getData(self::LOG_ID);
    }

    /**
     * Set log id
     *
     * @param int|null $logId
     * @return void
     */
    public function setLogId(?int $logId): void
    {
        $this->setData(self::LOG_ID, $logId);
    }

    /**
     * Get action code
     *
     * @return string|null
     */
    public function getActionCode(): ?string
    {
        return $this->getData(self::ACTION_CODE);
    }

    /**
     * Set action code
     *
     * @param string|null $actionCode
     * @return void
     */
    public function setActionCode(?string $actionCode): void
    {
        $this->setData(self::ACTION_CODE, $actionCode);
    }

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->getData(self::FROM);
    }

    /**
     * Set created at
     *
     * @param string|null $from
     * @return void
     */
    public function setFrom(?string $from): void
    {
        $this->setData(self::FROM, $from);
    }

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getTo(): ?string
    {
        return $this->getData(self::TO);
    }

    /**
     * Set created at
     *
     * @param string|null $to
     * @return void
     */
    public function setTo(?string $to): void
    {
        $this->setData(self::TO, $to);
    }

    /**
     * Get total count
     *
     * @return int|null
     */
    public function getTotalCount(): ?int
    {
        return $this->getData(self::TOTAL_COUNT);
    }

    /**
     * Set product id
     *
     * @param int|null $totalCount
     * @return void
     */
    public function setTotalCount(?int $totalCount): void
    {
        $this->setData(self::TOTAL_COUNT, $totalCount);
    }

    /**
     * Get average
     *
     * @return float|null
     */
    public function getAverage(): ?float
    {
        return $this->getData(self::AVERAGE);
    }

    /**
     * Set average
     *
     * @param float|null $average
     * @return void
     */
    public function setAverage(?float $average): void
    {
        $this->setData(self::AVERAGE, $average);
    }

    /**
     * Get action type
     *
     * @return int|null
     */
    public function getPosId(): ?int
    {
        return $this->getData(self::POS_ID);
    }

    /**
     * Set product sku
     *
     * @param int|null $posId
     * @return void
     */
    public function setPosId(?int $posId): void
    {
        $this->setData(self::POS_ID, $posId);
    }

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackExtensionInterface|null
     */
    public function getExtensionAttributes()
    : ?\Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackExtensionInterface
    {
        return $this->getData(self::EXTENSION_ATTRIBUTES_KEY);
    }

    /**
     * Set an extension attributes object
     *
     * @param \Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackExtensionInterface $extensionAttributes
    ): void {
        $this->setData(self::EXTENSION_ATTRIBUTES_KEY, $extensionAttributes);
    }
}
