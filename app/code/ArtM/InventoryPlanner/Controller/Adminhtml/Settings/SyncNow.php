<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Controller\Adminhtml\Settings;

use ArtM\InventoryPlanner\Model\WeeklySalesSync;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Cache\Type\Config as ConfigCacheType;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Runs the same aggregate-and-save logic as the Sync Data page's Calculate button,
 * but resumes from the last order id already synced (not a date) - so newly placed
 * orders are always picked up regardless of when the previous sync ran, and a
 * still-open week's totals get added to rather than overwritten.
 */
class SyncNow extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'ArtM_InventoryPlanner::settings';

    public const SESSION_KEY_LAST_SYNC_STATS = 'artm_inventoryplanner_last_sync_stats';

    private const CONFIG_PATH_STATUSES = 'artm_inventoryplanner/general/statuses';

    private const CONFIG_PATH_LAST_ORDER_ID = 'artm_inventoryplanner/general/last_order_id';

    private const CONFIG_PATH_LAST_SYNCED_AT = 'artm_inventoryplanner/general/last_synced_at';

    private const DEFAULT_STATUSES = ['processing', 'complete'];

    private const IST_TIMEZONE = 'Asia/Kolkata';

    private WeeklySalesSync $weeklySalesSync;

    private ScopeConfigInterface $scopeConfig;

    private WriterInterface $configWriter;

    private TypeListInterface $cacheTypeList;

    private BackendSession $backendSession;

    public function __construct(
        Context $context,
        WeeklySalesSync $weeklySalesSync,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        BackendSession $backendSession
    ) {
        parent::__construct($context);
        $this->weeklySalesSync = $weeklySalesSync;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->backendSession = $backendSession;
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create()->setPath('artm_inventoryplanner/settings/index');

        $lastOrderId = (int) $this->scopeConfig->getValue(self::CONFIG_PATH_LAST_ORDER_ID);
        $result = $this->weeklySalesSync->syncFromOrderId($lastOrderId, $this->getSelectedStatuses());

        if ($result['last_order_id'] === $lastOrderId) {
            $this->messageManager->addSuccessMessage(__('Already up to date.'));

            return $resultRedirect;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone(self::IST_TIMEZONE));
        $this->configWriter->save(self::CONFIG_PATH_LAST_ORDER_ID, (string) $result['last_order_id']);
        $this->configWriter->save(self::CONFIG_PATH_LAST_SYNCED_AT, $now->format('Y-m-d H:i:s'));
        $this->cacheTypeList->cleanType(ConfigCacheType::TYPE_IDENTIFIER);

        $this->backendSession->setData(self::SESSION_KEY_LAST_SYNC_STATS, [
            'last_order_id' => $result['last_order_id'],
        ] + $result['stats']);

        $this->messageManager->addSuccessMessage(__('Sync complete. See details below.'));

        return $resultRedirect;
    }

    /**
     * @return string[]
     */
    private function getSelectedStatuses(): array
    {
        $saved = (string) $this->scopeConfig->getValue(self::CONFIG_PATH_STATUSES);
        if ($saved === '') {
            return self::DEFAULT_STATUSES;
        }

        return array_values(array_filter(array_map('trim', explode(',', $saved))));
    }
}
