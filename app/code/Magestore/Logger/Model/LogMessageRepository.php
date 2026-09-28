<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model;

use Magento\Framework\Exception\LocalizedException;
use Magestore\LoggerApi\Api\LogMessageRepositoryInterface;
use Magestore\LoggerApi\Api\Data\LogMessageInterface;
use Magestore\LoggerApi\Api\CheckCanLogConfigInterface;
use Magestore\Logger\Model\Data\ExceptionLogMessage;
use Psr\Log\LoggerInterface;

/**
 * Service for log message repository
 *
 * @api
 */
class LogMessageRepository implements LogMessageRepositoryInterface
{
    /**
     * @var CheckCanLogConfigInterface
     */
    protected $checkCanLogConfig;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * LogMessageRepository constructor.
     *
     * @param CheckCanLogConfigInterface $checkCanLogConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        CheckCanLogConfigInterface $checkCanLogConfig,
        LoggerInterface $logger
    ) {
        $this->checkCanLogConfig = $checkCanLogConfig;
        $this->logger = $logger;
    }

    /**
     * Save log to file
     *
     * @param LogMessageInterface $logMessage
     */
    public function save(LogMessageInterface $logMessage)
    {
        $messageLog = $logMessage->getData();
        if ($this->checkCanLogConfig->execute($messageLog)) {
            try {
                if ($logMessage->validate()) {
                    $logMessage->writeToFile();
                }
            } catch (LocalizedException $exception) {
                $logMessage->logInvalid($exception->getMessage());
            }
        } elseif ($logMessage->getData(LogMessageInterface::LOG_TYPE) === ExceptionLogMessage::LOGGER_FORMAT) {
            //Log is exception, disable log should save to magento log default
            if (isset($messageLog[ExceptionLogMessage::EXCEPTION_MESSAGE])) {
                $this->logger->error($messageLog[ExceptionLogMessage::EXCEPTION_MESSAGE]);
            }
        }
    }
}
