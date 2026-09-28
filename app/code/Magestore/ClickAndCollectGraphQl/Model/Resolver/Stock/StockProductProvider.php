<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver\Stock;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * Model StockProductProvider
 */
class StockProductProvider implements ResolverInterface
{
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * StockProductProvider constructor.
     *
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        StockRegistryInterface $stockRegistry
    ) {
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!array_key_exists('model', $value) || !$value['model'] instanceof ProductInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /* @var $product ProductInterface */
        $product = $value['model'];

        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        if (!$stockItem) {
            return [];
        }

        return [
            'item_id' => $stockItem->getItemId(),
            'product_id' => $stockItem->getProductId(),
            'stock_id' => $stockItem->getStockId(),
            'qty' => $stockItem->getQty(),
            'is_qty_decimal' => $stockItem->getIsQtyDecimal(),
            'show_default_notification_message' => $stockItem->getShowDefaultNotificationMessage(),
            'use_config_min_qty' => $stockItem->getUseConfigMinQty(),
            'min_qty' => $stockItem->getMinQty(),
            'use_config_min_sale_qty' => $stockItem->getUseConfigMinSaleQty(),
            'min_sale_qty' => $stockItem->getMinSaleQty(),
            'use_config_max_sale_qty' => $stockItem->getUseConfigMaxSaleQty(),
            'max_sale_qty' => $stockItem->getMaxSaleQty(),
            'use_config_backorders' => $stockItem->getUseConfigBackorders(),
            'backorders' => $stockItem->getBackorders(),
            'use_config_notify_stock_qty' => $stockItem->getUseConfigNotifyStockQty(),
            'notify_stock_qty' => $stockItem->getNotifyStockQty(),
            'use_config_qty_increments' => $stockItem->getUseConfigQtyIncrements(),
            'qty_increments' => $stockItem->getQtyIncrements(),
            'use_config_enable_qty_inc' => $stockItem->getUseConfigEnableQtyInc(),
            'enable_qty_increments' => $stockItem->getEnableQtyIncrements(),
            'use_config_manage_stock' => $stockItem->getUseConfigManageStock(),
            'manage_stock' => $stockItem->getManageStock(),
            'is_in_stock' => $stockItem->getIsInStock(),
            'low_stock_date' => $stockItem->getLowStockDate(),
            'is_decimal_divided' => $stockItem->getIsDecimalDivided(),
            'stock_status_changed_auto' => $stockItem->getStockStatusChangedAuto(),
        ];
    }
}
