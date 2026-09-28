<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\LogHandle;

use Magento\Framework\Event\ManagerInterface;
use Magestore\Webpos\Api\LogHandle\ProcessLogExceptionInterface;
use Magestore\Webpos\Helper\Data as HelperData;
use Psr\Log\LoggerInterface;

/**
 * Class ProcessLogException
 *
 * Process log exception handle
 */
class ProcessLogException implements ProcessLogExceptionInterface
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * ProcessLogException constructor.
     *
     * @param HelperData $helperData
     * @param LoggerInterface $logger
     * @param ManagerInterface $manager
     */
    public function __construct(
        HelperData $helperData,
        LoggerInterface $logger,
        ManagerInterface $manager
    ) {
        $this->helperData = $helperData;
        $this->logger = $logger;
        $this->eventManager = $manager;
    }

    /**
     * Handle log exception
     *
     * @param string $flow
     * @param string $businessFlow
     * @param string $message
     * @param string $level
     * @param \Exception $exception
     * @param array $otherFields
     * @return void
     */
    public function execute(
        string $flow,
        string $businessFlow,
        string $message,
        string $level,
        \Exception $exception,
        array $otherFields = []
    ) {
        if ($this->helperData->isEnabledLog('exception', $flow)) {
            $messageLog = [
                'format' => 'exception',
                'data' => [
                    'app' => 'pos_server',
                    'log_flow' => $flow,
                    'business_flow' => $businessFlow,
                    'log_message' => $message,
                    'exception_level' => $level,
                    'exception_message' => $exception->getMessage(),
                    'stack_trace' => $exception->getTraceAsString(),
                    'exception_code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine()
                ]
            ];
            if (!empty($otherFields)) {
                $messageLog = array_merge($messageLog, $otherFields);
            }
            $this->eventManager->dispatch(
                'magestore_log',
                $messageLog
            );
        } else {
            /* Should log in magento log */
            $this->logger->error($exception->getMessage());
        }
    }
}
