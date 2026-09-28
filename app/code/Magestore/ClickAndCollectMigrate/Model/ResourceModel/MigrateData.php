<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Model\ResourceModel;

use Magento\Config\Model\Config\Factory;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Validation\ValidationException;
use Magestore\ClickAndCollectMigrate\Api\Migrate\MigrateLocationServiceInterface;
use Magestore\ClickAndCollectMigrate\Api\Migrate\MigrateOrderServiceInterface;
use Magestore\ClickAndCollectMigrate\Api\Migrate\MigrateSpecialDayServiceInterface;
use Magestore\ClickAndCollectMigrate\Model\ResourceModel\LocationStoreMap as LocationStoreMapResource;
use Magestore\Storepickup\Setup\InstallSchema as StorepickupInstallSchema;
use Magestore\ClickAndCollect\Model\ResourceModel\Schedule as ScheduleResourceModel;
use Magestore\ClickAndCollect\Model\ResourceModel\SpecialDay as SpecialDayResourceModel;
use Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink as LocationSpecialDayLinkResourceModel;

/**
 * Migrate data from Store Pickup to Click And Collect
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MigrateData
{
    const MIGRATE_CONFIG_DATA_VALUE = [
        'storepickup/searching/default_radius' => [
            'new_key' => 'click_and_collect/general/search_radius',
            'value' => []
        ],
        'storepickup/general/display_toplink' => [
            'new_key' => 'click_and_collect/general/show_store_list_on_frontend',
            'value' => []
        ],
        'storepickup/service/google_api_key' => [
            'new_key' => 'webpos/general/google_api_key',
            'value' => []
        ],
    ];
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $connection;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Factory
     */
    protected $configFactory;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var LocationStoreMapResource
     */
    protected $locationStoreMapResource;

    /**
     * @var MigrateLocationServiceInterface
     */
    protected $migrateLocationService;

    /**
     * @var MigrateSpecialDayServiceInterface
     */
    protected $migrateSpecialDayService;

    /**
     * @var MigrateOrderServiceInterface
     */
    protected $migrateOrderService;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * MigrateData constructor.
     * @param ResourceConnection $resource
     * @param CollectionFactory $collectionFactory
     * @param Factory $configFactory
     * @param LocationStoreMap $locationStoreMapResource
     * @param MigrateLocationServiceInterface $migrateLocationService
     * @param MigrateSpecialDayServiceInterface $migrateSpecialDayService
     * @param MigrateOrderServiceInterface $migrateOrderService
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        ResourceConnection $resource,
        CollectionFactory $collectionFactory,
        Factory $configFactory,
        LocationStoreMapResource $locationStoreMapResource,
        MigrateLocationServiceInterface $migrateLocationService,
        MigrateSpecialDayServiceInterface $migrateSpecialDayService,
        MigrateOrderServiceInterface $migrateOrderService,
        ModuleListInterface $moduleList
    ) {
        $this->resource = $resource;
        $this->connection = $this->resource->getConnection();
        $this->collectionFactory = $collectionFactory;
        $this->configFactory = $configFactory;
        $this->locationStoreMapResource = $locationStoreMapResource;
        $this->migrateLocationService = $migrateLocationService;
        $this->migrateSpecialDayService = $migrateSpecialDayService;
        $this->migrateOrderService = $migrateOrderService;
        $this->moduleList = $moduleList;
    }

    /**
     * Execute Migrate Data
     *
     * @param bool $manual
     * @param bool $ignoreUnmappedStore
     * @return string
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(bool $manual = false, bool $ignoreUnmappedStore = false)
    {
        if (!$this->connection->isTableExists($this->resource->getTableName(StorepickupInstallSchema::SCHEMA_STORE))
            || !$this->moduleList->has('Magestore_Storepickup')
        ) {
            throw new LocalizedException(__('Store pickup is not installed'));
        }
        if (!$this->moduleList->has('Magestore_ClickAndCollectApi')
            || !$this->connection->isTableExists(
                $this->resource->getTableName(ScheduleResourceModel::TABLE_NAME_SCHEDULE)
            )
        ) {
            throw new LocalizedException(__('Please install Click And Collect before run this command'));
        }

        if (!$manual) {
            $this->locationStoreMapResource->createLocationAndStoreMapData();
        }

        if (!$this->connection->isTableExists($this->resource->getTableName(LocationStoreMapResource::TABLE_NAME))
            || $this->locationStoreMapResource->getTotalItems() === 0
        ) {
            throw new LocalizedException(
                __("There is no mapping data between POS's location and Store Pickup's store")
            );
        }

        if (!$ignoreUnmappedStore && $this->locationStoreMapResource->hasUnmappedStores()) {
            throw new LocalizedException(
                __("There are some stores that are not mapped with any stock."
                    . " Please check and update data or add option '-i' to command to ignore those stores!")
            );
        }

        $tables = [];

        // schedule
        $tables[] = $this->migrateSchedule();
        // Location
        $tables[] = $this->migrateLocation();
        // Special Day
        $tables[] = $this->migrateSpecialDay();
        // Order
        $tables[] = $this->migrateOrder();

        // Migrate Configuration
        $this->migrateConfiguration();

        return 'Migrate Configuration and Tables: ' . join(', ', $tables);
    }

    /**
     * Migrate Location
     *
     * @return string
     * @throws CouldNotSaveException
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function migrateLocation(): string
    {
        $this->migrateLocationService->migrateLocation();
        return $this->resource->getTableName('webpos_location');
    }

    /**
     * Migrate Special Day
     *
     * @return string
     * @throws CouldNotSaveException
     * @throws ValidationException
     */
    public function migrateSpecialDay(): string
    {
        $table = $this->resource->getTableName(SpecialDayResourceModel::TABLE_NAME_SPECIAL_DAY);
        $linkTable = $this->resource->getTableName(
            LocationSpecialDayLinkResourceModel::TABLE_NAME_LOCATION_SPECIAL_DAY
        );

        // Remove Old Data
        $this->connection->delete($table);
        $this->connection->delete($linkTable);

        // Migrate data
        $this->migrateSpecialDayService->migrate();

        return $table;
    }

    /**
     * Migrate sales_order
     *
     * @return string
     */
    protected function migrateOrder()
    {
        $table = $this->resource->getTableName('sales_order');

        // Migrate data
        $this->migrateOrderService->migrate();

        return $table;
    }

    /**
     * Migrate Configuration
     */
    protected function migrateConfiguration()
    {
        // Migrate Configuration
        foreach (self::MIGRATE_CONFIG_DATA_VALUE as $oldConfigKey => $newData) {
            $newKey = $newData['new_key'];
            $value = $newData['value'];
            $collection = $this->collectionFactory->create()->addFieldToFilter('path', $oldConfigKey);
            foreach ($collection as $configModel) {
                $searchNewKey = $this->collectionFactory->create()->addFieldToFilter('path', $newKey)
                    ->addFieldToFilter('scope', $configModel->getData('scope'))
                    ->addFieldToFilter('scope_id', $configModel->getData('scope_id'))
                    ->getFirstItem();
                $isNewKeyHasValue = false;
                if ($searchNewKey && $searchNewKey->getId()) {
                    $isNewKeyHasValue = true;
                }
                if (!$isNewKeyHasValue) {
                    $configModel->setId(null);
                    $configModel->setData('path', $newKey);
                    $oldValue = $configModel->getData('value');
                    if (isset($value[$oldValue])) {
                        $configModel->setData('value', $value[$oldValue]);
                    }
                    try {
                        $configModel->save();
                    } catch (\Exception $exception) {
                        return $this;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Migrate schedule table
     *
     * @return string
     */
    protected function migrateSchedule()
    {
        $table = $this->resource->getTableName(ScheduleResourceModel::TABLE_NAME_SCHEDULE);
        $backupTable = $this->resource->getTableName(StorepickupInstallSchema::SCHEMA_SCHEDULE);
        $columns = [
            'schedule_id' => 'schedule_id',
            'schedule_name' => 'schedule_name',
            'monday_status' => new \Zend_Db_Expr("IF(monday_status = 1, 1, 0)"),
            'tuesday_status' => new \Zend_Db_Expr("IF(tuesday_status = 1, 1, 0)"),
            'wednesday_status' => new \Zend_Db_Expr("IF(wednesday_status = 1, 1, 0)"),
            'thursday_status' => new \Zend_Db_Expr("IF(thursday_status = 1, 1, 0)"),
            'friday_status' => new \Zend_Db_Expr("IF(friday_status = 1, 1, 0)"),
            'saturday_status' => new \Zend_Db_Expr("IF(saturday_status = 1, 1, 0)"),
            'sunday_status' => new \Zend_Db_Expr("IF(sunday_status = 1, 1, 0)"),
            'monday_open' => 'monday_open',
            'tuesday_open' => 'tuesday_open',
            'wednesday_open' => 'wednesday_open',
            'thursday_open' => 'thursday_open',
            'friday_open' => 'friday_open',
            'saturday_open' => 'saturday_open',
            'sunday_open' => 'sunday_open',
            'monday_close' => 'monday_close',
            'tuesday_close' => 'tuesday_close',
            'wednesday_close' => 'wednesday_close',
            'thursday_close' => 'thursday_close',
            'friday_close' => 'friday_close',
            'saturday_close' => 'saturday_close',
            'sunday_close' => 'sunday_close',
        ];
        return $this->migrateTable($table, $backupTable, $columns);
    }

    /**
     * Process migrate data from backup
     *
     * @param string $table
     * @param string $backupTable
     * @param array $columns
     * @return string
     */
    protected function migrateTable($table, $backupTable, $columns)
    {
        // Remove Old Data
        $this->connection->delete($table);
        // Prepare select
        $select = $this->connection->select()->from(
            ['m' => $backupTable],
            $columns
        );
        $sql = $this->connection->insertFromSelect($select, $table, array_keys($columns));
        // Migrate data from Store Pickup
        $this->connection->query($sql);
        return $table;
    }
}
