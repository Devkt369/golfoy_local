<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Model\Migrate;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magestore\ClickAndCollect\Model\Carrier\Method as ClickAndCollectMethod;
use Magestore\ClickAndCollect\Model\Order\OrderPickupStatusResolverInterface;
use Magestore\ClickAndCollectApi\Api\Data\LocationAdditionalDataInterface;
use Magestore\ClickAndCollectMigrate\Api\Migrate\MigrateOrderServiceInterface;
use Magestore\Storepickup\Model\ResourceModel\Orders\StorepickupStatus;
use Magestore\ClickAndCollectMigrate\Model\ResourceModel\LocationStoreMap as LocationStoreMapResource;
use Magento\Sales\Model\Order;
use Magestore\Webpos\Api\Data\Checkout\OrderInterface as PosOrderInterface;

/**
 * Service Migrate Location
 */
class MigrateOrderService implements MigrateOrderServiceInterface
{
    const SHIPPING_DESCRIPTION_BACKUP = 'shipping_description_backup';

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var ClickAndCollectMethod
     */
    protected $clickAndCollectMethod;

    /**
     * MigrateOrderService constructor.
     * @param ResourceConnection $resource
     * @param ClickAndCollectMethod $clickAndCollectMethod
     */
    public function __construct(
        ResourceConnection $resource,
        ClickAndCollectMethod $clickAndCollectMethod
    ) {
        $this->resource = $resource;
        $this->connection = $this->resource->getConnection();
        $this->clickAndCollectMethod = $clickAndCollectMethod;
    }

    /**
     * @inheritDoc
     */
    public function migrate()
    {
        $table = $this->resource->getTableName('sales_order');
        $locationStoreMapTable = $this->resource->getTableName(LocationStoreMapResource::TABLE_NAME);
        $shippingDescription = $this->clickAndCollectMethod->getConfigData('title')
            . ' - ' . $this->clickAndCollectMethod->getConfigData('method_name');
        $shippingDescription = trim($shippingDescription, ' -');
        $columns = [
            LocationAdditionalDataInterface::LOCATION_PICKUP_ID => 'map.location_id',
            LocationAdditionalDataInterface::PICKUP_DATE_TIME => 'storepickup_time',
            LocationAdditionalDataInterface::PICKUP_STATUS => new \Zend_Db_Expr(
                "CASE"
                . " WHEN m.storepickup_status = ". StorepickupStatus::STOREPICUP_WAIT_FOR_PICKUP
                    . " THEN '". OrderPickupStatusResolverInterface::STATUS_READY_TO_PICK ."'"
                . " WHEN m.storepickup_status = ". StorepickupStatus::STOREPICUP_RECEIVED
                    . " THEN '". OrderPickupStatusResolverInterface::STATUS_DELIVERED ."'"
                . " WHEN m.state = '". Order::STATE_COMPLETE . "'"
                    . " THEN '". OrderPickupStatusResolverInterface::STATUS_DELIVERED ."'"
                . "ELSE '" . OrderPickupStatusResolverInterface::STATUS_PENDING . "' END"
            ),
            PosOrderInterface::SHIPPING_METHOD => new \Zend_Db_Expr(
                "'" . ClickAndCollectMethod::CLICK_AND_COLLECT_SHIPPING_METHOD . "'"
            ),
            self::SHIPPING_DESCRIPTION_BACKUP => $this->connection->getIfNullSql(
                self::SHIPPING_DESCRIPTION_BACKUP,
                OrderInterface::SHIPPING_DESCRIPTION
            ),
            OrderInterface::SHIPPING_DESCRIPTION => new \Zend_Db_Expr("'" . $shippingDescription . "'"),
        ];

        $select = $this->connection->select();
        $select->from(
            false,
            $columns
        )->join(
            ['map' => $locationStoreMapTable],
            '`map`.`storepickup_id` = `m`.`storepickup_id`',
            []
        )->where(
            '`m`.`storepickup_id` IS NOT NULL'
        )->where(
            '`map`.`location_id` IS NOT NULL'
        );
        $sql = $this->connection->updateFromSelect($select, ['m' => $table]);
        $this->connection->query($sql);
    }
}
