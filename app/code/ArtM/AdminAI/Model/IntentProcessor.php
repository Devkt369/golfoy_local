<?php
namespace ArtM\AdminAI\Model;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory as SourceItemCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use ArtM\AdminAI\Model\IntentDayCacheService;
use ArtM\AdminAI\Model\Service\AnalyticsIntentService;

class IntentProcessor
{
    private const SUPPORTED_INTENTS = [
        [
            'group' => 'Catalog',
            'code' => 'low_stock',
            'label' => 'low stock',
            'description' => 'Find products with inventory quantity within a specified range. Useful for identifying slow-moving stock or dangerously low inventory.',
            'command' => 'low_stock {min_qty} {max_qty}',
            'example' => 'low_stock 5 15',
            'params' => [
                [
                    'name' => 'min_qty',
                    'label' => 'Min Qty',
                    'description' => 'Minimum stock quantity threshold. Products with qty >= this value are included.',
                    'required' => false,
                    'default' => 0,
                    'placeholder' => '5'
                ],
                [
                    'name' => 'max_qty',
                    'label' => 'Max Qty',
                    'description' => 'Maximum stock quantity threshold. Products with qty <= this value are included.',
                    'required' => true,
                    'default' => 10,
                    'placeholder' => '15'
                ]
            ]
        ],
        [
            'group' => 'Catalog',
            'code' => 'product_qty',
            'label' => 'product qty',
            'description' => 'Get current stock quantity for a specific product. Works with both SKU and product ID.',
            'command' => 'product_qty {sku}',
            'example' => 'product_qty ABC123',
            'params' => [
                [
                    'name' => 'sku',
                    'label' => 'Product SKU',
                    'description' => 'Product SKU (e.g., ABC123) or Product ID (e.g., 1234). Used to identify which product to check stock for.',
                    'required' => true,
                    'placeholder' => 'ABC123'
                ]
            ]
        ],
        [
            'group' => 'Sales',
            'code' => 'sales_today',
            'label' => 'sales_today',
            'description' => 'Get today\'s sales summary: total orders, revenue, and order count.',
            'command' => 'sales_today',
            'example' => 'sales_today',
            'params' => []
        ],
        [
            'group' => 'Sales',
            'code' => 'trending_products',
            'label' => 'trending products',
            'description' => 'Find top-selling products within a date range. You can filter by product category (attribute set) and specific product attributes (color, size, etc.).',
            'command' => 'trending_products {limit} {from_date} {to_date} {attribute_sets} {attribute_filters}',
            'example' => 'trending_products 10 2026-01-01 2026-03-01 tshirts,trousers color=red',
            'params' => [
                [
                    'name' => 'limit',
                    'label' => 'Top N',
                    'description' => 'Number of products to show (max 500). e.g., "10" shows top 10 trending products.',
                    'required' => true,
                    'default' => 10,
                    'max' => 500,
                    'placeholder' => '10'
                ],
                [
                    'name' => 'from_date',
                    'label' => 'From Date (YYYY-MM-DD)',
                    'description' => 'Start date for the trend analysis period. Default is 3 months ago. Max range is 1 year.',
                    'required' => false,
                    'type' => 'date',
                    'default' => 'last_3_months',
                    'max_range' => '1_year',
                    'placeholder' => '2026-01-01'
                ],
                [
                    'name' => 'to_date',
                    'label' => 'To Date (YYYY-MM-DD)',
                    'description' => 'End date for the trend analysis period. Default is today. Max range is 1 year.',
                    'required' => false,
                    'type' => 'date',
                    'default' => 'today',
                    'max_range' => '1_year',
                    'placeholder' => '2026-03-01'
                ],
                [
                    'name' => 'attribute_sets',
                    'label' => 'Attribute Sets (comma separated)',
                    'description' => 'Product categories to include (e.g., "tshirts,trousers,drivers"). Leave blank for all categories.',
                    'required' => false,
                    'options_source' => 'attribute_sets',
                    'placeholder' => 'tshirts,trousers,drivers'
                ],
                [
                    'name' => 'attribute_filters',
                    'label' => 'Attribute Filters (key=value;key=value)',
                    'description' => 'Filter products by attributes (e.g., "color=red;size=l"). Only works if Attribute Sets are selected.',
                    'required' => false,
                    'options_source' => 'attribute_filters',
                    'placeholder' => 'color=red;size=l'
                ]
            ]
        ],
        [
            'group' => 'Catalog',
            'code' => 'catalog_product_data',
            'label' => 'product data by sku/id',
            'description' => 'Retrieve specific product information (name, price, status, etc.) using SKU or product ID.',
            'command' => 'catalog_product_data {sku_or_id} {attributes}',
            'example' => 'catalog_product_data GOLTEMXN100SP name,price,status',
            'params' => [
                [
                    'name' => 'sku_or_id',
                    'label' => 'SKU or ID',
                    'description' => 'Product identifier. Use either SKU (e.g., GOLTEMXN100SP) or numeric ID (e.g., 1234).',
                    'required' => true,
                    'placeholder' => 'GOLTEMXN100SP or 1234'
                ],
                [
                    'name' => 'attributes',
                    'label' => 'Attributes (comma separated)',
                    'description' => 'Which product fields to fetch (e.g., "name,price,status,visibility,tax_class"). Leave blank to get all.',
                    'required' => true,
                    'entity_type' => 'product',
                    'placeholder' => 'name,price,status,visibility'
                ]
            ]
        ],
        [
            'group' => 'Catalog',
            'code' => 'category_search_by_name',
            'label' => 'search category by name',
            'description' => 'Find product categories by name. Returns full category breadcrumb paths (e.g., "Home > Sports > Golf").',
            'command' => 'category_search_by_name {name_query}',
            'example' => 'category_search_by_name balls',
            'params' => [
                [
                    'name' => 'name_query',
                    'label' => 'Category Name',
                    'description' => 'Category name or partial name to search for (e.g., "balls", "shoes"). Search is case-insensitive and matches partial names.',
                    'required' => true,
                    'placeholder' => 'balls'
                ]
            ]
        ],
        [
            'group' => 'Catalog',
            'code' => 'category_data_by_id',
            'label' => 'category data by id',
            'description' => 'Get all category information (description, layout, theme, name, url, etc.) using the category ID. By default returns all available attributes.',
            'command' => 'category_data_by_id {category_id} {attributes}',
            'example' => 'category_data_by_id 12',
            'params' => [
                [
                    'name' => 'category_id',
                    'label' => 'Category ID',
                    'description' => 'Numeric category ID. Find this from "Search Category" intent or admin panel URL.',
                    'required' => true,
                    'placeholder' => '12'
                ],
                [
                    'name' => 'attributes',
                    'label' => 'Attributes (comma separated)',
                    'description' => 'Specific category properties to retrieve (e.g., "description,page_layout,custom_theme,name,url_key"). Leave blank to fetch all attributes.',
                    'required' => false,
                    'entity_type' => 'category',
                    'placeholder' => 'description,page_layout,custom_theme (optional)'
                ]
            ]
        ],
        [
            'group' => 'Catalog',
            'code' => 'reserved_products',
            'label' => 'reserved products',
            'description' => 'List products with pending/reserved inventory. Optionally filter by a specific SKU to check reservation for a single product.',
            'command' => 'reserved_products {sku} {limit}',
            'example' => 'reserved_products ABC123',
            'params' => [
                [
                    'name' => 'sku',
                    'label' => 'SKU (optional)',
                    'description' => 'Product SKU to check reservation for (e.g., ABC123). Leave blank to list all reserved products.',
                    'required' => false,
                    'placeholder' => 'ABC123 (optional)'
                ],
                [
                    'name' => 'limit',
                    'label' => 'Limit (max 500)',
                    'description' => 'Maximum number of reserved products to display. Ignored when SKU is specified. Default: 10.',
                    'required' => false,
                    'default' => 10,
                    'max' => 500,
                    'placeholder' => '10'
                ]
            ]
        ],
        [
            'group' => 'Sales',
            'code' => 'sales_on_date',
            'label' => 'sales on date',
            'description' => 'Get sales summary for a specific date: total orders, total revenue, and order count.',
            'command' => 'sales_on_date {date}',
            'example' => 'sales_on_date 2026-03-20',
            'params' => [
                [
                    'name' => 'date',
                    'label' => 'Date (YYYY-MM-DD)',
                    'description' => 'The specific date to analyze sales for (e.g., "2026-03-20" for March 20, 2026).',
                    'required' => true,
                    'type' => 'date',
                    'placeholder' => '2026-03-20'
                ]
            ]
        ],
        [
            'group' => 'Sales',
            'code' => 'sales_date_range',
            'label' => 'sales date range',
            'description' => 'Get sales summary for a period between two dates. Shows total orders, total revenue, and order count. Max range: 1 year.',
            'command' => 'sales_date_range {from_date} {to_date}',
            'example' => 'sales_date_range 2025-12-01 2026-03-01',
            'params' => [
                [
                    'name' => 'from_date',
                    'label' => 'From Date (YYYY-MM-DD)',
                    'description' => 'Start date of the sales period (e.g., "2025-12-01"). Default is 3 months ago. Max range is 1 year.',
                    'required' => true,
                    'type' => 'date',
                    'default' => 'last_3_months',
                    'max_range' => '1_year',
                    'placeholder' => '2025-12-01'
                ],
                [
                    'name' => 'to_date',
                    'label' => 'To Date (YYYY-MM-DD)',
                    'description' => 'End date of the sales period (e.g., "2026-03-01"). Default is today. Max range is 1 year.',
                    'required' => true,
                    'type' => 'date',
                    'default' => 'today',
                    'max_range' => '1_year',
                    'placeholder' => '2026-03-01'
                ]
            ]
        ],
        [
            'group' => 'Sales',
            'code' => 'sales_filtered',
            'label' => 'sales with product filters',
            'description' => 'Analyze sales within a date range, filtered by product category (attribute set) and specific attributes (color, size). Shows quantities and revenue for matching products.',
            'command' => 'sales_filtered {from_date} {to_date} {attribute_sets} {attribute_filters}',
            'example' => 'sales_filtered 2026-01-01 2026-03-01 drivers,balls color=brown;size=l',
            'params' => [
                [
                    'name' => 'from_date',
                    'label' => 'From Date (YYYY-MM-DD)',
                    'description' => 'Start date for sales analysis (e.g., "2026-01-01"). Default is 3 months ago. Max range is 1 year.',
                    'required' => false,
                    'type' => 'date',
                    'default' => 'last_3_months',
                    'max_range' => '1_year',
                    'placeholder' => '2026-01-01'
                ],
                [
                    'name' => 'to_date',
                    'label' => 'To Date (YYYY-MM-DD)',
                    'description' => 'End date for sales analysis (e.g., "2026-03-01"). Default is today. Max range is 1 year.',
                    'required' => false,
                    'type' => 'date',
                    'default' => 'today',
                    'max_range' => '1_year',
                    'placeholder' => '2026-03-01'
                ],
                [
                    'name' => 'attribute_sets',
                    'label' => 'Attribute Sets (comma separated)',
                    'description' => 'Product categories to filter by (e.g., "drivers,balls,tshirts"). Leave blank to include all categories.',
                    'required' => false,
                    'options_source' => 'attribute_sets',
                    'placeholder' => 'drivers,balls,tshirts'
                ],
                [
                    'name' => 'attribute_filters',
                    'label' => 'Attribute Filters (key=value;key=value)',
                    'description' => 'Filter products by specific attributes (e.g., "color=brown;size=l"). Multiple filters use semicolon separator. Optional.',
                    'required' => false,
                    'options_source' => 'attribute_filters',
                    'placeholder' => 'color=brown;size=l'
                ]
            ]
        ],
        [
            'group' => 'Analytics',
            'code' => 'customer_risk_summary',
            'label' => 'customer risk summary',
            'description' => 'Review customer risk segments with optional filtering by risk level and store.',
            'command' => 'customer_risk_summary {risk_level} {limit} {store_id}',
            'example' => 'customer_risk_summary high 20 1',
            'params' => [
                [
                    'name' => 'risk_level',
                    'label' => 'Risk Level',
                    'description' => 'Optional risk segment filter such as low, medium, or high.',
                    'required' => false,
                    'placeholder' => 'high'
                ],
                [
                    'name' => 'limit',
                    'label' => 'Limit',
                    'description' => 'Maximum number of customers to return. Default depends on intent logic.',
                    'required' => false,
                    'type' => 'number',
                    'placeholder' => '20'
                ],
                [
                    'name' => 'store_id',
                    'label' => 'Store ID',
                    'description' => 'Optional Magento store ID to narrow the summary.',
                    'required' => false,
                    'type' => 'number',
                    'placeholder' => '1'
                ]
            ]
        ],
        [
            'group' => 'Analytics',
            'code' => 'stock_projection',
            'label' => 'stock projection',
            'description' => 'Project stock coverage for a future window, optionally for a single SKU.',
            'command' => 'stock_projection {days_ahead} {sku} {limit}',
            'example' => 'stock_projection 30 GOLTEMXN100SP 10',
            'params' => [
                [
                    'name' => 'days_ahead',
                    'label' => 'Days Ahead',
                    'description' => 'Number of days ahead for the stock projection window.',
                    'required' => false,
                    'type' => 'number',
                    'placeholder' => '30'
                ],
                [
                    'name' => 'sku',
                    'label' => 'SKU',
                    'description' => 'Optional SKU for projecting a single product instead of a list.',
                    'required' => false,
                    'placeholder' => 'GOLTEMXN100SP'
                ],
                [
                    'name' => 'limit',
                    'label' => 'Limit',
                    'description' => 'Maximum number of projected rows to return when SKU is not provided.',
                    'required' => false,
                    'type' => 'number',
                    'placeholder' => '10'
                ]
            ]
        ],
        [
            'group' => 'Analytics',
            'code' => 'product_performance',
            'label' => 'product performance',
            'description' => 'Analyze product performance for a required date range with optional store and result limit filters.',
            'command' => 'product_performance {from_date} {to_date} {limit} {store_id}',
            'example' => 'product_performance 2026-01-01 2026-03-01 20 1',
            'params' => [
                [
                    'name' => 'from_date',
                    'label' => 'From Date (YYYY-MM-DD)',
                    'description' => 'Start date for the performance window.',
                    'required' => true,
                    'type' => 'date',
                    'placeholder' => '2026-01-01'
                ],
                [
                    'name' => 'to_date',
                    'label' => 'To Date (YYYY-MM-DD)',
                    'description' => 'End date for the performance window.',
                    'required' => true,
                    'type' => 'date',
                    'placeholder' => '2026-03-01'
                ],
                [
                    'name' => 'limit',
                    'label' => 'Limit',
                    'description' => 'Maximum number of products to return.',
                    'required' => false,
                    'type' => 'number',
                    'placeholder' => '20'
                ],
                [
                    'name' => 'store_id',
                    'label' => 'Store ID',
                    'description' => 'Optional Magento store ID for the performance view.',
                    'required' => false,
                    'type' => 'number',
                    'placeholder' => '1'
                ]
            ]
        ],
        [
            'group' => 'Analytics',
            'code' => 'executive_sales_summary',
            'label' => 'executive sales summary',
            'description' => 'Summarize high-level sales performance for a preset executive reporting period.',
            'command' => 'executive_sales_summary {period} {store_id}',
            'example' => 'executive_sales_summary 30d 1',
            'params' => [
                [
                    'name' => 'period',
                    'label' => 'Period',
                    'description' => 'Executive reporting period to summarize.',
                    'required' => true,
                    'type' => 'select',
                    'default' => '30d',
                    'options' => [
                        ['value' => '7d', 'label' => '7d'],
                        ['value' => '30d', 'label' => '30d'],
                        ['value' => '90d', 'label' => '90d']
                    ]
                ],
                [
                    'name' => 'store_id',
                    'label' => 'Store ID',
                    'description' => 'Optional Magento store ID to scope the summary.',
                    'required' => false,
                    'type' => 'number',
                    'placeholder' => '1'
                ]
            ]
        ],
        [
            'group' => 'Analytics',
            'code' => 'inactive_customers',
            'label' => 'inactive customers',
            'description' => 'Find customers with no recent activity based on an inactivity threshold and optional store filter.',
            'command' => 'inactive_customers {inactive_days} {limit} {store_id}',
            'example' => 'inactive_customers 90 20 1',
            'params' => [
                [
                    'name' => 'inactive_days',
                    'label' => 'Inactive Days',
                    'description' => 'Number of days since the last order to treat a customer as inactive.',
                    'required' => false,
                    'type' => 'number',
                    'placeholder' => '90'
                ],
                [
                    'name' => 'limit',
                    'label' => 'Limit',
                    'description' => 'Maximum number of inactive customers to return.',
                    'required' => false,
                    'type' => 'number',
                    'placeholder' => '20'
                ],
                [
                    'name' => 'store_id',
                    'label' => 'Store ID',
                    'description' => 'Optional Magento store ID to scope customer inactivity.',
                    'required' => false,
                    'type' => 'number',
                    'placeholder' => '1'
                ]
            ]
        ],
        [
            'group' => 'Analytics',
            'code' => 'customer_segment_list',
            'label' => 'customer segment list',
            'description' => 'List customer IDs by CDP behavioral segment from analytics segment storage.',
            'command' => 'customer_segment_list {segment_code} {limit} {store_id}',
            'example' => 'customer_segment_list INACTIVE_CUSTOMER 20 1',
            'params' => [
                [
                    'name' => 'segment_code',
                    'label' => 'Segment Code',
                    'description' => 'Optional segment code filter such as NEW_CUSTOMER or DISCOUNT_HUNTER.',
                    'required' => false,
                    'type' => 'select',
                    'default' => '',
                    'options' => [
                        ['value' => '', 'label' => 'All segments'],
                        ['value' => 'NEW_CUSTOMER', 'label' => 'NEW_CUSTOMER'],
                        ['value' => 'FREQUENT_BUYER', 'label' => 'FREQUENT_BUYER'],
                        ['value' => 'HIGH_VALUE_CUSTOMER', 'label' => 'HIGH_VALUE_CUSTOMER'],
                        ['value' => 'DISCOUNT_HUNTER', 'label' => 'DISCOUNT_HUNTER'],
                        ['value' => 'NEW_ARRIVAL_BUYER', 'label' => 'NEW_ARRIVAL_BUYER'],
                        ['value' => 'CATEGORY_LOYAL', 'label' => 'CATEGORY_LOYAL'],
                        ['value' => 'AT_RISK_CUSTOMER', 'label' => 'AT_RISK_CUSTOMER'],
                        ['value' => 'INACTIVE_CUSTOMER', 'label' => 'INACTIVE_CUSTOMER']
                    ],
                    'placeholder' => 'NEW_CUSTOMER'
                ],
                [
                    'name' => 'limit',
                    'label' => 'Limit',
                    'description' => 'Maximum number of rows to return.',
                    'required' => false,
                    'type' => 'number',
                    'placeholder' => '20'
                ],
                [
                    'name' => 'store_id',
                    'label' => 'Store ID',
                    'description' => 'Optional Magento store ID to scope the segment list.',
                    'required' => false,
                    'type' => 'number',
                    'placeholder' => '1'
                ]
            ]
        ],
        [
            'group' => 'Reviews',
            'code' => 'reviews_recent',
            'label' => 'recent reviews',
            'description' => 'Get recent customer reviews from the last N days. Shows reviewer name, rating, and review text.',
            'command' => 'reviews_recent {days} {limit}',
            'example' => 'reviews_recent 10 20',
            'params' => [
                [
                    'name' => 'days',
                    'label' => 'Days',
                    'description' => 'Number of days to look back for reviews (e.g., "10" shows reviews from last 10 days). Default: 10, Max: 365 days (1 year).',
                    'required' => false,
                    'default' => 10,
                    'max' => 365,
                    'placeholder' => '10'
                ],
                [
                    'name' => 'limit',
                    'label' => 'Limit',
                    'description' => 'Maximum number of reviews to display (e.g., "20" shows 20 most recent reviews). Default: 10, Max: 500.',
                    'required' => false,
                    'default' => 10,
                    'max' => 500,
                    'placeholder' => '20'
                ]
            ]
        ],
        [
            'group' => 'Reviews',
            'code' => 'products_most_reviews',
            'label' => 'products with most reviews',
            'description' => 'Find products with the highest number of customer reviews. Shows products that are generating the most customer feedback.',
            'command' => 'products_most_reviews {limit}',
            'example' => 'products_most_reviews 10',
            'params' => [
                [
                    'name' => 'limit',
                    'label' => 'Limit',
                    'description' => 'Number of top-reviewed products to display (e.g., "10" shows the 10 products with most reviews). Default: 10, Max: 500.',
                    'required' => false,
                    'default' => 10,
                    'max' => 500,
                    'placeholder' => '10'
                ]
            ]
        ],
        [
            'group' => 'Reviews',
            'code' => 'products_highest_reviews',
            'label' => 'highest rated products',
            'description' => 'Find products with the best customer ratings. Shows products with the highest average review scores.',
            'command' => 'products_highest_reviews {limit}',
            'example' => 'products_highest_reviews 10',
            'params' => [
                [
                    'name' => 'limit',
                    'label' => 'Limit',
                    'description' => 'Number of highest-rated products to display (e.g., "10" shows the 10 best-rated products). Default: 10, Max: 500.',
                    'required' => false,
                    'default' => 10,
                    'max' => 500,
                    'placeholder' => '10'
                ]
            ]
        ],
        [
            'group' => 'Customer',
            'code' => 'customer_search',
            'label' => 'search customer',
            'description' => 'Find customers by name, email, or phone number. Returns matching customer profiles with contact details.',
            'command' => 'customer_search {query}',
            'example' => 'customer_search mohit makhija',
            'params' => [
                [
                    'name' => 'query',
                    'label' => 'Customer Query',
                    'description' => 'Customer name, email address, or phone number to search for (e.g., "john doe", "john@example.com", "9876543210").',
                    'required' => true,
                    'placeholder' => 'mohit makhija or email/phone'
                ]
            ]
        ],
        [
            'group' => 'Customer',
            'code' => 'customer_details',
            'label' => 'customer details',
            'description' => 'Get detailed customer information (name, email, customer group, registration date, etc.) using customer ID.',
            'command' => 'customer_details {customer_id} {attributes}',
            'example' => 'customer_details 123 firstname,lastname,email,group_id',
            'params' => [
                [
                    'name' => 'customer_id',
                    'label' => 'Customer ID',
                    'description' => 'Numeric customer ID. Find this using "Search Customer" intent or from admin customer profile URL.',
                    'required' => true,
                    'placeholder' => '123'
                ],
                [
                    'name' => 'attributes',
                    'label' => 'Attributes (comma separated)',
                    'description' => 'Which customer fields to retrieve (e.g., "firstname,lastname,email,group_id,created_at,gender"). Leave blank for all.',
                    'required' => false,
                    'entity_type' => 'customer',
                    'placeholder' => 'firstname,lastname,email,group_id'
                ]
            ]
        ],
        [
            'group' => 'Customer',
            'code' => 'customer_addresses',
            'label' => 'customer addresses',
            'description' => 'Get all address records for a customer. Shows billing and shipping addresses with full details.',
            'command' => 'customer_addresses {customer_id}',
            'example' => 'customer_addresses 123',
            'params' => [
                [
                    'name' => 'customer_id',
                    'label' => 'Customer ID',
                    'description' => 'Numeric customer ID. Find this using "Search Customer" intent or from admin customer profile URL.',
                    'required' => true,
                    'placeholder' => '123'
                ]
            ]
        ],
        [
            'group' => 'Customer',
            'code' => 'customers_by_location',
            'label' => 'customers by location',
            'description' => 'Find customers by their location: city, region/state, or country. Useful for regional analysis or targeted outreach.',
            'command' => 'customers_by_location {city} {region} {country} {limit}',
            'example' => 'customers_by_location chandigarh 100',
            'params' => [
                [
                    'name' => 'city',
                    'label' => 'City',
                    'description' => 'City name to search for customers in (e.g., "delhi", "mumbai"). Optional - leave blank to skip city filter.',
                    'required' => false,
                    'placeholder' => 'delhi'
                ],
                [
                    'name' => 'region',
                    'label' => 'Region/State',
                    'description' => 'State/Region name to filter customers (e.g., "chandigarh", "maharashtra"). Can be used with or without city. Optional.',
                    'required' => false,
                    'placeholder' => 'chandigarh'
                ],
                [
                    'name' => 'country',
                    'label' => 'Country ID',
                    'description' => 'Two-letter country code (e.g., "IN" for India, "US" for USA). Use country code not full name. Optional.',
                    'required' => false,
                    'placeholder' => 'IN'
                ],
                [
                    'name' => 'limit',
                    'label' => 'Limit',
                    'description' => 'Maximum number of customers to display (e.g., "20" shows 20 customers). Default: 20, Max: 500.',
                    'required' => false,
                    'default' => 20,
                    'max' => 500,
                    'placeholder' => '20'
                ]
            ]
        ]
    ];

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $salableQuantity;

    /**
     * @var SourceItemCollectionFactory
     */
    private $sourceItemCollectionFactory;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var OrderItemCollectionFactory
     */
    private $orderItemCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var IntentDayCacheService
     */
    private $dayCacheService;

    /** @var AnalyticsIntentService */
    private $analyticsIntentService;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param GetSalableQuantityDataBySku $salableQuantity
     * @param SourceItemCollectionFactory $sourceItemCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderItemCollectionFactory $orderItemCollectionFactory
     * @param LoggerInterface $logger
     * @param Configurable $configurableType
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        GetSalableQuantityDataBySku $salableQuantity,
        SourceItemCollectionFactory $sourceItemCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        LoggerInterface $logger,
            Configurable $configurableType,
            IntentDayCacheService $dayCacheService,
            AnalyticsIntentService $analyticsIntentService
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->salableQuantity = $salableQuantity;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->logger = $logger;
        $this->configurableType = $configurableType;
            $this->dayCacheService = $dayCacheService;
            $this->analyticsIntentService = $analyticsIntentService;
    }

    /**
     * @return array
     */
    public function getSupportedIntents(): array
    {
        return self::SUPPORTED_INTENTS;
    }

    /**
     * @return array
     */
    public function getSupportedIntentCodes(): array
    {
        return array_column(self::SUPPORTED_INTENTS, 'code');
    }

    /**
     * Process intent and return formatted response
     *
     * @param array $intentData Intent data from Gemini with 'intent' and optional 'sku'/'message'
     * @return string Formatted plain text response
     */
    public function process(array $intentData): string
    {
        try {
            $intent = $intentData['intent'] ?? 'general_chat';

            switch ($intent) {
                case 'low_stock':
                    $minQty = (int)($intentData['min_qty'] ?? 0);
                    $maxQty = (int)($intentData['max_qty'] ?? 10);
                    return $this->processLowStock($minQty, $maxQty);
                case 'product_qty':
                    $sku = $intentData['sku'] ?? '';
                    return $this->processProductQty($sku);
                case 'sales_today':
                    return $this->processSalesToday();
                case 'trending_products':
                    $limit = (int)($intentData['limit'] ?? 10);
                    return $this->processTrendingProducts(
                        $limit,
                        $intentData['from_date'] ?? null,
                        $intentData['to_date'] ?? null,
                        (string)($intentData['attribute_sets'] ?? ''),
                        (string)($intentData['attribute_filters'] ?? '')
                    );
                case 'catalog_product_data':
                    return $this->processCatalogProductData(
                        (string)($intentData['sku_or_id'] ?? ''),
                        (string)($intentData['attributes'] ?? '')
                    );
                case 'category_search_by_name':
                    return $this->processCategorySearchByName((string)($intentData['name_query'] ?? ''));
                case 'category_data_by_id':
                    return $this->processCategoryDataById(
                        (int)($intentData['category_id'] ?? 0),
                        (string)($intentData['attributes'] ?? '')
                    );
                case 'reserved_products':
                    return $this->processReservedProducts((int)($intentData['limit'] ?? 10), (string)($intentData['sku'] ?? ''));
                case 'sales_on_date':
                    return $this->processSalesOnDate((string)($intentData['date'] ?? ''));
                case 'sales_date_range':
                    return $this->processSalesDateRange(
                        (string)($intentData['from_date'] ?? ''),
                        (string)($intentData['to_date'] ?? '')
                    );
                case 'sales_filtered':
                    return $this->processSalesFiltered(
                        (string)($intentData['from_date'] ?? ''),
                        (string)($intentData['to_date'] ?? ''),
                        (string)($intentData['attribute_sets'] ?? ''),
                        (string)($intentData['attribute_filters'] ?? '')
                    );
                case 'reviews_recent':
                    return $this->processReviewsRecent(
                        (int)($intentData['days'] ?? 10),
                        (int)($intentData['limit'] ?? 10)
                    );
                case 'products_most_reviews':
                    return $this->processProductsMostReviews((int)($intentData['limit'] ?? 10));
                case 'products_highest_reviews':
                    return $this->processProductsHighestReviews((int)($intentData['limit'] ?? 10));
                case 'customer_search':
                    return $this->processCustomerSearch((string)($intentData['query'] ?? ''));
                case 'customer_details':
                    return $this->processCustomerDetails(
                        (int)($intentData['customer_id'] ?? 0),
                        (string)($intentData['attributes'] ?? '')
                    );
                case 'customer_addresses':
                    return $this->processCustomerAddresses((int)($intentData['customer_id'] ?? 0));
                case 'customers_by_location':
                    return $this->processCustomersByLocation(
                        (string)($intentData['city'] ?? ''),
                        (string)($intentData['region'] ?? ''),
                        (string)($intentData['country'] ?? ''),
                        (int)($intentData['limit'] ?? 20)
                    );
                case 'customer_risk_summary':
                    return $this->processCustomerRiskSummary(
                        (string)($intentData['risk_level'] ?? ''),
                        (int)($intentData['limit'] ?? 10),
                        isset($intentData['store_id']) && $intentData['store_id'] !== '' ? (int)$intentData['store_id'] : null
                    );
                case 'stock_projection':
                    return $this->processStockProjection(
                        (int)($intentData['days_ahead'] ?? 30),
                        (string)($intentData['sku'] ?? ''),
                        (int)($intentData['limit'] ?? 10)
                    );
                case 'product_performance':
                    return $this->processProductPerformance(
                        (string)($intentData['from_date'] ?? ''),
                        (string)($intentData['to_date'] ?? ''),
                        (int)($intentData['limit'] ?? 10),
                        isset($intentData['store_id']) && $intentData['store_id'] !== '' ? (int)$intentData['store_id'] : null
                    );
                case 'executive_sales_summary':
                    return $this->processExecutiveSalesSummary(
                        (string)($intentData['period'] ?? '30d'),
                        isset($intentData['store_id']) && $intentData['store_id'] !== '' ? (int)$intentData['store_id'] : null
                    );
                case 'inactive_customers':
                    return $this->processInactiveCustomers(
                        (int)($intentData['inactive_days'] ?? 90),
                        (int)($intentData['limit'] ?? 10),
                        isset($intentData['store_id']) && $intentData['store_id'] !== '' ? (int)$intentData['store_id'] : null
                    );
                case 'customer_segment_list':
                    return $this->analyticsIntentService->processCustomerSegmentList([
                        'segment_code' => (string)($intentData['segment_code'] ?? ''),
                        'limit' => (int)($intentData['limit'] ?? 20),
                        'store_id' => isset($intentData['store_id']) && $intentData['store_id'] !== '' ? (int)$intentData['store_id'] : null,
                    ]);
                case 'general_chat':
                default:
                    // Return Gemini's message for general chat or any other intent
                    $message = $intentData['message'] ?? $intentData['reply'] ?? null;
                    
                    if ($message) {
                        return $message;
                    }
                    
                    return 'I could not generate a response. Please try again.';
            }
        } catch (\Throwable $e) {
            $this->logger->error('IntentProcessor error', ['exception' => $e, 'intentData' => $intentData]);
            return 'An error occurred processing your request.';
        }
    }

    /**
     * Get products with stock quantity within a specified range
     *
     * @param int $minQty
     * @param int $maxQty
     * @return string
     */
    private function processLowStock(int $minQty = 0, int $maxQty = 10): string
    {
        try {
            $minQty = max(0, $minQty);
            $maxQty = max(1, $maxQty);

            if ($minQty > $maxQty) {
                [$minQty, $maxQty] = [$maxQty, $minQty];
            }

            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['name', 'sku', 'qty']);
            $collection->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

            $matchedProducts = [];

            foreach ($collection as $product) {
                try {
                    $salableQty = $this->getSalableQty($product->getSku());
                    if ($salableQty >= $minQty && $salableQty <= $maxQty) {
                        $matchedProducts[] = [
                            'name' => $product->getName(),
                            'sku' => $product->getSku(),
                            'qty' => (int) $salableQty
                        ];
                    }
                } catch (\Throwable $e) {
                    $this->logger->warning('Failed to get salable qty for SKU: ' . $product->getSku());
                    continue;
                }
            }

            if (empty($matchedProducts)) {
                return 'No products found with stock quantity between ' . $minQty . ' and ' . $maxQty . '.';
            }

            $response = "Products with Stock Qty Between " . $minQty . " and " . $maxQty . ":\n\n";
            foreach ($matchedProducts as $product) {
                $response .= "• " . $product['name'] . " (SKU: " . $product['sku'] . ") - Qty: " . $product['qty'] . "\n";
            }

            return $response;
        } catch (\Throwable $e) {
            $this->logger->error('Low stock processing error', ['exception' => $e]);
            return 'Unable to retrieve products by stock quantity range.';
        }
    }

    /**
     * Get salable quantity for a specific SKU
     *
     * @return string
     */
    private function processProductQty(string $sku): string
    {
        if (empty($sku)) {
            return 'Please provide a SKU or product ID to check product quantity.';
        }

        try {
            $product = null;
            $lookupValue = trim($sku);

            if (ctype_digit($lookupValue)) {
                try {
                    $product = $this->productRepository->getById((int)$lookupValue);
                } catch (NoSuchEntityException $e) {
                    $product = null;
                }
            }

            if ($product === null) {
                $product = $this->productRepository->get($lookupValue);
            }

            $resolvedSku = (string)$product->getSku();

            if ($product->getTypeId() === Configurable::TYPE_CODE) {
                return $this->buildConfigurableQtyResponse($product);
            }

            return $this->buildSingleSkuQtyResponse((int)$product->getId(), $product->getName(), $resolvedSku);
        } catch (NoSuchEntityException $e) {
            return 'Product with SKU or ID "' . $sku . '" not found.';
        } catch (\Throwable $e) {
            $this->logger->error('Product qty processing error', ['exception' => $e, 'sku' => $sku]);
            return 'Unable to retrieve quantity for SKU/ID: ' . $sku;
        }
    }

    /**
     * @param string $productName
     * @param string $sku
     * @return string
     */
    private function buildSingleSkuQtyResponse(int $productId, string $productName, string $sku): string
    {
        $sourceStock = $this->getSourceStockData($sku);

        $response = "Product: " . $productName . "\n"
            . "SKU: " . $sku . "\n"
            . "Open: " . $this->buildAdminLinkToken('product', $productId, 'Product Page') . "\n"
            . "Type: Simple/Standalone\n\n";

        if (!empty($sourceStock)) {
            $response .= "Stock by Source:\n";
            $totalQty = 0;

            foreach ($sourceStock as $source) {
                $qty = (int)$source['quantity'];
                $response .= "- " . $source['source_code'] . ": " . $qty . "\n";
                $totalQty += $qty;
            }

            $response .= "\nTotal Quantity: " . $totalQty;
        } else {
            $response .= "No stock data found across sources.";
        }

        return $response;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @return string
     */
    private function buildConfigurableQtyResponse($product): string
    {
        $childrenIdsByGroup = $this->configurableType->getChildrenIds((int)$product->getId());
        $childIds = [];

        foreach ($childrenIdsByGroup as $ids) {
            if (is_array($ids)) {
                $childIds = array_merge($childIds, $ids);
            }
        }

        $childIds = array_values(array_unique(array_map('intval', $childIds)));

        if (empty($childIds)) {
            return "Product: " . $product->getName() . "\n"
                . "SKU: " . $product->getSku() . "\n"
                . "Type: Configurable\n\n"
                . "No child products found for this configurable SKU.";
        }

        $children = $this->productCollectionFactory->create();
        $children->addAttributeToSelect(['name', 'sku', 'status']);
        $children->addIdFilter($childIds);

        $rows = [];
        $grandTotalQty = 0;

        foreach ($children as $child) {
            $childSku = (string)$child->getSku();
            $sourceStock = $this->getSourceStockData($childSku);
            $childTotalQty = 0;

            foreach ($sourceStock as $source) {
                $childTotalQty += (int)$source['quantity'];
            }

            $grandTotalQty += $childTotalQty;

            $sourceChunks = [];
            foreach ($sourceStock as $source) {
                $sourceChunks[] = $source['source_code'] . ':' . (int)$source['quantity'];
            }

            $rows[] = [
                'sku' => $childSku,
                'name' => (string)$child->getName(),
                'status' => $this->getProductStatusLabel((int)$child->getStatus()),
                'total_qty' => $childTotalQty,
                'sources' => empty($sourceChunks) ? 'No source rows' : implode(', ', $sourceChunks)
            ];
        }

        usort($rows, function (array $a, array $b): int {
            return strcmp($a['sku'], $b['sku']);
        });

        $response = "Product: " . $product->getName() . "\n"
            . "SKU: " . $product->getSku() . "\n"
            . "Open: " . $this->buildAdminLinkToken('product', (int)$product->getId(), 'Product Page') . "\n"
            . "Type: Configurable\n"
            . "Child Variants (including disabled): " . count($rows) . "\n"
            . "Combined Child Quantity: " . $grandTotalQty . "\n\n";

        foreach ($rows as $index => $row) {
            $response .= ($index + 1) . ") " . $row['sku'] . " [" . $row['status'] . "]\n"
                . "   Name: " . $row['name'] . "\n"
                . "   Total Qty: " . $row['total_qty'] . "\n"
                . "   Sources: " . $row['sources'] . "\n\n";
        }

        return trim($response);
    }

    /**
     * @param int $status
     * @return string
     */
    private function getProductStatusLabel(int $status): string
    {
        return $status === \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
            ? 'Enabled'
            : 'Disabled';
    }

    /**
     * Calculate today's total sales
     *
     * @return string
     */
    private function processSalesToday(): string
    {
        try {
            $today = date('Y-m-d');
            $collection = $this->orderCollectionFactory->create();

            $collection->addFieldToFilter('created_at', [
                'from' => $today . ' 00:00:00',
                'to' => $today . ' 23:59:59'
            ]);
            $collection->addFieldToFilter('state', [
                'nin' => [
                    \Magento\Sales\Model\Order::STATE_CANCELED,
                    \Magento\Sales\Model\Order::STATE_CLOSED
                ]
            ]);

            $totalSales = 0;
            $orderCount = 0;

            foreach ($collection as $order) {
                if ($order->getGrandTotal()) {
                    $totalSales += (float) $order->getGrandTotal();
                    $orderCount++;
                }
            }

            $currencySymbol = $this->getCurrencySymbol();

            return "Sales Summary for " . date('F d, Y') . ":\n\n"
                . "Total Orders: " . $orderCount . "\n"
                . "Total Revenue: " . $currencySymbol . number_format($totalSales, 2);
        } catch (\Throwable $e) {
            $this->logger->error('Sales today processing error', ['exception' => $e]);
            return 'Unable to retrieve today\'s sales data.';
        }
    }

    private function processTrendingProducts(
        int $limit = 10,
        ?string $fromDate = null,
        ?string $toDate = null,
        string $attributeSetsCsv = '',
        string $attributeFiltersRaw = ''
    ): string {
        if ($limit <= 0) $limit = 10;
        if ($limit > 500) $limit = 500;

        [$fromDT, $toDT, $err] = $this->normalizeDateRange($fromDate, $toDate, '-3 months');
        if ($err !== null) {
            return $err;
        }

        $from             = date('Y-m-d', strtotime($fromDT));
        $to               = date('Y-m-d', strtotime($toDT));
        $attributeSets    = $this->parseCsvValues($attributeSetsCsv);
        $attributeFilters = $this->parseKeyValueFilters($attributeFiltersRaw);

        // limit is intentionally excluded from the hash so queries with different
        // limits but the same filters can share per-day cache.
        $paramHash = $this->dayCacheService->makeParamHash([
            'attribute_sets'    => implode(',', $attributeSets),
            'attribute_filters' => $this->formatFilters($attributeFilters),
        ]);
        $paramSnap = json_encode([
            'attribute_sets'    => $attributeSets,
            'attribute_filters' => $attributeFilters,
        ]);

        $allRaw  = $this->resolveDayCache(
            'trending_products',
            $from,
            $to,
            $paramHash,
            $paramSnap,
            function (array $days) use ($attributeSets, $attributeFilters) {
                return $this->fetchTrendingRawByDays($days, $attributeSets, $attributeFilters);
            }
        );

        $trendingProducts = $this->aggregateTrendingDaily($allRaw, $limit);

        if (empty($trendingProducts)) {
            return 'No trending products found for ' . $from . ' to ' . $to
                . '. (Default range is last 3 months when dates are omitted.)';
        }

        $response = "Top {$limit} Trending Products ({$from} to {$to}):\n\n";
        if (!empty($attributeSets) || !empty($attributeFilters)) {
            $response .= "Filters Applied:\n";
            $response .= '- Attribute Sets: ' . (empty($attributeSets) ? '[none]' : implode(', ', $attributeSets)) . "\n";
            $response .= '- Attribute Filters: ' . (empty($attributeFilters) ? '[none]' : $this->formatFilters($attributeFilters)) . "\n\n";
        }

        $rank = 1;
        foreach ($trendingProducts as $p) {
            $response .= $rank++ . '. ' . $p['name'] . ' (SKU: ' . $p['sku'] . ') - Units Sold: ' . $p['qty']
                . ' ' . $this->buildAdminLinkToken('product', (int)$p['product_id'], 'open') . "\n";
        }

        return $response;
    }

    /**
     * Get salable quantity for SKU
     *
     * @param string $sku
     * @return float
     */
    private function getSalableQty(string $sku): float
    {
        try {
            $data = $this->salableQuantity->execute($sku);
            if (!empty($data)) {
                return $data[0]['qty'] ?? 0;
            }
            return 0;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to get salable qty for SKU: ' . $sku);
            return 0;
        }
    }

    /**
     * Get stock data from all sources for a SKU
     *
     * @param string $sku
     * @return array
     */
    private function getSourceStockData(string $sku): array
    {
        try {
            $collection = $this->sourceItemCollectionFactory->create();
            $collection->addFieldToFilter('sku', $sku);

            $sourceStock = [];
            foreach ($collection as $item) {
                $sourceStock[] = [
                    'source_code' => $item->getSourceCode(),
                    'quantity' => $item->getQuantity(),
                    'status' => $item->getStatus()
                ];
            }

            return $sourceStock;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to get source stock data for SKU: ' . $sku, ['exception' => $e]);
            return [];
        }
    }

    /**
     * Get store currency symbol
     *
     * @return string
     */
    private function getCurrencySymbol(): string
    {
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
            $currencyCode = $storeManager->getStore()->getCurrentCurrency()->getCode();

            $currencyFactory = $objectManager->get(\Magento\Framework\Locale\CurrencyFactory::class);
            $currency = $currencyFactory->create($currencyCode);
            return $currency->getSymbol();
        } catch (\Throwable $e) {
            return '₹';
        }
    }

    /**
     * @param string $skuOrId
     * @param string $attributesCsv
     * @return string
     */
    private function processCatalogProductData(string $skuOrId, string $attributesCsv): string
    {
        if ($skuOrId === '') {
            return 'Please provide SKU or product ID. Use: catalog_product_data GOLTEMXN100SP name,price,status';
        }

        try {
            $product = ctype_digit($skuOrId)
                ? $this->productRepository->getById((int)$skuOrId)
                : $this->productRepository->get($skuOrId);
        } catch (\Throwable $e) {
            return 'Product not found for: ' . $skuOrId;
        }

        $codes = $this->parseCsvValues($attributesCsv);
        if (empty($codes)) {
            $codes = ['name', 'sku', 'price', 'status', 'visibility', 'type_id', 'attribute_set_id'];
        }

        $lines = [
            'Product Data:',
            'ID: ' . (int)$product->getId(),
            'SKU: ' . (string)$product->getSku(),
            'Open: ' . $this->buildAdminLinkToken('product', (int)$product->getId(), 'Product Page')
        ];

        foreach ($codes as $code) {
            $value = $product->getData($code);
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            if ($value === null || $value === '') {
                $value = '[empty]';
            }
            $lines[] = $code . ': ' . (string)$value;
        }

        return implode("\n", $lines);
    }

    /**
     * @param string $nameQuery
     * @return string
     */
    private function processCategorySearchByName(string $nameQuery): string
    {
        $nameQuery = trim($nameQuery);
        if ($nameQuery === '') {
            return 'Please provide category name. Use: category_search_by_name balls';
        }

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $collectionFactory = $objectManager->get(\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory::class);
            $storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);

            $collection = $collectionFactory->create();
            $collection->addAttributeToSelect(['name', 'path'])
                ->addAttributeToFilter('name', ['like' => '%' . $nameQuery . '%'])
                ->setPageSize(20);

            if ($collection->getSize() === 0) {
                return 'No categories found for: ' . $nameQuery;
            }

            $store = $storeManager->getStore();
            $lines = ['Matching Categories for "' . $nameQuery . '":'];
            foreach ($collection as $category) {
                $lines[] = 'ID: ' . (int)$category->getId();
                $lines[] = 'Name: ' . $category->getName();
                $lines[] = 'Path: ' . $this->buildReadableCategoryPath((string)$category->getPath());
                try {
                    $lines[] = 'URL: ' . $category->setStore($store)->getUrl();
                } catch (\Throwable $e) {
                    // Skip URL failures for invalid store/category states.
                }
                $lines[] = '[[SEP]]';
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            $this->logger->error('Category search error', ['exception' => $e]);
            return 'Unable to search categories right now.';
        }
    }

    /**
     * @param int $categoryId
     * @param string $attributesCsv
     * @return string
     */
    private function processCategoryDataById(int $categoryId, string $attributesCsv): string
    {
        if ($categoryId <= 0) {
            return 'Please provide category ID. Use: category_data_by_id 12 description,page_layout';
        }

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $categoryRepository = $objectManager->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
            $category = $categoryRepository->get($categoryId);
        } catch (\Throwable $e) {
            return 'Category not found for ID: ' . $categoryId;
        }

        $codes = $this->parseCsvValues($attributesCsv);
        if (empty($codes)) {
            $codes = ['name', 'description', 'is_active', 'url_key', 'page_layout', 'custom_theme'];
        }

        $lines = [
            'Category Data:',
            'ID: ' . (int)$category->getId(),
            'Name: ' . (string)$category->getName()
        ];

        foreach ($codes as $code) {
            $value = $category->getData($code);
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            if ($value === null || $value === '') {
                $value = '[empty]';
            }
            $lines[] = $code . ': ' . (string)$value;
        }

        return implode("\n", $lines);
    }

    /**
     * Convert a category path of IDs (e.g. 1/2/99) into readable names.
     *
     * @param string $path
     * @return string
     */
    private function buildReadableCategoryPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '[empty]';
        }

        $ids = array_values(array_filter(array_map('intval', explode('/', $path))));
        if (empty($ids)) {
            return $path;
        }

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $collectionFactory = $objectManager->get(\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory::class);

            $collection = $collectionFactory->create();
            $collection->addAttributeToSelect(['name'])
                ->addIdFilter($ids);

            $nameById = [];
            foreach ($collection as $category) {
                $nameById[(int)$category->getId()] = trim((string)$category->getName());
            }

            $parts = [];
            foreach ($ids as $id) {
                if (!empty($nameById[$id])) {
                    $parts[] = $nameById[$id];
                }
            }

            if (!empty($parts)) {
                return implode(' / ', $parts);
            }
        } catch (\Throwable $e) {
            // Fall back to raw ID path on lookup failure.
        }

        return $path;
    }

    /**
     * @param int $limit
     * @return string
     */
    private function processReservedProducts(int $limit = 10, string $sku = ''): string
    {
        $sku = trim($sku);

        if ($limit <= 0) {
            $limit = 10;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        try {
            $collection = $this->sourceItemCollectionFactory->create();

            if ($sku !== '') {
                $collection->addFieldToFilter('sku', $sku);
                $collection->getSelect()
                    ->columns(['reserved_qty' => new \Zend_Db_Expr('SUM(quantity)')])
                    ->group('sku');
            } else {
                $collection->getSelect()
                    ->columns(['reserved_qty' => new \Zend_Db_Expr('SUM(quantity)')])
                    ->group('sku')
                    ->order('reserved_qty DESC')
                    ->limit($limit);
            }

            $rows = [];
            foreach ($collection as $item) {
                $rows[] = [
                    'sku' => (string)$item->getSku(),
                    'qty' => (float)$item->getData('reserved_qty')
                ];
            }

            if (empty($rows)) {
                if ($sku !== '') {
                    return 'No reserved inventory found for SKU: ' . $sku;
                }
                return 'No reserved products data found.';
            }

            if ($sku !== '') {
                $lines = ['Reserved Inventory for SKU: ' . $sku];
            } else {
                $lines = ['Top Reserved Products (Limit ' . $limit . '):'];
            }

            foreach ($rows as $i => $row) {
                $lines[] = ($i + 1) . '. SKU: ' . $row['sku'] . ' | Reserved Qty: ' . number_format($row['qty'], 2);
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            $this->logger->error('Reserved products error', ['exception' => $e]);
            return 'Unable to fetch reserved products right now.';
        }
    }

    private function processSalesOnDate(string $date): string
    {
        $date = trim($date);
        if (!$this->isValidDate($date)) {
            return 'Please provide a valid date (YYYY-MM-DD). Use: sales_on_date 2026-03-20';
        }

        $paramHash = $this->dayCacheService->makeParamHash([]);
        $allRaw    = $this->resolveDayCache(
            'sales_summary',
            $date,
            $date,
            $paramHash,
            '{}',
            function (array $days) {
                return $this->fetchSalesSummaryRawByDays($days);
            }
        );

        ['order_count' => $count, 'revenue' => $revenue] = $this->aggregateSalesDaily($allRaw);
        $currency = $this->getCurrencySymbol();

        return 'Sales Summary for ' . $date . ":\n\n"
            . 'Total Orders: ' . $count . "\n"
            . 'Total Revenue: ' . $currency . number_format($revenue, 2) . "\n"
            . 'Orders: ' . $this->buildAdminLinkToken('orders', 0, 'Open Orders Grid');
    }

    private function processSalesDateRange(string $fromDate, string $toDate): string
    {
        [$fromDT, $toDT, $err] = $this->normalizeDateRange($fromDate, $toDate, '-3 months');
        if ($err !== null) {
            return $err;
        }

        $from = date('Y-m-d', strtotime($fromDT));
        $to   = date('Y-m-d', strtotime($toDT));

        $paramHash = $this->dayCacheService->makeParamHash([]);
        $allRaw    = $this->resolveDayCache(
            'sales_summary',
            $from,
            $to,
            $paramHash,
            '{}',
            function (array $days) {
                return $this->fetchSalesSummaryRawByDays($days);
            }
        );

        ['order_count' => $count, 'revenue' => $revenue] = $this->aggregateSalesDaily($allRaw);
        $currency = $this->getCurrencySymbol();
        $title    = 'Sales Summary (' . $from . ' to ' . $to . ')';

        return $title . ":\n\n"
            . 'Total Orders: ' . $count . "\n"
            . 'Total Revenue: ' . $currency . number_format($revenue, 2) . "\n"
            . 'Orders: ' . $this->buildAdminLinkToken('orders', 0, 'Open Orders Grid');
    }

    private function processSalesFiltered(
        string $fromDate,
        string $toDate,
        string $attributeSetsCsv,
        string $attributeFiltersRaw
    ): string {
        [$fromDT, $toDT, $err] = $this->normalizeDateRange($fromDate, $toDate, '-3 months');
        if ($err !== null) {
            return $err;
        }

        $from          = date('Y-m-d', strtotime($fromDT));
        $to            = date('Y-m-d', strtotime($toDT));
        $attributeSets = $this->parseCsvValues($attributeSetsCsv);
        $parsedFilters = $this->parseKeyValueFilters($attributeFiltersRaw);

        $paramHash = $this->dayCacheService->makeParamHash([
            'attribute_sets'    => implode(',', $attributeSets),
            'attribute_filters' => $this->formatFilters($parsedFilters),
        ]);
        $paramSnap = json_encode([
            'attribute_sets'    => $attributeSets,
            'attribute_filters' => $parsedFilters,
        ]);

        $allRaw = $this->resolveDayCache(
            'sales_filtered',
            $from,
            $to,
            $paramHash,
            $paramSnap,
            function (array $days) use ($attributeSets, $parsedFilters) {
                return $this->fetchSalesFilteredRawByDays($days, $attributeSets, $parsedFilters);
            }
        );

        ['orders' => $allOrders, 'total_qty' => $totalQty, 'total_revenue' => $totalRevenue]
            = $this->aggregateSalesFiltered($allRaw);

        if (empty($allOrders)) {
            return implode("\n", [
                'No matching sales found for selected filters.',
                'Date Range: ' . $from . ' to ' . $to,
                'Attribute Sets: ' . (empty($attributeSets) ? '[none]' : implode(', ', $attributeSets)),
                'Attribute Filters: ' . (empty($parsedFilters) ? '[none]' : $this->formatFilters($parsedFilters)),
            ]);
        }

        $currency = $this->getCurrencySymbol();
        $lines    = [
            'Sales Summary with Filters (' . $from . ' to ' . $to . '):',
            '',
            'Matched Orders: ' . count($allOrders),
            'Matched Quantity: ' . number_format($totalQty, 2),
            'Matched Revenue: ' . $currency . number_format($totalRevenue, 2),
            'Orders: ' . $this->buildAdminLinkToken('orders', 0, 'Open Orders Grid'),
            '',
            'Applied Attribute Sets: ' . (empty($attributeSets) ? '[none]' : implode(', ', $attributeSets)),
            'Applied Attribute Filters: ' . (empty($parsedFilters) ? '[none]' : $this->formatFilters($parsedFilters)),
            '',
            'Matching Orders:',
        ];

        foreach ($allOrders as $row) {
            $lines[] = '- Order #' . ($row['increment_id'] !== '' ? $row['increment_id'] : (string)$row['entity_id'])
                . ' | Date: ' . date('Y-m-d', strtotime((string)$row['created_at']))
                . ' | Qty: ' . number_format((float)$row['qty'], 2)
                . ' | Amount: ' . $currency . number_format((float)$row['amount'], 2)
                . ' ' . $this->buildAdminLinkToken('order', (int)$row['entity_id'], 'open');
        }

        return implode("\n", $lines);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null $product
     * @param array $attributeSets
     * @param array<string,string> $attributeFilters
     * @return bool
     */
    private function orderItemMatchesSalesFilters($item, $product, array $attributeSets, array $attributeFilters): bool
    {
        if (!empty($attributeSets)) {
            $setName = '';
            if ($product !== null) {
                $setName = $this->getAttributeSetName((int)$product->getAttributeSetId());
            }

            $setNameLower = $this->normalizeFilterToken($setName);
            $setMatch = false;

            foreach ($attributeSets as $requiredSet) {
                $requiredSetNormalized = $this->normalizeFilterToken($requiredSet);
                if ($requiredSetNormalized === '') {
                    continue;
                }

                if (
                    ($setNameLower !== '' && strpos($setNameLower, $requiredSetNormalized) !== false)
                    || ($setNameLower !== '' && strpos($requiredSetNormalized, $setNameLower) !== false)
                    || ($setNameLower !== '' && rtrim($setNameLower, 's') === rtrim($requiredSetNormalized, 's'))
                ) {
                    $setMatch = true;
                    break;
                }
            }

            if (!$setMatch) {
                return false;
            }
        }

        foreach ($attributeFilters as $attributeCode => $expectedValue) {
            if (!$this->orderItemMatchesAttributeFilter($item, $product, (string)$attributeCode, (string)$expectedValue)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product|null $product
     * @param string $attributeCode
     * @param string $expectedValue
     * @return bool
     */
    private function orderItemMatchesAttributeFilter($item, $product, string $attributeCode, string $expectedValue): bool
    {
        $needle = $this->normalizeFilterToken($expectedValue);
        if ($needle === '') {
            return false;
        }

        $candidateValues = [];

        if ($product !== null) {
            $rawValue = $product->getData($attributeCode);
            $displayValue = $product->getAttributeText($attributeCode);

            $candidateValues[] = is_array($rawValue) ? implode(',', $rawValue) : (string)$rawValue;
            $candidateValues[] = is_array($displayValue) ? implode(',', $displayValue) : (string)$displayValue;
        }

        $options = $item->getProductOptions();
        if (is_array($options) && !empty($options['attributes_info']) && is_array($options['attributes_info'])) {
            $normalizedCode = $this->normalizeFilterToken($attributeCode);
            foreach ($options['attributes_info'] as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $label = $this->normalizeFilterToken((string)($row['label'] ?? ''));
                $value = (string)($row['value'] ?? '');

                if ($label === '') {
                    continue;
                }

                if (
                    $label === $normalizedCode
                    || strpos($label, $normalizedCode) !== false
                    || strpos($normalizedCode, $label) !== false
                ) {
                    $candidateValues[] = $value;
                }
            }
        }

        foreach ($candidateValues as $candidate) {
            $haystack = $this->normalizeFilterToken((string)$candidate);
            if ($haystack !== '' && strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function processReviewsRecent(int $days = 10, int $limit = 10): string
    {
        $days  = max(1, min($days, 365));
        $limit = max(1, min($limit, 500));

        $to   = $this->dayCacheService->getToday();
        $from = date('Y-m-d', strtotime('-' . $days . ' days'));

        // limit is NOT in the hash — per-day raw stores all reviews; limit is applied at aggregation.
        $paramHash = $this->dayCacheService->makeParamHash([]);

        $allRaw  = $this->resolveDayCache(
            'reviews_recent',
            $from,
            $to,
            $paramHash,
            '{}',
            function (array $daysToFetch) {
                return $this->fetchReviewsRawByDays($daysToFetch);
            }
        );

        $reviews = $this->aggregateReviewsDaily($allRaw, $limit);

        if (empty($reviews)) {
            return 'No reviews found in the last ' . $days . ' day(s).';
        }

        $lines = ['Recent Reviews — last ' . $days . ' day(s) (showing ' . count($reviews) . '):'];
        foreach ($reviews as $row) {
            $stars  = $row['rating_summary'] !== null ? round((float)$row['rating_summary'] / 20, 1) . '/5' : 'N/A';
            $sku    = $row['sku'] ?? 'Product ID ' . $row['entity_pk_value'];
            $name   = $row['nickname'] ?: 'Guest';
            $date   = date('d M Y', strtotime($row['created_at']));
            $title  = $row['title'] ?: '(no title)';
            $detail = mb_strimwidth((string)($row['detail'] ?? ''), 0, 120, '...');
            $lines[] = '[[SEP]]';
            $lines[] = 'SKU: ' . $sku . ' | Rating: ' . $stars . ' | By: ' . $name . ' | ' . $date;
            $lines[] = 'Title: ' . $title;
            $lines[] = $detail;
        }

        return implode("\n", $lines);
    }

    /**
     * @param int $limit
     * @return string
     */
    private function processProductsMostReviews(int $limit = 10): string
    {
        $limit = max(1, min($limit, 500));

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $connection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

            $reviewTable  = $connection->getTableName('review');
            $productTable = $connection->getTableName('catalog_product_entity');
            $varcharTable = $connection->getTableName('catalog_product_entity_varchar');

            $select = $connection->select()
                ->from(['r' => $reviewTable], ['entity_pk_value', 'review_count' => new \Zend_Db_Expr('COUNT(*)')])
                ->joinLeft(['cpe' => $productTable], 'cpe.entity_id = r.entity_pk_value', ['sku'])
                ->where('r.entity_id = 1')
                ->group('r.entity_pk_value')
                ->order('review_count DESC')
                ->limit($limit);

            $rows = $connection->fetchAll($select);

            if (empty($rows)) {
                return 'No review data found.';
            }

            // Fetch product names in one query
            $productIds = array_column($rows, 'entity_pk_value');
            $nameAttrId = (int)$connection->fetchOne(
                "SELECT attribute_id FROM " . $connection->getTableName('eav_attribute')
                . " WHERE entity_type_id = 4 AND attribute_code = 'name' LIMIT 1"
            );
            $nameMap = [];
            if ($nameAttrId && $productIds) {
                $nameRows = $connection->fetchAll(
                    $connection->select()
                        ->from($varcharTable, ['entity_id', 'value'])
                        ->where('attribute_id = ?', $nameAttrId)
                        ->where('entity_id IN (?)', $productIds)
                        ->where('store_id = 0')
                );
                foreach ($nameRows as $nr) {
                    $nameMap[(int)$nr['entity_id']] = $nr['value'];
                }
            }

            $lines = ['Products with Most Reviews (top ' . count($rows) . '):'];
            foreach ($rows as $i => $row) {
                $pid  = (int)$row['entity_pk_value'];
                $name = $nameMap[$pid] ?? ('Product ID ' . $pid);
                $sku  = $row['sku'] ?? '-';
                $cnt  = (int)$row['review_count'];
                $lines[] = ($i + 1) . '. ' . $name . ' | SKU: ' . $sku . ' | Reviews: ' . $cnt
                    . ' ' . $this->buildAdminLinkToken('product', $pid, '✎');
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            $this->logger->error('Products most reviews error', ['exception' => $e]);
            return 'Unable to fetch products most reviews right now.';
        }
    }

    /**
     * @param int $limit
     * @return string
     */
    private function processProductsHighestReviews(int $limit = 10): string
    {
        $limit = max(1, min($limit, 500));

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $connection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

            $summaryTable = $connection->getTableName('review_entity_summary');
            $productTable = $connection->getTableName('catalog_product_entity');
            $varcharTable = $connection->getTableName('catalog_product_entity_varchar');

            // Join summary (avg rating) with product, require at least 1 review
            $select = $connection->select()
                ->from(['rs' => $summaryTable], ['entity_pk_value', 'rating_summary', 'reviews_count'])
                ->joinLeft(['cpe' => $productTable], 'cpe.entity_id = rs.entity_pk_value', ['sku'])
                ->where('rs.entity_type = 1')
                ->where('rs.store_id = 1')
                ->where('rs.reviews_count > 0')
                ->order('rs.rating_summary DESC')
                ->order('rs.reviews_count DESC')
                ->limit($limit);

            $rows = $connection->fetchAll($select);

            if (empty($rows)) {
                return 'No rated products found.';
            }

            // Fetch product names
            $productIds = array_column($rows, 'entity_pk_value');
            $nameAttrId = (int)$connection->fetchOne(
                "SELECT attribute_id FROM " . $connection->getTableName('eav_attribute')
                . " WHERE entity_type_id = 4 AND attribute_code = 'name' LIMIT 1"
            );
            $nameMap = [];
            if ($nameAttrId && $productIds) {
                $nameRows = $connection->fetchAll(
                    $connection->select()
                        ->from($varcharTable, ['entity_id', 'value'])
                        ->where('attribute_id = ?', $nameAttrId)
                        ->where('entity_id IN (?)', $productIds)
                        ->where('store_id = 0')
                );
                foreach ($nameRows as $nr) {
                    $nameMap[(int)$nr['entity_id']] = $nr['value'];
                }
            }

            $lines = ['Highest Rated Products (top ' . count($rows) . '):'];
            foreach ($rows as $i => $row) {
                $pid    = (int)$row['entity_pk_value'];
                $name   = $nameMap[$pid] ?? ('Product ID ' . $pid);
                $sku    = $row['sku'] ?? '-';
                $stars  = round((float)$row['rating_summary'] / 20, 1);
                $cnt    = (int)$row['reviews_count'];
                $lines[] = ($i + 1) . '. ' . $name . ' | SKU: ' . $sku
                    . ' | Rating: ' . $stars . '/5 (' . $cnt . ' review' . ($cnt !== 1 ? 's' : '') . ')'
                    . ' ' . $this->buildAdminLinkToken('product', $pid, '✎');
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            $this->logger->error('Products highest reviews error', ['exception' => $e]);
            return 'Unable to fetch highest rated products right now.';
        }
    }

    /**
     * @param string $query
     * @return string
     */
    private function processCustomerSearch(string $query): string
    {
        $query = trim($query);
        if ($query === '') {
            return 'Please provide a customer query. Use: customer_search mohit makhija';
        }

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $collectionFactory = $objectManager->get(\Magento\Customer\Model\ResourceModel\Customer\CollectionFactory::class);

            $collection = $collectionFactory->create();
            $collection->addNameToSelect();
            $collection->addAttributeToSelect(['firstname', 'lastname', 'email', 'created_at']);
            $collection->addAttributeToFilter([
                ['attribute' => 'firstname', 'like' => '%' . $query . '%'],
                ['attribute' => 'lastname', 'like' => '%' . $query . '%'],
                ['attribute' => 'email', 'like' => '%' . $query . '%']
            ]);
            $collection->setPageSize(20);

            if ($collection->getSize() === 0) {
                return 'No customers found for query: ' . $query;
            }

            $lines = ['Customer matches for "' . $query . '" (top 20):'];
            foreach ($collection as $customer) {
                $customerId = (int)$customer->getId();
                $name = trim((string)$customer->getFirstname() . ' ' . (string)$customer->getLastname());
                $lines[] = '- ID: ' . $customerId
                    . ' | Name: ' . ($name === '' ? '[empty]' : $name)
                    . ' | Email: ' . (string)$customer->getEmail()
                    . ' ' . $this->buildAdminLinkToken('customer', $customerId, 'open')
                    . ' [[CMD:customer_details ' . $customerId . '|Get details for customer ' . $customerId . ']]';
            }

            if ($collection->getSize() > 1) {
                $lines[] = 'Please use customer_details {customer_id} for exact record.';
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            $this->logger->error('Customer search error', ['exception' => $e, 'query' => $query]);
            return 'Unable to search customers right now.';
        }
    }

    /**
     * @param int $customerId
     * @param string $attributesCsv
     * @return string
     */
    private function processCustomerDetails(int $customerId, string $attributesCsv): string
    {
        if ($customerId <= 0) {
            return 'Please provide customer ID. Use: customer_details 123 firstname,lastname,email';
        }

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerRepository = $objectManager->get(\Magento\Customer\Api\CustomerRepositoryInterface::class);
            $customer = $customerRepository->getById($customerId);
        } catch (\Throwable $e) {
            return 'Customer not found for ID: ' . $customerId;
        }

        $attrs = $this->parseCsvValues($attributesCsv);
        if (empty($attrs)) {
            $attrs = [
                'firstname', 'lastname', 'email', 'group_id',
                'website_id', 'store_id', 'created_at', 'dob', 'gender'
            ];
        }

        $baseValues = [
            'entity_id' => (int)$customer->getId(),
            'firstname' => (string)$customer->getFirstname(),
            'lastname' => (string)$customer->getLastname(),
            'email' => (string)$customer->getEmail(),
            'group_id' => (string)$customer->getGroupId(),
            'website_id' => (string)$customer->getWebsiteId(),
            'store_id' => (string)$customer->getStoreId(),
            'created_at' => (string)$customer->getCreatedAt(),
            'dob' => (string)$customer->getDob(),
            'gender' => (string)$customer->getGender(),
            'taxvat' => (string)$customer->getTaxvat(),
            'addresses_count' => (string)count($customer->getAddresses())
        ];

        $customAttributes = [];
        foreach ((array)$customer->getCustomAttributes() as $attribute) {
            $customAttributes[$attribute->getAttributeCode()] = $attribute->getValue();
        }

        $lines = [
            'Customer Details:',
            'ID: ' . (int)$customer->getId(),
            'Name: ' . trim((string)$customer->getFirstname() . ' ' . (string)$customer->getLastname()),
            'Open: ' . $this->buildAdminLinkToken('customer', (int)$customer->getId(), 'Customer Page')
        ];

        foreach ($attrs as $code) {
            if ($code === 'id') {
                $code = 'entity_id';
            }

            $value = null;
            if (array_key_exists($code, $baseValues)) {
                $value = $baseValues[$code];
            } elseif (array_key_exists($code, $customAttributes)) {
                $value = $customAttributes[$code];
            }

            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            if ($value === null || $value === '') {
                $value = '[not found or empty]';
            }

            $lines[] = $code . ': ' . (string)$value;
        }

        return implode("\n", $lines);
    }

    /**
     * @param int $customerId
     * @return string
     */
    private function processCustomerAddresses(int $customerId): string
    {
        if ($customerId <= 0) {
            return 'Please provide customer ID. Use: customer_addresses 123';
        }

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customerRepository = $objectManager->get(\Magento\Customer\Api\CustomerRepositoryInterface::class);
            $customer = $customerRepository->getById($customerId);
        } catch (\Throwable $e) {
            return 'Customer not found for ID: ' . $customerId;
        }

        $addresses = (array)$customer->getAddresses();
        if (empty($addresses)) {
            return 'No addresses found for customer ID: ' . $customerId;
        }

        $lines = ['Addresses for Customer ID ' . $customerId . ':'];
        $lines[] = 'Open: ' . $this->buildAdminLinkToken('customer', $customerId, 'Customer Page');
        foreach ($addresses as $idx => $address) {
            $street = $address->getStreet();
            $streetText = is_array($street) ? implode(', ', $street) : (string)$street;

            $lines[] = ($idx + 1) . '. ' . trim((string)$address->getFirstname() . ' ' . (string)$address->getLastname());
            $lines[] = '   Street: ' . ($streetText === '' ? '[empty]' : $streetText);
            $lines[] = '   City: ' . (string)$address->getCity();
            $lines[] = '   Region: ' . (string)$address->getRegion()->getRegion();
            $lines[] = '   Country: ' . (string)$address->getCountryId();
            $lines[] = '   Postcode: ' . (string)$address->getPostcode();
            $lines[] = '   Telephone: ' . (string)$address->getTelephone();
            $lines[] = '[[SEP]]';
        }

        return implode("\n", $lines);
    }

    /**
     * @param string $city
     * @param string $region
     * @param string $country
     * @param int $limit
     * @return string
     */
    private function processCustomersByLocation(string $city, string $region, string $country, int $limit = 20): string
    {
        $limit = max(1, min($limit, 500));
        $city = trim($city);
        $region = trim($region);
        $country = strtoupper(trim($country));

        if ($city === '' && $region === '' && $country === '') {
            return 'Please provide at least one location filter (city, region, or country).';
        }

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $addressCollectionFactory = $objectManager->get(\Magento\Customer\Model\ResourceModel\Address\CollectionFactory::class);

            $collection = $addressCollectionFactory->create();
            $collection->addAttributeToSelect(['firstname', 'lastname', 'city', 'region', 'region_id', 'country_id', 'postcode']);

            if ($city !== '') {
                $collection->addAttributeToFilter('city', ['like' => '%' . $city . '%']);
            }
            if ($country !== '') {
                $collection->addAttributeToFilter('country_id', ['eq' => $country]);
            }

            // Keep a reasonable upper bound, then apply exact region filtering in PHP.
            $collection->setPageSize(1000);

            $matched = [];
            $regionNeedle = strtolower($region);

            foreach ($collection as $address) {
                if ($region !== '') {
                    $regionText = strtolower(trim((string)$address->getRegion()));
                    $regionId = trim((string)$address->getData('region_id'));

                    $matchesRegionText = $regionText !== '' && strpos($regionText, $regionNeedle) !== false;
                    $matchesRegionId = ctype_digit($region) && $regionId !== '' && (int)$regionId === (int)$region;

                    if (!$matchesRegionText && !$matchesRegionId) {
                        continue;
                    }
                }

                $matched[] = $address;
                if (count($matched) >= $limit) {
                    break;
                }
            }

            if (empty($matched)) {
                return 'No customer addresses found for the given location filters.';
            }

            $lines = ['Customers by Location (limit ' . $limit . '):'];
            foreach ($matched as $address) {
                $lines[] = '- Customer ID: ' . (int)$address->getCustomerId()
                    . ' | Name: ' . trim((string)$address->getFirstname() . ' ' . (string)$address->getLastname())
                    . ' | City: ' . (string)$address->getCity()
                    . ' | Region: ' . (string)$address->getRegion()
                    . ' | Country: ' . (string)$address->getCountryId();
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            $this->logger->error('Customers by location error', ['exception' => $e]);
            return 'Unable to fetch customers by location right now.';
        }
    }
    // =========================================================================
    // Day-Cache orchestration
    // =========================================================================

    /**
     * Core orchestration method for all date-range intents.
     *
     * 1. Partitions the range into "closed" days (< today) and "flexible" days (>= today).
     * 2. Loads already-cached closed days from DB.
     * 3. Calls $fetcher only for missing closed days + flexible days in one batch.
     * 4. Persists the newly fetched closed-day results to cache.
     * 5. Returns merged raw data keyed by 'Y-m-d'.
     *
     * @param callable $fetcher  function(string[] $days): array<string, array>
     * @return array<string, array>
     */
    private function resolveDayCache(
        string $intentCode,
        string $from,
        string $to,
        string $paramHash,
        string $paramSnapshot,
        callable $fetcher
    ): array {
        $today   = $this->dayCacheService->getToday();
        $allDays = $this->dayCacheService->getDaysInRange($from, $to);

        if (empty($allDays)) {
            return [];
        }

        $closedDays   = array_values(array_filter($allDays, function ($d) use ($today) { return $d < $today; }));
        $flexibleDays = array_values(array_filter($allDays, function ($d) use ($today) { return $d >= $today; }));

        // Retrieve already-cached closed-day entries from module table.
        $cached = $this->dayCacheService->getCachedDays($intentCode, $paramHash, $from, $to);

        // Closed days not yet in cache.
        $missingClosed = array_values(array_diff($closedDays, array_keys($cached)));

        // Fetch uncached closed days + flexible days (today / future) from live DB.
        $daysToFetch = array_merge($missingClosed, $flexibleDays);
        $fetched     = [];

        if (!empty($daysToFetch)) {
            $fetched = $fetcher($daysToFetch);
        }

        // Persist newly fetched closed days — flexible days (today) are never cached.
        foreach ($missingClosed as $closedDay) {
            $this->dayCacheService->saveDay(
                $intentCode,
                $closedDay,
                $paramHash,
                $paramSnapshot,
                $fetched[$closedDay] ?? []
            );
        }

        // Merge: cached results + freshly fetched results.
        $result = $cached + $fetched;

        // Ensure every requested day has an entry (empty = no orders/data that day).
        foreach ($allDays as $day) {
            if (!isset($result[$day])) {
                $result[$day] = [];
            }
        }

        ksort($result);
        return $result;
    }

    // =========================================================================
    // Per-day DB fetchers
    // =========================================================================

    /**
     * Query sales_order for the supplied days and return per-day totals.
     * Uses DATE(created_at) IN (...) so only the necessary days hit the DB.
     *
     * @param  string[] $days 'Y-m-d' list
     * @return array<string, array{order_count:int, revenue:float}>
     */
    private function fetchSalesSummaryRawByDays(array $days): array
    {
        $result = [];
        foreach ($days as $day) {
            $result[$day] = ['order_count' => 0, 'revenue' => 0.0];
        }

        if (empty($days)) {
            return $result;
        }

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $conn  = $objectManager->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
            $table = $conn->getTableName('sales_order');

            $select = $conn->select()
                ->from($table, [
                    'day'         => new \Zend_Db_Expr('DATE(created_at)'),
                    'order_count' => new \Zend_Db_Expr('COUNT(*)'),
                    'revenue'     => new \Zend_Db_Expr('SUM(grand_total)'),
                ])
                ->where('DATE(created_at) IN (?)', $days)
                ->where('state NOT IN (?)', [
                    \Magento\Sales\Model\Order::STATE_CANCELED,
                    \Magento\Sales\Model\Order::STATE_CLOSED,
                ])
                ->group(new \Zend_Db_Expr('DATE(created_at)'));

            foreach ($conn->fetchAll($select) as $row) {
                $result[(string)$row['day']] = [
                    'order_count' => (int)$row['order_count'],
                    'revenue'     => (float)$row['revenue'],
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('fetchSalesSummaryRawByDays error', ['exception' => $e]);
            return $result;
        }
    }

    /**
     * Query order items for a list of days, GROUP BY day+product_id,
     * then PHP-filter by attribute_sets / attribute_filters.
     * No LIMIT is applied — limit is enforced at aggregation time.
     *
     * @param  string[]             $days
     * @param  string[]             $attributeSets
     * @param  array<string,string> $attributeFilters
     * @return array<string, array[]>   per-day list of {product_id, name, sku, qty}
     */
    private function fetchTrendingRawByDays(array $days, array $attributeSets, array $attributeFilters): array
    {
        $result = [];
        foreach ($days as $day) {
            $result[$day] = [];
        }

        if (empty($days)) {
            return $result;
        }

        try {
            $collection = $this->orderItemCollectionFactory->create();

            $collection->getSelect()
                ->joinLeft(
                    ['o' => $collection->getTable('sales_order')],
                    'main_table.order_id = o.entity_id',
                    []
                )
                ->where('DATE(o.created_at) IN (?)', $days)
                ->where('o.state NOT IN (?)', [
                    \Magento\Sales\Model\Order::STATE_CANCELED,
                    \Magento\Sales\Model\Order::STATE_CLOSED,
                ]);

            // SQL-level keyword filter for attribute_sets (faster, reduces PHP work).
            if (!empty($attributeSets)) {
                $nameConditions = [];
                foreach ($attributeSets as $kw) {
                    $kw = trim($kw);
                    if ($kw !== '') {
                        $nameConditions[] = $collection->getConnection()->quoteInto(
                            'main_table.name LIKE ?',
                            '%' . $kw . '%'
                        );
                    }
                }
                if (!empty($nameConditions)) {
                    $collection->getSelect()->where('(' . implode(' OR ', $nameConditions) . ')');
                }
            }

            $collection->getSelect()->columns([
                'order_day' => new \Zend_Db_Expr('DATE(o.created_at)'),
                'product_id' => 'main_table.product_id',
                'total_qty'  => new \Zend_Db_Expr('SUM(main_table.qty_ordered)'),
            ]);
            $collection->getSelect()->group([
                new \Zend_Db_Expr('DATE(o.created_at)'),
                'main_table.product_id',
            ]);

            $productCache = [];

            foreach ($collection as $item) {
                $day       = (string)$item->getData('order_day');
                $productId = (int)$item->getData('product_id');
                $qty       = (int)$item->getData('total_qty');

                if (!isset($result[$day])) {
                    continue;
                }

                if (!array_key_exists($productId, $productCache)) {
                    try {
                        $productCache[$productId] = $this->productRepository->getById($productId);
                    } catch (\Throwable $e) {
                        $productCache[$productId] = null;
                    }
                }

                $product = $productCache[$productId];
                if ($product === null) {
                    continue;
                }

                if (!$this->productMatchesTrendingFilters($product, $attributeSets, $attributeFilters)) {
                    continue;
                }

                $result[$day][] = [
                    'product_id' => $productId,
                    'name'       => (string)$product->getName(),
                    'sku'        => (string)$product->getSku(),
                    'qty'        => $qty,
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('fetchTrendingRawByDays error', ['exception' => $e]);
            return $result;
        }
    }

    /**
     * Fetch matched order rows for the given days, applying attribute + product filters.
     * Groups results by order date so each day's data can be cached independently.
     *
     * @param  string[]             $days
     * @param  string[]             $attributeSets
     * @param  array<string,string> $parsedFilters
     * @return array<string, array{orders:array[], total_qty:float, total_revenue:float}>
     */
    private function fetchSalesFilteredRawByDays(array $days, array $attributeSets, array $parsedFilters): array
    {
        $result = [];
        foreach ($days as $day) {
            $result[$day] = ['orders' => [], 'total_qty' => 0.0, 'total_revenue' => 0.0];
        }

        if (empty($days)) {
            return $result;
        }

        try {
            $orders = $this->orderCollectionFactory->create();
            $orders->getSelect()->where('DATE(main_table.created_at) IN (?)', $days);
            $orders->addFieldToFilter('state', [
                'nin' => [
                    \Magento\Sales\Model\Order::STATE_CANCELED,
                    \Magento\Sales\Model\Order::STATE_CLOSED,
                ]
            ]);
            $orders->setOrder('created_at', 'DESC');

            $productCache = [];

            foreach ($orders as $order) {
                $day              = date('Y-m-d', strtotime((string)$order->getCreatedAt()));
                $orderMatchedQty  = 0.0;
                $orderMatchedAmt  = 0.0;

                foreach ($order->getAllVisibleItems() as $item) {
                    $productId = (int)$item->getProductId();
                    if (!array_key_exists($productId, $productCache)) {
                        try {
                            $productCache[$productId] = $this->productRepository->getById($productId);
                        } catch (\Throwable $e) {
                            $productCache[$productId] = null;
                        }
                    }

                    if (!$this->orderItemMatchesSalesFilters($item, $productCache[$productId], $attributeSets, $parsedFilters)) {
                        continue;
                    }

                    $qty    = (float)$item->getQtyOrdered();
                    $amount = (float)$item->getRowTotalInclTax();
                    if ($amount <= 0) {
                        $amount = (float)$item->getRowTotal();
                    }

                    $orderMatchedQty += $qty;
                    $orderMatchedAmt += $amount;
                }

                if ($orderMatchedQty <= 0.0) {
                    continue;
                }

                if (!isset($result[$day])) {
                    $result[$day] = ['orders' => [], 'total_qty' => 0.0, 'total_revenue' => 0.0];
                }

                $result[$day]['orders'][] = [
                    'entity_id'    => (int)$order->getId(),
                    'increment_id' => (string)$order->getIncrementId(),
                    'created_at'   => (string)$order->getCreatedAt(),
                    'qty'          => $orderMatchedQty,
                    'amount'       => $orderMatchedAmt,
                ];
                $result[$day]['total_qty']     += $orderMatchedQty;
                $result[$day]['total_revenue'] += $orderMatchedAmt;
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('fetchSalesFilteredRawByDays error', ['exception' => $e]);
            return $result;
        }
    }

    /**
     * Fetch all reviews that fall on the given days.
     * No limit is applied — limit is enforced during aggregation.
     *
     * @param  string[] $days
     * @return array<string, array[]>   per-day list of review row arrays
     */
    private function fetchReviewsRawByDays(array $days): array
    {
        $result = [];
        foreach ($days as $day) {
            $result[$day] = [];
        }

        if (empty($days)) {
            return $result;
        }

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $conn = $objectManager->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();

            $reviewTable   = $conn->getTableName('review');
            $reviewDetail  = $conn->getTableName('review_detail');
            $reviewSummary = $conn->getTableName('review_entity_summary');
            $productTable  = $conn->getTableName('catalog_product_entity');

            $select = $conn->select()
                ->from(['r' => $reviewTable], ['review_id', 'entity_pk_value', 'created_at', 'status_id'])
                ->join(['rd' => $reviewDetail], 'rd.review_id = r.review_id', ['title', 'detail', 'nickname', 'customer_id'])
                ->joinLeft(['rs' => $reviewSummary], 'rs.entity_pk_value = r.entity_pk_value AND rs.store_id = 1', ['rating_summary', 'reviews_count'])
                ->joinLeft(['cpe' => $productTable], 'cpe.entity_id = r.entity_pk_value', ['sku'])
                ->where('DATE(r.created_at) IN (?)', $days)
                ->where('r.entity_id = 1')
                ->order('r.created_at DESC');

            foreach ($conn->fetchAll($select) as $row) {
                $day = date('Y-m-d', strtotime((string)$row['created_at']));
                if (isset($result[$day])) {
                    $result[$day][] = $row;
                }
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('fetchReviewsRawByDays error', ['exception' => $e]);
            return $result;
        }
    }

    // =========================================================================
    // Aggregators — combine per-day raw data into a single result set
    // =========================================================================

    /**
     * @param  array<string, array> $allRawByDay
     * @return array{order_count:int, revenue:float}
     */
    private function aggregateSalesDaily(array $allRawByDay): array
    {
        $totalOrders  = 0;
        $totalRevenue = 0.0;

        foreach ($allRawByDay as $dayData) {
            $totalOrders  += (int)($dayData['order_count'] ?? 0);
            $totalRevenue += (float)($dayData['revenue'] ?? 0.0);
        }

        return ['order_count' => $totalOrders, 'revenue' => $totalRevenue];
    }

    /**
     * Merge per-day product sales, sum qty across days, sort desc, and slice to $limit.
     *
     * @param  array<string, array[]> $allRawByDay
     * @return array[]
     */
    private function aggregateTrendingDaily(array $allRawByDay, int $limit): array
    {
        $byProduct = [];

        foreach ($allRawByDay as $dayData) {
            if (!is_array($dayData)) {
                continue;
            }
            foreach ($dayData as $product) {
                $pid = (int)($product['product_id'] ?? 0);
                if ($pid <= 0) {
                    continue;
                }
                if (!isset($byProduct[$pid])) {
                    $byProduct[$pid] = [
                        'product_id' => $pid,
                        'name'       => (string)($product['name'] ?? ''),
                        'sku'        => (string)($product['sku'] ?? ''),
                        'qty'        => 0,
                    ];
                }
                $byProduct[$pid]['qty'] += (int)($product['qty'] ?? 0);
            }
        }

        usort($byProduct, function (array $a, array $b): int {
            return $b['qty'] - $a['qty'];
        });

        return array_slice(array_values($byProduct), 0, $limit);
    }

    /**
     * Merge per-day filtered sales data. Orders from all days are concatenated and
     * sorted by date descending.
     *
     * @param  array<string, array> $allRawByDay
     * @return array{orders:array[], total_qty:float, total_revenue:float}
     */
    private function aggregateSalesFiltered(array $allRawByDay): array
    {
        $allOrders    = [];
        $totalQty     = 0.0;
        $totalRevenue = 0.0;

        foreach ($allRawByDay as $dayData) {
            $orders = $dayData['orders'] ?? [];
            if (!empty($orders)) {
                $allOrders    = array_merge($allOrders, $orders);
                $totalQty    += (float)($dayData['total_qty'] ?? 0.0);
                $totalRevenue += (float)($dayData['total_revenue'] ?? 0.0);
            }
        }

        // Sort orders newest first.
        usort($allOrders, function (array $a, array $b): int {
            return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
        });

        return ['orders' => $allOrders, 'total_qty' => $totalQty, 'total_revenue' => $totalRevenue];
    }

    /**
     * Concatenate per-day review arrays, sort by date descending, and slice to $limit.
     *
     * @param  array<string, array[]> $allRawByDay
     * @return array[]
     */
    private function aggregateReviewsDaily(array $allRawByDay, int $limit): array
    {
        $all = [];
        foreach ($allRawByDay as $dayData) {
            if (is_array($dayData)) {
                $all = array_merge($all, $dayData);
            }
        }

        usort($all, function (array $a, array $b): int {
            return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
        });

        return array_slice($all, 0, $limit);
    }

    /**
     * @param string $fromDateTime
     * @param string $toDateTime
     * @param string $title
     * @return string
     */
    private function buildSalesSummary(string $fromDateTime, string $toDateTime, string $title): string
    {
        try {
            $collection = $this->orderCollectionFactory->create();
            $collection->addFieldToFilter('created_at', ['from' => $fromDateTime, 'to' => $toDateTime]);
            $collection->addFieldToFilter('state', [
                'nin' => [
                    \Magento\Sales\Model\Order::STATE_CANCELED,
                    \Magento\Sales\Model\Order::STATE_CLOSED
                ]
            ]);

            $totalSales = 0.0;
            $orderCount = 0;

            foreach ($collection as $order) {
                if ($order->getGrandTotal()) {
                    $totalSales += (float)$order->getGrandTotal();
                    $orderCount++;
                }
            }

            return $title . ":\n\n"
                . 'Total Orders: ' . $orderCount . "\n"
                . 'Total Revenue: ' . $this->getCurrencySymbol() . number_format($totalSales, 2) . "\n"
                . 'Orders: ' . $this->buildAdminLinkToken('orders', 0, 'Open Orders Grid');
        } catch (\Throwable $e) {
            $this->logger->error('Sales summary error', ['exception' => $e]);
            return 'Unable to fetch sales summary right now.';
        }
    }

    /**
     * @param string|null $fromDate
     * @param string|null $toDate
     * @param string $defaultFromModifier
     * @return array{0:string,1:string,2:?string}
     */
    private function normalizeDateRange(?string $fromDate, ?string $toDate, string $defaultFromModifier): array
    {
        $fromDate = trim((string)$fromDate);
        $toDate = trim((string)$toDate);

        if ($fromDate === '' || $toDate === '') {
            $toDateTime = date('Y-m-d 23:59:59');
            $fromDateTime = date('Y-m-d 00:00:00', strtotime($defaultFromModifier));
        } else {
            if (!$this->isValidDate($fromDate) || !$this->isValidDate($toDate)) {
                return ['', '', 'Please provide valid dates in YYYY-MM-DD format.'];
            }

            $fromDateTime = $fromDate . ' 00:00:00';
            $toDateTime = $toDate . ' 23:59:59';
        }

        $fromTs = strtotime($fromDateTime);
        $toTs = strtotime($toDateTime);
        if ($fromTs === false || $toTs === false || $fromTs > $toTs) {
            return ['', '', 'Invalid date range.'];
        }

        $maxDays = 365;
        if ((($toTs - $fromTs) / 86400) > $maxDays) {
            return ['', '', 'Date range cannot exceed 1 year.'];
        }

        return [$fromDateTime, $toDateTime, null];
    }

    /**
     * @param string $date
     * @return bool
     */
    private function isValidDate(string $date): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        return $dt !== false && $dt->format('Y-m-d') === $date;
    }

    /**
     * @param string $csv
     * @return array
     */
    private function parseCsvValues(string $csv): array
    {
        $values = array_map('trim', explode(',', $csv));
        $values = array_filter($values, static function ($v) {
            if ($v === '') {
                return false;
            }

            return !preg_match('/^\{[^}]+\}$/', $v);
        });
        return array_values(array_unique($values));
    }

    /**
     * @param string $filtersRaw
     * @return array<string,string>
     */
    private function parseKeyValueFilters(string $filtersRaw): array
    {
        $result = [];
        $parts = preg_split('/[;,]/', $filtersRaw) ?: [];
        $parts = array_filter(array_map('trim', $parts));

        foreach ($parts as $part) {
            if (strpos($part, '=') === false) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $part, 2));
            if ($key === '' || $value === '') {
                continue;
            }

            $result[strtolower($key)] = strtolower($value);
        }

        return $result;
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param array $attributeSets
     * @param array<string,string> $attributeFilters
     * @return bool
     */
    private function productMatchesTrendingFilters($product, array $attributeSets, array $attributeFilters): bool
    {
        if (!empty($attributeSets)) {
            $setName = $this->getAttributeSetName((int)$product->getAttributeSetId());
            $setNameLower = $this->normalizeFilterToken($setName);
            $setMatch = false;

            foreach ($attributeSets as $requiredSet) {
                $requiredSetNormalized = $this->normalizeFilterToken($requiredSet);
                if ($requiredSetNormalized === '') {
                    continue;
                }

                if (
                    ($setNameLower !== '' && strpos($setNameLower, $requiredSetNormalized) !== false)
                    || ($setNameLower !== '' && strpos($requiredSetNormalized, $setNameLower) !== false)
                    || ($setNameLower !== '' && rtrim($setNameLower, 's') === rtrim($requiredSetNormalized, 's'))
                ) {
                    $setMatch = true;
                    break;
                }
            }

            if (!$setMatch) {
                return false;
            }
        }

        foreach ($attributeFilters as $attributeCode => $expectedValue) {
            $rawValue = $product->getData($attributeCode);
            $displayValue = $product->getAttributeText($attributeCode);

            $rawString = is_array($rawValue) ? implode(',', $rawValue) : (string)$rawValue;
            $displayString = is_array($displayValue) ? implode(',', $displayValue) : (string)$displayValue;

            $haystack = $this->normalizeFilterToken($displayString !== '' ? $displayString : $rawString);
            $needle = $this->normalizeFilterToken($expectedValue);

            if ($needle === '' || $haystack === '' || strpos($haystack, $needle) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $attributeSetId
     * @return string
     */
    private function getAttributeSetName(int $attributeSetId): string
    {
        if ($attributeSetId <= 0) {
            return '';
        }

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
            $connection = $resource->getConnection();
            $table = $resource->getTableName('eav_attribute_set');

            $name = $connection->fetchOne(
                $connection->select()
                    ->from($table, ['attribute_set_name'])
                    ->where('attribute_set_id = ?', $attributeSetId)
                    ->limit(1)
            );

            return $name ? (string)$name : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * @param string $riskLevel
     * @param int $limit
     * @param int|null $storeId
     * @return string
     */
    private function processCustomerRiskSummary(string $riskLevel = '', int $limit = 10, ?int $storeId = null): string
    {
        $riskLevel = strtolower(trim($riskLevel));
        $limit = max(1, min($limit, 500));

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
            $connection = $resource->getConnection();
            $table = $resource->getTableName('artm_adminai_customer_analytics');

            $select = $connection->select()
                ->from($table)
                ->order('risk_score DESC')
                ->order('days_since_last_order DESC')
                ->limit($limit);

            if ($riskLevel !== '') {
                $select->where('risk_level = ?', $riskLevel);
            }
            if ($storeId !== null && $storeId >= 0) {
                $select->where('store_id = ?', $storeId);
            }

            $rows = $connection->fetchAll($select);
            if (empty($rows)) {
                return 'No customer risk data found. Please ensure analytics sync has populated customer analytics.';
            }

            $lines = ['Customer Risk Summary:'];
            $lines[] = 'Risk Level Filter: ' . ($riskLevel !== '' ? $riskLevel : '[all]');
            $lines[] = 'Store ID: ' . ($storeId !== null ? (string)$storeId : '[all]');
            $lines[] = '';

            foreach ($rows as $index => $row) {
                $lines[] = ($index + 1) . '. Customer ID: ' . (int)$row['customer_id']
                    . ' | Risk: ' . (string)$row['risk_level']
                    . ' (' . number_format((float)$row['risk_score'], 2) . ')'
                    . ' | Days Since Order: ' . ($row['days_since_last_order'] !== null ? (int)$row['days_since_last_order'] : 'N/A')
                    . ' ' . $this->buildAdminLinkToken('customer', (int)$row['customer_id'], 'open');
                $lines[] = '   Orders 90d: ' . (int)$row['orders_count_90d']
                    . ' | Revenue 90d: ' . $this->getCurrencySymbol() . number_format((float)$row['revenue_90d'], 2)
                    . ' | Last Order: ' . ($row['last_order_at'] ?: 'N/A');
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            $this->logger->error('Customer risk summary error', ['exception' => $e]);
            return 'Unable to fetch customer risk summary right now.';
        }
    }

    /**
     * @param int $daysAhead
     * @param string $sku
     * @param int $limit
     * @return string
     */
    private function processStockProjection(int $daysAhead = 30, string $sku = '', int $limit = 10): string
    {
        $daysAhead = max(1, min($daysAhead, 365));
        $limit = max(1, min($limit, 500));
        $sku = trim($sku);

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
            $connection = $resource->getConnection();
            $table = $resource->getTableName('artm_adminai_product_analytics_day');

            $latestDate = $connection->fetchOne(
                $connection->select()->from($table, [new \Zend_Db_Expr('MAX(aggregate_date)')])
            );

            if (!$latestDate) {
                return 'No stock projection data found. Please ensure product analytics sync has populated the analytics table.';
            }

            $select = $connection->select()
                ->from($table)
                ->where('aggregate_date = ?', $latestDate)
                ->order('days_of_stock ASC')
                ->order('stock_qty ASC')
                ->limit($limit);

            if ($sku !== '') {
                $select->where('sku = ?', $sku);
            }

            $rows = $connection->fetchAll($select);
            if (empty($rows)) {
                return 'No stock projection rows found for the requested filters.';
            }

            $lines = ['Stock Projection (' . $daysAhead . ' day(s) ahead):'];
            $lines[] = 'Snapshot Date: ' . (string)$latestDate;
            $lines[] = '';

            foreach ($rows as $index => $row) {
                $dailySold = (float)$row['qty_sold'];
                $stockQty = (float)$row['stock_qty'];
                $projectedRemaining = $stockQty - ($dailySold * $daysAhead);
                $status = $projectedRemaining <= 0 ? 'Risk of stockout' : 'Projected in stock';

                $lines[] = ($index + 1) . '. ' . (string)$row['sku']
                    . ' | Stock: ' . number_format($stockQty, 2)
                    . ' | Daily Sold: ' . number_format($dailySold, 2)
                    . ' | Projected Remaining: ' . number_format($projectedRemaining, 2)
                    . ' | ' . $status
                    . ' ' . $this->buildAdminLinkToken('product', (int)$row['product_id'], 'open');
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            $this->logger->error('Stock projection error', ['exception' => $e]);
            return 'Unable to fetch stock projection right now.';
        }
    }

    /**
     * @param string $fromDate
     * @param string $toDate
     * @param int $limit
     * @param int|null $storeId
     * @return string
     */
    private function processProductPerformance(string $fromDate, string $toDate, int $limit = 10, ?int $storeId = null): string
    {
        $limit = max(1, min($limit, 500));
        [$fromDT, $toDT, $err] = $this->normalizeDateRange($fromDate, $toDate, '-30 days');
        if ($err !== null) {
            return $err;
        }

        $from = date('Y-m-d', strtotime($fromDT));
        $to = date('Y-m-d', strtotime($toDT));

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
            $connection = $resource->getConnection();
            $table = $resource->getTableName('artm_adminai_product_analytics_day');

            $select = $connection->select()
                ->from(
                    ['p' => $table],
                    [
                        'product_id',
                        'sku',
                        'qty_sold' => new \Zend_Db_Expr('SUM(qty_sold)'),
                        'revenue_total' => new \Zend_Db_Expr('SUM(revenue_total)'),
                        'avg_stock_qty' => new \Zend_Db_Expr('AVG(stock_qty)'),
                        'avg_days_of_stock' => new \Zend_Db_Expr('AVG(days_of_stock)')
                    ]
                )
                ->where('aggregate_date >= ?', $from)
                ->where('aggregate_date <= ?', $to)
                ->group(['product_id', 'sku'])
                ->order('revenue_total DESC')
                ->limit($limit);

            if ($storeId !== null && $storeId >= 0) {
                $select->where('store_id = ?', $storeId);
            }

            $rows = $connection->fetchAll($select);
            if (empty($rows)) {
                return 'No product performance data found for the selected date range.';
            }

            $lines = ['Product Performance (' . $from . ' to ' . $to . '):'];
            $lines[] = 'Store ID: ' . ($storeId !== null ? (string)$storeId : '[all]');
            $lines[] = '';

            foreach ($rows as $index => $row) {
                $lines[] = ($index + 1) . '. ' . (string)$row['sku']
                    . ' | Qty Sold: ' . number_format((float)$row['qty_sold'], 2)
                    . ' | Revenue: ' . $this->getCurrencySymbol() . number_format((float)$row['revenue_total'], 2)
                    . ' | Avg Stock: ' . number_format((float)$row['avg_stock_qty'], 2)
                    . ' ' . $this->buildAdminLinkToken('product', (int)$row['product_id'], 'open');
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            $this->logger->error('Product performance error', ['exception' => $e]);
            return 'Unable to fetch product performance right now.';
        }
    }

    /**
     * @param string $period
     * @param int|null $storeId
     * @return string
     */
    private function processExecutiveSalesSummary(string $period = '30d', ?int $storeId = null): string
    {
        $period = strtolower(trim($period));
        $days = ['7d' => 7, '30d' => 30, '90d' => 90][$period] ?? 30;
        $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
            $connection = $resource->getConnection();
            $table = $resource->getTableName('artm_adminai_sales_aggregate_day');

            $select = $connection->select()
                ->from(
                    $table,
                    [
                        'orders_count' => new \Zend_Db_Expr('SUM(orders_count)'),
                        'revenue_total' => new \Zend_Db_Expr('SUM(revenue_total)'),
                        'items_qty_total' => new \Zend_Db_Expr('SUM(items_qty_total)'),
                        'new_customers_count' => new \Zend_Db_Expr('SUM(new_customers_count)')
                    ]
                )
                ->where('aggregate_date >= ?', $from);

            if ($storeId !== null && $storeId >= 0) {
                $select->where('store_id = ?', $storeId);
            }

            $row = $connection->fetchRow($select);
            if (!is_array($row) || (float)($row['revenue_total'] ?? 0) <= 0 && (int)($row['orders_count'] ?? 0) <= 0) {
                return 'No executive sales summary data found for the selected period.';
            }

            $ordersCount = (int)$row['orders_count'];
            $revenueTotal = (float)$row['revenue_total'];
            $avgOrderValue = $ordersCount > 0 ? $revenueTotal / $ordersCount : 0.0;

            return implode("\n", [
                'Executive Sales Summary (' . $period . '):',
                'Store ID: ' . ($storeId !== null ? (string)$storeId : '[all]'),
                'Total Orders: ' . $ordersCount,
                'Total Revenue: ' . $this->getCurrencySymbol() . number_format($revenueTotal, 2),
                'Items Sold: ' . number_format((float)$row['items_qty_total'], 2),
                'New Customers: ' . (int)$row['new_customers_count'],
                'Average Order Value: ' . $this->getCurrencySymbol() . number_format($avgOrderValue, 2),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Executive sales summary error', ['exception' => $e]);
            return 'Unable to fetch executive sales summary right now.';
        }
    }

    /**
     * @param int $inactiveDays
     * @param int $limit
     * @param int|null $storeId
     * @return string
     */
    private function processInactiveCustomers(int $inactiveDays = 90, int $limit = 10, ?int $storeId = null): string
    {
        $inactiveDays = max(1, min($inactiveDays, 3650));
        $limit = max(1, min($limit, 500));

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
            $connection = $resource->getConnection();
            $table = $resource->getTableName('artm_adminai_customer_analytics');

            $select = $connection->select()
                ->from($table)
                ->where('days_since_last_order >= ?', $inactiveDays)
                ->order('days_since_last_order DESC')
                ->order('risk_score DESC')
                ->limit($limit);

            if ($storeId !== null && $storeId >= 0) {
                $select->where('store_id = ?', $storeId);
            }

            $rows = $connection->fetchAll($select);
            if (empty($rows)) {
                return 'No inactive customers found for the selected filters.';
            }

            $lines = ['Inactive Customers:'];
            $lines[] = 'Inactive Days Threshold: ' . $inactiveDays;
            $lines[] = 'Store ID: ' . ($storeId !== null ? (string)$storeId : '[all]');
            $lines[] = '';

            foreach ($rows as $index => $row) {
                $lines[] = ($index + 1) . '. Customer ID: ' . (int)$row['customer_id']
                    . ' | Days Since Order: ' . (int)$row['days_since_last_order']
                    . ' | Risk: ' . (string)$row['risk_level']
                    . ' ' . $this->buildAdminLinkToken('customer', (int)$row['customer_id'], 'open');
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            $this->logger->error('Inactive customers error', ['exception' => $e]);
            return 'Unable to fetch inactive customers right now.';
        }
    }

    /**
     * @param array<string,string> $filters
     * @return string
     */
    private function formatFilters(array $filters): string
    {
        $parts = [];
        foreach ($filters as $key => $value) {
            $parts[] = $key . '=' . $value;
        }
        return implode('; ', $parts);
    }

    /**
     * @param string $value
     * @return string
     */
    private function normalizeFilterToken(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '', $value);
        return (string)$value;
    }

    /**
     * @param string $entity
     * @param int $id
     * @param string $label
     * @return string
     */
    private function buildAdminLinkToken(string $entity, int $id, string $label): string
    {
        return '[[ADMIN:' . $entity . ':' . $id . '|' . $label . ']]';
    }
}
