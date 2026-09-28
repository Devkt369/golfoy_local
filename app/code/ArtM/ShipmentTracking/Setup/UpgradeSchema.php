<?php

namespace ArtM\ShipmentTracking\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare((string)$context->getVersion(), '1.0.2', '<')) {
            $connection = $setup->getConnection();
            $tableName = $setup->getTable('sales_shipment_track');

            if ($connection->isTableExists($tableName)) {
                $columns = [
                    'tracking_status' => [
                        'type' => Table::TYPE_TEXT,
                        'length' => 100,
                        'options' => ['nullable' => true],
                        'comment' => 'Latest Courier Tracking Status',
                    ],
                    'tracking_response' => [
                        'type' => Table::TYPE_TEXT,
                        'length' => '2M',
                        'options' => ['nullable' => true],
                        'comment' => 'Latest Courier Tracking Response JSON',
                    ],
                    'shipment_items_json' => [
                        'type' => Table::TYPE_TEXT,
                        'length' => '2M',
                        'options' => ['nullable' => true],
                        'comment' => 'Shipment Item Details JSON',
                    ],
                    'order_created_at' => [
                        'type' => Table::TYPE_DATETIME,
                        'length' => null,
                        'options' => ['nullable' => true],
                        'comment' => 'Order Created At',
                    ],
                    'shipment_created_at' => [
                        'type' => Table::TYPE_DATETIME,
                        'length' => null,
                        'options' => ['nullable' => true],
                        'comment' => 'Shipment Created At',
                    ],
                    'out_for_delivery_at' => [
                        'type' => Table::TYPE_DATETIME,
                        'length' => null,
                        'options' => ['nullable' => true],
                        'comment' => 'Out For Delivery At',
                    ],
                    'delivered_at' => [
                        'type' => Table::TYPE_DATETIME,
                        'length' => null,
                        'options' => ['nullable' => true],
                        'comment' => 'Delivered At',
                    ],
                    'last_checked_at' => [
                        'type' => Table::TYPE_DATETIME,
                        'length' => null,
                        'options' => ['nullable' => true],
                        'comment' => 'Last Tracking Checked At',
                    ],
                ];

                foreach ($columns as $columnName => $column) {
                    if (!$connection->tableColumnExists($tableName, $columnName)) {
                        $connection->addColumn(
                            $tableName,
                            $columnName,
                            [
                                'type' => $column['type'],
                                'length' => $column['length'],
                                'nullable' => $column['options']['nullable'],
                                'comment' => $column['comment'],
                            ]
                        );
                    }
                }
            }
        }

        if (version_compare((string)$context->getVersion(), '1.0.3', '<')) {
            $connection = $setup->getConnection();
            $tableName = $setup->getTable('sales_shipment_track');

            if (
                $connection->isTableExists($tableName) &&
                !$connection->tableColumnExists($tableName, 'current_status_at')
            ) {
                $connection->addColumn(
                    $tableName,
                    'current_status_at',
                    [
                        'type' => Table::TYPE_DATETIME,
                        'nullable' => true,
                        'comment' => 'Latest Courier Status At',
                    ]
                );
            }
        }

        $setup->endSetup();
    }
}
