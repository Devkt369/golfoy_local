<?php

namespace Magestore\BranchRequest\Api\Data\InventoryTransfer;

interface InventoryTransferInterface
{
    const STATUS_NEW = 0;
    const STATUS_WAIT_APPROVE = 1;
    const STATUS_APPROVED = 2;
    const STATUS_PROCESSING = 3;
    const STATUS_CANCELED = 4;
    const STATUS_CLOSED = 5;
    const ID = 'inventorytransfer_id';
    const CODE = 'branchrequest_code';
    const FROM = 'source_warehouse_code';
    const TO = 'des_warehouse_code';
    const CREATED_BY_ID = 'created_by_id';
    const CREATED_BY_NAME = 'created_by_name';
    const APPROVED_BY_ID = 'approved_by_id';
    const APPROVED_BY_NAME = 'approved_by_name';
    const CANCELED_BY_ID = 'canceled_by_id';
    const CANCELED_BY_NAME = 'canceled_by_name';
    const CREATED_AT = 'created_at';
    const STATUS = 'status';
    const QTY_REQUESTED = 'qty_requested';
    const QTY_DELIVERED = 'qty_delivered';
    const QTY_RECEIVED = 'qty_received';
}
