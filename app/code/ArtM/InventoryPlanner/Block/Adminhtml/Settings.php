<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Block\Adminhtml;

use ArtM\InventoryPlanner\Controller\Adminhtml\Settings\SyncNow;
use ArtM\InventoryPlanner\Model\ColumnGroupsProvider;
use ArtM\InventoryPlanner\Model\DashboardColumnList;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Sales\Model\Order\Config as OrderConfig;

/**
 * Settings page: which order statuses count towards the sync, which MSI sources are
 * tracked (informational only for now - there's no reliable historical link between
 * a sales_order_item and the source that fulfilled it, so this isn't wired into the
 * sync query), and a "Sync Now" button that resumes from the last order id synced.
 */
class Settings extends Template
{
    private const CONFIG_PATH_STATUSES = 'artm_inventoryplanner/general/statuses';

    private const CONFIG_PATH_SOURCES = 'artm_inventoryplanner/general/sources';

    private const CONFIG_PATH_LAST_ORDER_ID = 'artm_inventoryplanner/general/last_order_id';

    private const CONFIG_PATH_LAST_SYNCED_AT = 'artm_inventoryplanner/general/last_synced_at';

    private const DEFAULT_STATUSES = ['processing', 'complete'];

    private OrderConfig $orderConfig;

    private BackendSession $backendSession;

    private SourceRepositoryInterface $sourceRepository;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private ColumnGroupsProvider $columnGroupsProvider;

    public function __construct(
        Context $context,
        OrderConfig $orderConfig,
        BackendSession $backendSession,
        SourceRepositoryInterface $sourceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ColumnGroupsProvider $columnGroupsProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderConfig = $orderConfig;
        $this->backendSession = $backendSession;
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->columnGroupsProvider = $columnGroupsProvider;
    }

    public function getSaveFormActionUrl(): string
    {
        return $this->getUrl('artm_inventoryplanner/settings/save');
    }

    public function getSyncNowFormActionUrl(): string
    {
        return $this->getUrl('artm_inventoryplanner/settings/syncNow');
    }

    /**
     * Every order status registered in Magento, code => label.
     *
     * @return array<string, string>
     */
    public function getAllStatusOptions(): array
    {
        return $this->orderConfig->getStatuses();
    }

    /**
     * Currently saved statuses (default: Processing + Complete, until an admin saves
     * their own selection).
     *
     * @return string[]
     */
    public function getSelectedStatuses(): array
    {
        $saved = (string) $this->_scopeConfig->getValue(self::CONFIG_PATH_STATUSES);
        if ($saved === '') {
            return self::DEFAULT_STATUSES;
        }

        return array_values(array_filter(array_map('trim', explode(',', $saved))));
    }

    public function isStatusSelected(string $statusCode): bool
    {
        return in_array($statusCode, $this->getSelectedStatuses(), true);
    }

    /**
     * Every MSI source, code => name.
     *
     * @return array<string, string>
     */
    public function getAllSourceOptions(): array
    {
        $options = [];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        foreach ($this->sourceRepository->getList($searchCriteria)->getItems() as $source) {
            $options[$source->getSourceCode()] = $source->getName();
        }

        return $options;
    }

    /**
     * @return string[]
     */
    public function getSelectedSources(): array
    {
        $saved = (string) $this->_scopeConfig->getValue(self::CONFIG_PATH_SOURCES);
        if ($saved === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $saved))));
    }

    public function isSourceSelected(string $sourceCode): bool
    {
        return in_array($sourceCode, $this->getSelectedSources(), true);
    }

    public function getSaveColumnsFormActionUrl(): string
    {
        return $this->getUrl('artm_inventoryplanner/settings/saveColumns');
    }

    /**
     * @return array<string, string>
     */
    public function getAllColumnOptions(): array
    {
        return DashboardColumnList::ALL_COLUMNS;
    }

    /**
     * Raw {"groups": [{"name":..., "columns": [...]}], "customColumns": {"key": "label"}}
     * JSON, either what was last saved or empty defaults - the client-side
     * column-grouping UI bootstraps entirely from this rather than duplicating its
     * render logic here. customColumns holds anything the admin added manually
     * (beyond the built-in ALL_COLUMNS list), so those keep their label across reloads.
     */
    public function getColumnGroupsJson(): string
    {
        return $this->columnGroupsProvider->getGroupsJson();
    }

    public function getLastSyncedLabel(): string
    {
        $lastSyncedAt = (string) $this->_scopeConfig->getValue(self::CONFIG_PATH_LAST_SYNCED_AT);
        if ($lastSyncedAt === '') {
            return (string) __('Never');
        }

        return (new \DateTimeImmutable($lastSyncedAt))->format('d-M-Y g:i A') . ' IST';
    }

    public function getLastSyncedOrderId(): int
    {
        return (int) $this->_scopeConfig->getValue(self::CONFIG_PATH_LAST_ORDER_ID);
    }

    /**
     * One-time display of the details of the "Sync Now" run that just completed
     * (stashed in session by the controller since it redirects) - null if this page
     * load isn't right after a sync, or nothing new was found.
     *
     * @return array<string, mixed>|null
     */
    public function getLastSyncStats(): ?array
    {
        $stats = $this->backendSession->getData(SyncNow::SESSION_KEY_LAST_SYNC_STATS, true);

        return is_array($stats) ? $stats : null;
    }
}
