<?php
namespace ArtM\MassProducts\Model\Config\Backend;

use ArtM\MassProducts\Helper\Cron as CronHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class Cron extends Value
{
    private ScopeConfigInterface $scopeConfig;
    private WriterInterface $configWriter;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $scopeConfig,
        TypeListInterface $cacheTypeList,
        WriterInterface $configWriter,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $scopeConfig, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
    }

    public function afterSave()
    {
        $mode = $this->readFieldValue('mode', 'run_once');
        $startDateTime = $this->readFieldValue('start_datetime', '');
        $interval = $this->readFieldValue('interval', 'minutes');
        $intervalValue = (int)$this->readFieldValue('interval_value', '15');

        $this->validateConfig($mode, $startDateTime, $interval, $intervalValue);

        $nextRunAt = $this->calculateNextRunAt($mode, $startDateTime, $interval, $intervalValue);

        $this->configWriter->save(
            CronHelper::XML_PATH_NEXT_RUN_AT,
            $nextRunAt,
            $this->getScope(),
            (int)$this->getScopeId()
        );

        return parent::afterSave();
    }

    private function readFieldValue(string $field, string $default = ''): string
    {
        $fieldsetValue = $this->getFieldsetDataValue($field);
        if ($fieldsetValue !== null && trim((string)$fieldsetValue) !== '') {
            return trim((string)$fieldsetValue);
        }

        $stored = (string)$this->scopeConfig->getValue(
            CronHelper::XML_PATH_PREFIX . $field,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );

        return trim($stored) !== '' ? trim($stored) : $default;
    }

    private function validateConfig(string $mode, string $startDateTime, string $interval, int $intervalValue): void
    {
        if ($startDateTime === '') {
            throw new LocalizedException(__('Start Date & Time is required.'));
        }

        if ($mode === 'recurring') {
            if ($interval === '') {
                throw new LocalizedException(__('Recurring Interval is required for recurring mode.'));
            }

            if ($interval === 'minutes' && $intervalValue <= 0) {
                throw new LocalizedException(__('Interval Value must be a valid positive number.'));
            }
        }
    }

    private function calculateNextRunAt(string $mode, string $startDateTime, string $interval, int $intervalValue): string
    {
        try {
            $start = new \DateTimeImmutable($startDateTime);
        } catch (\Throwable $e) {
            throw new LocalizedException(__('Invalid Start Date & Time format. Use YYYY-MM-DD HH:MM:SS.'));
        }

        if ($mode !== 'recurring') {
            return $start->format('Y-m-d H:i:s');
        }

        $now = new \DateTimeImmutable('now');
        $next = $start;
        $safety = 0;

        while ($next <= $now && $safety < 100000) {
            if ($interval === 'hourly') {
                $next = $next->modify('+1 hour');
            } elseif ($interval === 'daily') {
                $next = $next->modify('+1 day');
            } else {
                $step = $intervalValue > 0 ? $intervalValue : 1;
                $next = $next->modify('+' . $step . ' minutes');
            }
            $safety++;
        }

        return $next->format('Y-m-d H:i:s');
    }
}
