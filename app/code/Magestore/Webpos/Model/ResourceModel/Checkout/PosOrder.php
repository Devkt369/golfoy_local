<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\ResourceModel\Checkout;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magestore\Webpos\Log\Logger;

/**
 * Class PosOrder
 *
 * Used for resource model table webpos_order
 */
class PosOrder extends AbstractDb
{
    const TABLE_NAME = 'webpos_order';

    const LIST_WEEK_TWO_MONTH_AGO = [
        ['monday this week', 'sunday this week'],
        ['-2 weeks monday', '-1 weeks sunday'],
        ['-3 weeks monday', '-2 weeks sunday'],
        ['-4 weeks monday', '-3 weeks sunday'],
        ['-5 weeks monday', '-4 weeks sunday'],
        ['-6 weeks monday', '-5 weeks sunday'],
        ['-7 weeks monday', '-6 weeks sunday'],
        ['-8 weeks monday', '-7 weeks sunday'],
        ['-9 weeks monday', '-8 weeks sunday'],
    ];

    const COLUMNS_WEBPOS_ORDER = [
        'status' => 'o.status',
        'created_at' => 'o.created_at',
        'additional_data' => 'o.additional_data',
        'increment_id' => 'o.increment_id',
        'session_id' => 'o.session_id',
        'store_id' => 'o.store_id',
        'location_id' => 'o.location_id',
        'pos_id' => 'o.pos_id',
        'pos_staff_id' => 'o.pos_staff_id',
        'order_created_time' => 'o.order_created_time',
        'params' => 'o.params',
        'queueing_status' => 'o.queueing_status',
        'number_of_trials' => 'o.number_of_trials',
        'is_notify_error' => 'o.is_notify_error'
    ];

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, 'id');
        $objectManager = ObjectManager::getInstance();
        $this->localeDate = $objectManager->get(TimezoneInterface::class);
        $this->logger = $objectManager->get(Logger::class);
    }

    /**
     * Function delete Webpos Backup Table
     */
    public function deleteWebposBackupTable()
    {
        $currentDate = $this->localeDate->date()->format("Y-m-d h:i:s");
        $dateTwoMonthAgo = date('Ymd', strtotime('last day of -3 month', strtotime($currentDate)));
        $connection = $this->getConnection();
        $tableNameToCompare = $this->getTable("webpos_order_backup_" . $dateTwoMonthAgo);
        foreach ($connection->getTables("%webpos_order_backup_%") as $table) {
            // Drop all table has been pass 2 months from current month
            if (strncmp($tableNameToCompare, $table, strlen($tableNameToCompare)) > 0) {
                if ($connection->isTableExists($table)) {
                    $connection->dropTable($table);
                }
            }
        }
    }

    /**
     * Process backup order of webpos order
     *
     * @param string $incrementId
     */
    public function processBackupOfWebposOrder($incrementId)
    {
        $currentDate = $this->localeDate->date()->format("Y-m-d h:i:s");
        $monday = date('Ymd', strtotime('monday this week', strtotime($currentDate)));
        $sunday = date('Ymd', strtotime('sunday this week', strtotime($currentDate)));

        $connection = $this->getConnection();
        $backupTable = $this->getTable("webpos_order_backup_" . $monday . "_" . $sunday);
        $mainTable = $this->getMainTable();
        if (!$connection->isTableExists($backupTable)) {
            $this->createTableBackupWebposOrder($backupTable, $mainTable);
        }

        $select = $connection->select();
        $select->from(
            ['o' => $mainTable],
            self::COLUMNS_WEBPOS_ORDER
        )->where("increment_id = ?", $incrementId);
        $connection->query($connection->insertFromSelect(
            $select,
            $backupTable,
            array_keys(self::COLUMNS_WEBPOS_ORDER)
        ));
        $connection->delete($mainTable, ["increment_id = ?" => $incrementId]);
    }

    /**
     * Function deleteWebposOrderBeforeTwoMonthAgo
     */
    public function deleteWebposOrderBeforeTwoMonthAgo()
    {
        $connection = $this->getConnection();
        $currentDate = $this->localeDate->date()->format('Y-m-d h:i:s');
        $dateTwoMonthAgo = date('Ymd', strtotime('last day of -2 month', strtotime($currentDate)));
        $mainTable = $this->getMainTable();
        try {
            $connection->delete(
                $mainTable,
                [
                    'created_at <= ?' => $dateTwoMonthAgo,
                    'status = ?' => \Magestore\Webpos\Model\Checkout\PosOrder::STATUS_COMPLETED
                ]
            );
        } catch (\Exception $error) {
            $this->logger->error($error->getMessage());
        }
    }

    /**
     * Function moveToBackupOfWebposOrderByBulk
     */
    public function moveToBackupOfWebposOrderByBulk()
    {
        $connection = $this->getConnection();
        try {
            foreach (self::LIST_WEEK_TWO_MONTH_AGO as $week) {
                $dataDayAndTable = $this->getInformationTimeAndTableFromWeek($week[0], $week[1]);
                if ($connection->isTableExists($dataDayAndTable['backupTableName'])) {
                    $select = $connection->select();
                    $select->from(
                        ['o' => $dataDayAndTable['mainTableName']],
                        self::COLUMNS_WEBPOS_ORDER
                    )
                        ->where("created_at >= ?", $dataDayAndTable['monday'])
                        ->where("created_at <= ?", $dataDayAndTable['sunday'])
                        ->where("status = ?", \Magestore\Webpos\Model\Checkout\PosOrder::STATUS_COMPLETED);
                    $connection->query($connection->insertFromSelect(
                        $select,
                        $dataDayAndTable['backupTableName'],
                        array_keys(self::COLUMNS_WEBPOS_ORDER)
                    ));
                    $connection->delete(
                        $dataDayAndTable['mainTableName'],
                        [
                            "created_at >= ?" => $dataDayAndTable['monday'],
                            "created_at <= ?" => $dataDayAndTable['sunday'],
                            "status = ?" => \Magestore\Webpos\Model\Checkout\PosOrder::STATUS_COMPLETED
                        ]
                    );
                }
            }
        } catch (\Exception $error) {
            $this->logger->error($error->getMessage());
        }
    }

    /**
     * Function createBackupOfWebposOrderTable
     */
    public function createBackupOfWebposOrderTable()
    {
        try {
            $connection = $this->getConnection();
            foreach (self::LIST_WEEK_TWO_MONTH_AGO as $week) {
                $dataDayAndTable = $this->getInformationTimeAndTableFromWeek($week[0], $week[1]);
                if (!$connection->isTableExists($dataDayAndTable['backupTableName'])) {
                    $this->createTableBackupWebposOrder(
                        $dataDayAndTable['backupTableName'],
                        $dataDayAndTable['mainTableName']
                    );
                }
            }
        } catch (\Exception $error) {
            $this->logger->error($error->getMessage());
        }
    }

    /**
     * Function  createTableBackupWebposOrder used for create table backup data from exist table for webpos order
     *
     * @param string $newTableName
     * @param string $originTable
     * @throws \Zend_Db_Exception
     */
    public function createTableBackupWebposOrder($newTableName, $originTable)
    {
        $connection = $this->getConnection();
        $tmpTable = $connection->createTableByDdl($originTable, $newTableName);
        $connection->createTable($tmpTable);
    }

    /**
     * Function get Information of Time And Table name From Week
     *
     * @param string $codeMonday
     * @param string $codeSunday
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getInformationTimeAndTableFromWeek($codeMonday, $codeSunday)
    {
        $currentDate = $this->localeDate->date()->format("Y-m-d h:i:s");
        $monday = date('Ymd', strtotime($codeMonday, strtotime($currentDate)));
        $sunday = date('Ymd', strtotime($codeSunday, strtotime($currentDate)));
        $backupTableName = $this->getTable("webpos_order_backup_" . $monday . "_" . $sunday);
        $mainTableName = $this->getMainTable();

        return [
            'monday' => $monday,
            'sunday' => $sunday,
            'backupTableName' => $backupTableName,
            'mainTableName' => $mainTableName
        ];
    }
}
