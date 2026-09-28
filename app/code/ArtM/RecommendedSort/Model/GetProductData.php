<?php
namespace ArtM\RecommendedSort\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

class GetProductData
{
    protected $resource;
    protected $storeManager;

    public function __construct(
        ResourceConnection $resource,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
    }

    public function getProductsWithSortingPoints($categoryId)
    {
        $connection = $this->resource->getConnection();

        $productEntityTable   = $this->resource->getTableName('catalog_product_entity');
        $categoryProductTable = $this->resource->getTableName('catalog_category_product');
        $sortingProductsTable = $this->resource->getTableName('artm_sorting_products');
        $varcharTable         = $this->resource->getTableName('catalog_product_entity_varchar');
        $intTable             = $this->resource->getTableName('catalog_product_entity_int');
        $eavAttrTable         = $this->resource->getTableName('eav_attribute');
        $sortingCategoryProducts = $this->resource->getTableName('artm_sorting_category_products');

        // Fetch attribute IDs for 'name', 'image', 'status', and 'visibility'
        $attributeCodesToFetch = ['name', 'image', 'status', 'visibility'];
        $attributeIds = $connection->fetchPairs(
            $connection->select()
                ->from($eavAttrTable, ['attribute_code', 'attribute_id'])
                ->where('attribute_code IN (?)', $attributeCodesToFetch)
                ->where('entity_type_id = ?', 4)
        );

        $nameAttrId       = (int)$attributeIds['name'];
        $imageAttrId      = (int)$attributeIds['image'];
        $statusAttrId     = (int)$attributeIds['status'];
        $visibilityAttrId = (int)$attributeIds['visibility'];

        $select = $connection->select()
            ->from(['cp' => $categoryProductTable], ['sorting_point' => 'cp.sorting_points'])
            ->joinInner(['p' => $productEntityTable], 'cp.product_id = p.entity_id', ['id' => 'entity_id'])
            ->joinLeft(
                ['sp' => $sortingProductsTable],
                'sp.product_id = p.entity_id', // Removed cat_id join condition for better matching
                [
                    'sp_id'                        => 'sp.id',
                    'attr_set_id'                  => 'sp.attr_set_id',
                    'attr_set_name'                => 'sp.attr_set_name',
                    'is_feature'                   => 'sp.is_feature',
                    'available_size_roll_value'   => 'sp.available_size_roll_value',
                    'size_roll_points'             => 'sp.size_roll_points',
                    'size_roll_points_percentage' => 'sp.size_roll_points_percentage',
                    'high_discount_points'        => 'sp.high_discount_points',
                    'created_date_points'         => 'sp.created_date_points',
                    'latest_updated_points'       => 'sp.latest_updated_points',
                    'availability_cgc'            => 'sp.availability_cgc',
                    'availability_golfoy'         => 'sp.availability_golfoy',
                    'availability_other'          => 'sp.availability_other',
                    'rating'                      => 'sp.rating',
                    'total_points'                => 'sp.total_points',
                ]
            )
            ->joinLeft(
                ['name_varchar' => $varcharTable],
                'name_varchar.entity_id = p.entity_id AND name_varchar.attribute_id = ' . $nameAttrId . ' AND name_varchar.store_id = 0',
                ['name' => 'name_varchar.value']
            )
            ->joinLeft(
                ['image_varchar' => $varcharTable],
                'image_varchar.entity_id = p.entity_id AND image_varchar.attribute_id = ' . $imageAttrId . ' AND image_varchar.store_id = 0',
                ['image' => 'image_varchar.value']
            )
            ->joinLeft(
                ['status_int' => $intTable],
                'status_int.entity_id = p.entity_id AND status_int.attribute_id = ' . $statusAttrId . ' AND status_int.store_id = 0',
                []
            )
            ->joinLeft(
                ['visibility_int' => $intTable],
                'visibility_int.entity_id = p.entity_id AND visibility_int.attribute_id = ' . $visibilityAttrId . ' AND visibility_int.store_id = 0',
                []
            )
            ->where('cp.category_id = ?', $categoryId)
            ->where('status_int.value = ?', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->where('visibility_int.value IN (?)', [
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH
            ])
            //->group('p.entity_id') // Removed to avoid data collapse - uncomment only if duplicates occur
        ;

        $rows = $connection->fetchAll($select);

        // Build full image URLs
        $mediaBaseUrl = $this->storeManager
            ->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $mediaPath = 'catalog/product';

        // foreach ($rows as &$row) {
        //     $row['image'] = $row['image']
        //         ? $mediaBaseUrl . $mediaPath . $row['image']
        //         : '';
        // }


        foreach ($rows as &$row) {
            $row['image'] = $row['image']
                ? $mediaBaseUrl . $mediaPath . $row['image']
                : '';
            $productId = $row['id'];

            $manualCheckSelect = $connection->select()
                ->from('artm_sorting_category_products', 'id')
                ->where('category_id = ?', $categoryId)
                ->where('product_id = ?', $productId)
                ->limit(1);

            $manualId = $connection->fetchOne($manualCheckSelect);

            if ($manualId && isset($row['total_points']) && $row['total_points'] !== null) {
                $row['total_points'] .= ' (Manual)';
            }
        }

        return $rows;
    }
}
