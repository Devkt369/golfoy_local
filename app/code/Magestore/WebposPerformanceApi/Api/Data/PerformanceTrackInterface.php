<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformanceApi\Api\Data;

/**
 * Represents performance track
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface PerformanceTrackInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const LOG_ID = 'log_id';
    const ACTION_CODE = 'action_code';
    const FROM = 'from';
    const TO = 'to';
    const TOTAL_COUNT = 'total_count';
    const AVERAGE = 'average';
    const POS_ID = 'pos_id';
    /**#@-*/

    /**
     * Get log id
     *
     * @return int|null
     */
    public function getLogId(): ?int;

    /**
     * Set log id
     *
     * @param int|null $logId
     * @return void
     */
    public function setLogId(?int $logId): void;

    /**
     * Get action code
     *
     * @return string|null
     */
    public function getActionCode(): ?string;

    /**
     * Set action code
     *
     * @param string|null $actionCode
     * @return void
     */
    public function setActionCode(?string $actionCode): void;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getFrom(): ?string;

    /**
     * Set created at
     *
     * @param string|null $from
     * @return void
     */
    public function setFrom(?string $from): void;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getTo(): ?string;

    /**
     * Set created at
     *
     * @param string|null $to
     * @return void
     */
    public function setTo(?string $to): void;

    /**
     * Get total count
     *
     * @return int|null
     */
    public function getTotalCount(): ?int;

    /**
     * Set product id
     *
     * @param int|null $totalCount
     * @return void
     */
    public function setTotalCount(?int $totalCount): void;
    
    /**
     * Get average
     *
     * @return float|null
     */
    public function getAverage(): ?float;

    /**
     * Set average
     *
     * @param float|null $average
     * @return void
     */
    public function setAverage(?float $average): void;

    /**
     * Get action type
     *
     * @return int|null
     */
    public function getPosId(): ?int;

    /**
     * Set product sku
     *
     * @param int|null $posId
     * @return void
     */
    public function setPosId(?int $posId): void;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackExtensionInterface|null
     */
    public function getExtensionAttributes()
    : ?\Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magestore\WebposPerformanceApi\Api\Data\PerformanceTrackExtensionInterface $extensionAttributes
    ): void;
}
