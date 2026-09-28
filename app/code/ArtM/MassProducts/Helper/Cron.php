<?php
namespace ArtM\MassProducts\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Cron extends AbstractHelper
{
    public const XML_PATH_PREFIX = 'artm_massproducts/cron/';

    public const XML_PATH_ENABLE = self::XML_PATH_PREFIX . 'enable';
    public const XML_PATH_MODE = self::XML_PATH_PREFIX . 'mode';
    public const XML_PATH_START_DATETIME = self::XML_PATH_PREFIX . 'start_datetime';
    public const XML_PATH_INTERVAL = self::XML_PATH_PREFIX . 'interval';
    public const XML_PATH_INTERVAL_VALUE = self::XML_PATH_PREFIX . 'interval_value';
    public const XML_PATH_BATCH_SIZE = self::XML_PATH_PREFIX . 'batch_size';
    public const XML_PATH_STOP_AFTER_COMPLETE = self::XML_PATH_PREFIX . 'stop_after_complete';
    public const XML_PATH_NEXT_RUN_AT = self::XML_PATH_PREFIX . 'next_run_at';

    public function getConfig(string $path, ?string $scopeType = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT, ?int $scopeCode = null)
    {
        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }

    public function isEnabled(): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_ENABLE, \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    public function getBatchSize(): int
    {
        $size = (int)$this->getConfig(self::XML_PATH_BATCH_SIZE, \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        return $size > 0 ? $size : 20;
    }

    public function getMode(): string
    {
        $mode = (string)$this->getConfig(self::XML_PATH_MODE, \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        return $mode !== '' ? $mode : 'run_once';
    }

    public function getStartTime(): string
    {
        return trim((string)$this->getConfig(self::XML_PATH_START_DATETIME, \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT));
    }

    public function getInterval(): string
    {
        return trim((string)$this->getConfig(self::XML_PATH_INTERVAL, \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT));
    }

    public function getIntervalValue(): int
    {
        return (int)$this->getConfig(self::XML_PATH_INTERVAL_VALUE, \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    public function getNextRunAt(): string
    {
        return trim((string)$this->getConfig(self::XML_PATH_NEXT_RUN_AT, \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT));
    }
}
