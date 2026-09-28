<?php
namespace ArtM\Sizeroll\Model;

use Magento\Framework\Model\AbstractModel;

class SortingProducts extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\ArtM\Sizeroll\Model\ResourceModel\SortingProducts::class);
    }

    /**
     * Get available_size_roll_value for each SKU
     * @param array $skus
     * @return array
     */
    public function getAvailableSizesBySkus(array $skus)
    {
        // Remove empty values
        $skus = array_filter($skus, function($sku) {
            return !empty($sku);
        });
        if (empty($skus)) {
            return [];
        }
        $connection = $this->getResource()->getConnection();
        $select = $connection->select()
            ->from(['sp' => $this->getResource()->getMainTable()], ['product_id', 'available_size_roll_value'])
            ->joinInner(
                ['cpe' => $connection->getTableName('catalog_product_entity')],
                'sp.product_id = cpe.entity_id',
                ['sku']
            )
            ->where('cpe.sku IN (?)', $skus);
        $result = $connection->fetchAll($select);
        $sizes = [];
        foreach ($result as $row) {
            $sizes[$row['sku']] = $row['available_size_roll_value'];
        }
        return $sizes;
    }
}
