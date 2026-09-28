<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Block\Adminhtml;

use ArtM\InventoryPlanner\Model\WeeklySalesSync;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/**
 * Renders the "Calculate" form + weekly qty-sold-per-SKU report. Calculating also
 * persists the same rows via WeeklySalesSync, so Settings > Sync Now has something
 * to resume from. There is no Model/Helper layer beyond that one shared sync class.
 */
class Sync extends Template
{
    /**
     * Config path where Settings saves the selected order statuses (comma-separated).
     */
    private const CONFIG_PATH_STATUSES = 'artm_inventoryplanner/general/statuses';

    private const DEFAULT_STATUSES = ['processing', 'complete'];

    private WeeklySalesSync $weeklySalesSync;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $reportRows = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $syncStats = null;

    public function __construct(Context $context, WeeklySalesSync $weeklySalesSync, array $data = [])
    {
        parent::__construct($context, $data);
        $this->weeklySalesSync = $weeklySalesSync;
    }

    public function getFormActionUrl(): string
    {
        return $this->getUrl('artm_inventoryplanner/sync/index');
    }

    /**
     * Raw value to redisplay in the date picker input - empty if none was submitted.
     */
    public function getSelectedStartDateValue(): string
    {
        return (string) $this->getRequest()->getParam('start_date', '');
    }

    /**
     * Parses the submitted start date, or null if none was submitted (or it's invalid).
     */
    public function getSelectedStartDate(): ?\DateTimeImmutable
    {
        $value = $this->getSelectedStartDateValue();
        if ($value === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date instanceof \DateTimeImmutable ? $date->setTime(0, 0, 0) : null;
    }

    /**
     * Main entry point used by the template - one row per SKU per week.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getReportRows(): array
    {
        $this->runSyncOnce();

        return $this->reportRows;
    }

    /**
     * Order/item/SKU stats for the range just calculated - null if no start date was
     * submitted yet.
     *
     * @return array<string, mixed>|null
     */
    public function getSyncStats(): ?array
    {
        $this->runSyncOnce();

        return $this->syncStats;
    }

    private function runSyncOnce(): void
    {
        if ($this->reportRows !== null) {
            return;
        }

        $startDate = $this->getSelectedStartDate();
        if ($startDate === null) {
            $this->reportRows = [];
            $this->syncStats = null;

            return;
        }

        $today = new \DateTimeImmutable('today', new \DateTimeZone('Asia/Kolkata'));
        $result = $this->weeklySalesSync->syncRange($startDate, $today, $this->getSelectedStatuses());

        $this->reportRows = array_map(static function (array $row): array {
            $weekStart = new \DateTimeImmutable($row['week_start']);

            return [
                'product_id' => $row['product_id'],
                'sku' => $row['sku'],
                'week_label' => sprintf('Week %s, %s', $weekStart->format('W'), $weekStart->format('Y')),
                'from_date' => $weekStart->format('d-M-Y'),
                'to_date' => (new \DateTimeImmutable($row['week_end']))->format('d-M-Y'),
                'qty_sold' => $row['qty_sold'],
            ];
        }, $result['rows']);

        $this->syncStats = $result['stats'];
    }

    /**
     * Order statuses to include, as saved on the Settings page (default:
     * Processing + Complete until an admin explicitly changes it).
     *
     * @return string[]
     */
    private function getSelectedStatuses(): array
    {
        $saved = (string) $this->_scopeConfig->getValue(self::CONFIG_PATH_STATUSES);
        if ($saved === '') {
            return self::DEFAULT_STATUSES;
        }

        return array_values(array_filter(array_map('trim', explode(',', $saved))));
    }
}
