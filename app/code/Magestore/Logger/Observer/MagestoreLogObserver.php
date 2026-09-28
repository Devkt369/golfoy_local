<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magestore\Logger\Model\Data\DataChangeLogMessage;
use Magestore\Logger\Model\Data\ExceptionLogMessage;
use Magestore\LoggerApi\Api\Data\DataChangeLogMessageInterface;
use Magestore\Logger\Model\Data\ProcessLogMessage;
use Magestore\Logger\Model\Data\IntegrationLogMessage;
use Magestore\LoggerApi\Api\Data\ExceptionLogMessageInterface;
use Magestore\LoggerApi\Api\Data\ProcessLogMessageInterface;
use Magestore\LoggerApi\Api\Data\IntegrationLogMessageInterface;
use Magestore\LoggerApi\Api\LogMessageRepositoryInterface;
use Magestore\LoggerApi\Api\ReformatFieldByStructureOrderInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Class MagestoreLogObserver
 *
 * Process events and record to database
 */
class MagestoreLogObserver implements ObserverInterface
{
    const FORMAT = 'format';
    const DATA = 'data';

    /**
     * @var LogMessageRepositoryInterface
     */
    protected $logMessageRepository;

    /**
     * @var ReformatFieldByStructureOrderInterface
     */
    protected $reformatFieldByStructureOrder;

    /**
     * MagestoreLogObserver constructor.
     *
     * @param LogMessageRepositoryInterface $logMessageRepository
     * @param ReformatFieldByStructureOrderInterface $reformatFieldByStructureOrder
     */
    public function __construct(
        LogMessageRepositoryInterface $logMessageRepository,
        ReformatFieldByStructureOrderInterface $reformatFieldByStructureOrder
    ) {
        $this->reformatFieldByStructureOrder = $reformatFieldByStructureOrder;
        $this->logMessageRepository = $logMessageRepository;
    }

    /**
     * Observe created
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $objectManager = ObjectManager::getInstance();
        $logFormat = $observer->getEvent()->getData(self::FORMAT);
        $logData = $observer->getEvent()->getData(self::DATA);
        $objectClassArray = $this->processorHandle();
        $logModel = $objectManager->create($objectClassArray[$logFormat]);
        $logModel->setData($logData);
        $logModel->reformatByOrder();
        $this->logMessageRepository->save($logModel);
    }

    /**
     * Get processor handle
     *
     * @return array
     */
    public function processorHandle()
    {
        return [
            ExceptionLogMessage::LOGGER_FORMAT => ExceptionLogMessageInterface::class,
            DataChangeLogMessage::LOGGER_FORMAT => DataChangeLogMessageInterface::class,
            ProcessLogMessage::LOGGER_FORMAT => ProcessLogMessageInterface::class,
            IntegrationLogMessage::LOGGER_FORMAT => IntegrationLogMessageInterface::class,
        ];
    }
}
