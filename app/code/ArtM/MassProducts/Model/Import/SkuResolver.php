<?php
namespace ArtM\MassProducts\Model\Import;

use Magento\Framework\App\ResourceConnection;

class SkuResolver
{
    private $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function resolveOperation(string $sku): string
    {
        $sku = trim($sku);
        if ($sku === '') {
            return 'create';
        }

        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('catalog_product_entity');

        $exists = (bool)$connection->fetchOne(
            $connection->select()
                ->from($table, ['entity_id'])
                ->where('sku = ?', $sku)
                ->limit(1)
        );

        return $exists ? 'update' : 'create';
    }
}
