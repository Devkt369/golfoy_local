<?php

namespace Magestore\Webpos\Plugin\CatalogInventory\Model\Stock;

use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Registry;
use Magestore\Webpos\Helper\Data;
use Magento\CatalogInventory\Model\Stock\Item as CoreStockItem;

/**
 * Plugin function getBackOrders of stock item
 */
class Item
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var Data
     */
    protected $helper;

    /**
     * Item constructor.
     *
     * @param Registry $registry
     * @param Data $helper
     */
    public function __construct(
        Registry $registry,
        Data $helper
    ) {
        $this->registry = $registry;
        $this->helper = $helper;
    }

    /**
     * After get backorders
     *
     * @param CoreStockItem $stockItem
     * @param int $isBackOrdered
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetBackorders(
        CoreStockItem $stockItem,
        $isBackOrdered
    ) {
        $isEnableCheckoutWithOutOfStockproduct = (int) $this->helper->getStoreConfig(
            'webpos/checkout/add_out_of_stock_product'
        );
        if ($isEnableCheckoutWithOutOfStockproduct && $this->registry->registry('create_order_webpos')) {
            return Stock::BACKORDERS_YES_NONOTIFY;
        }
        return $isBackOrdered;
    }
}
