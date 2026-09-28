<?php

namespace Magestore\BranchRequest\Api\Data\InventoryTransfer;

interface InventoryTransferProductInterface
{
    const ENTITY_ID = 'entity_id';
    const INVENTORYTRANSFER_ID = 'inventorytransfer_id';
    const PRODUCT_ID = 'product_id';
    const PRODUCT_NAME = 'product_name';
    const PRODUCT_SKU = 'product_sku';
    const BARCODE = 'barcode';
    const QTY_REQUESTED = 'qty_requested';
    const QTY_RECEIVED = 'qty_received';
}
