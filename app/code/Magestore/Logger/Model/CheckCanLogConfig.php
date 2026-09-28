<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model;

use Magestore\LoggerApi\Api\CheckCanLogConfigInterface;
use Magestore\LoggerApi\Api\Data\LogMessageInterface;
use Magestore\LoggerApi\Api\IsEnabledLogFlowInterface;
use Magestore\LoggerApi\Api\IsEnabledLogTypeInterface;

/**
 * Service for check can log config
 *
 * @api
 */
class CheckCanLogConfig implements CheckCanLogConfigInterface
{
    /**
     * @var IsEnabledLogFlowInterface
     */
    protected $isEnabledLogFlow;

    /**
     * @var IsEnabledLogTypeInterface
     */
    protected $isEnabledLogType;

    /**
     * CheckCanLogConfig constructor.
     *
     * @param IsEnabledLogFlowInterface $isEnabledLogFlow
     * @param IsEnabledLogTypeInterface $isEnabledLogType
     */
    public function __construct(
        IsEnabledLogFlowInterface $isEnabledLogFlow,
        IsEnabledLogTypeInterface $isEnabledLogType
    ) {
        $this->isEnabledLogType = $isEnabledLogType;
        $this->isEnabledLogFlow = $isEnabledLogFlow;
    }

    /**
     * Execute service
     *
     * @param array $message
     * @return int
     */
    public function execute(array $message): int
    {
        $type = '';
        $logFlow = '';
        if (isset($message[LogMessageInterface::LOG_TYPE])) {
            $type = $message[LogMessageInterface::LOG_TYPE];
        }
        if (isset($message[LogMessageInterface::LOG_FLOW])) {
            $logFlow = $message[LogMessageInterface::LOG_FLOW];
        }

        return (int) $this->isEnabledLogFlow->execute($logFlow)
            && (int) $this->isEnabledLogType->execute($type) ? 1 : 0;
    }
}
