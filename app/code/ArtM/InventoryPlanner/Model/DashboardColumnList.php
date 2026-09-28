<?php

declare(strict_types=1);

namespace ArtM\InventoryPlanner\Model;

/**
 * Master list of every column the Dashboard table can render - shared by the
 * Settings page's column-grouping configurator, the Dashboard's column-visibility
 * popup, and the Dashboard table itself, so they can't drift apart.
 */
final class DashboardColumnList
{
    public const ALL_COLUMNS = [
        'product_id' => 'Product ID',
        'product_details' => 'Product Details',
        'attribute_set' => 'Attribute Set',
        'brand' => 'Brand',
        'from_qty' => 'Unit Sold (From Weeks)',
        'difference_qty' => 'Unit Sold (Difference)',
        'to_qty' => 'Unit Sold (To Weeks)',
        'growth_percent' => 'Growth %',
        'captured_inventory' => 'Captured Inventory',
        'live_inventory' => 'Live Inventory',
    ];
}
