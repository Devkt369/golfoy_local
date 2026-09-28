<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model\Data;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magestore\LoggerApi\Api\Data\LogMessageInterface;
use Magento\Framework\DataObject;
use Magestore\LoggerApi\Api\ValidateLogFormatInterface;
use Magestore\LoggerApi\Api\ReformatFieldByStructureOrderInterface;

/**
 * Class LogMessage
 *
 * Log Message Data Service
 */
abstract class LogMessage extends DataObject implements LogMessageInterface
{
    const LOGGER_FORMAT = '';

    const INVALID_RECORD = 'invalid_record';

    /**
     * @var ValidateLogFormatInterface
     */
    protected $validateLogFormat;

    /**
     * @var array
     */
    protected $loggers;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ReformatFieldByStructureOrderInterface
     */
    protected $reformatFieldByStructureOrder;

    /**
     * LogMessage constructor.
     *
     * @param ValidateLogFormatInterface $validateLogFormat
     * @param DateTime $dateTime
     * @param StoreManagerInterface $storeManager
     * @param ReformatFieldByStructureOrderInterface $reformatFieldByStructureOrder
     * @param array $loggers
     * @param array $data
     */
    public function __construct(
        ValidateLogFormatInterface $validateLogFormat,
        DateTime $dateTime,
        StoreManagerInterface $storeManager,
        ReformatFieldByStructureOrderInterface $reformatFieldByStructureOrder,
        array $loggers,
        array $data = []
    ) {
        $this->validateLogFormat = $validateLogFormat;
        $this->loggers = $loggers;
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
        $this->reformatFieldByStructureOrder = $reformatFieldByStructureOrder;
        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function prepareData(array $data): array
    {
        if (empty($data[self::LOG_TIME])) {
            $data[self::LOG_TIME] = str_replace(
                '+00:00',
                'Z',
                $this->dateTime->gmtDate(DATE_RFC3339_EXTENDED)
            );
        }
        if (empty($data[self::LOG_TYPE])) {
            $data[self::LOG_TYPE] = static::LOGGER_FORMAT;
        }
        if (empty($data[self::SOURCE])) {
            $data[self::SOURCE] = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        }
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function setData($key, $value = null)
    {
        if ($key === (array)$key && count($key)) {
            $key = $this->prepareData($key);
        }
        return parent::setData($key, $value);
    }

    /**
     * Validate message is satisfy type or not
     *
     * @return bool
     * @throws LocalizedException
     */
    public function validate()
    {
        return $this->validateLogFormat->execute($this->getData(), static::LOGGER_FORMAT);
    }

    /**
     * Write Message To File
     *
     * @return void
     */
    public function writeToFile()
    {
        $loggers = $this->loggers;
        $logProcessor = $loggers[static::LOGGER_FORMAT];
        $logProcessor->info($this->toJson());
    }

    /**
     * Log invalid record
     *
     * @param string $message
     * @return void
     */
    public function logInvalid(string $message)
    {
        $loggers = $this->loggers;
        $logProcessor = $loggers[static::INVALID_RECORD];
        $logProcessor->info($message . ' : ' . $this->toJson());
    }

    /**
     * Reformat order by structure
     *
     * @return $this
     */
    public function reformatByOrder()
    {
        $data = $this->reformatFieldByStructureOrder->execute($this->getData(), static::LOGGER_FORMAT);
        $this->setData($data);
        return $this;
    }
}
