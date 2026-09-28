<?php
namespace ArtM\MassProducts\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;

class MediaTableManager
{
    private const TABLE_NAME = 'artm_massproducts_draft_media';

    private ResourceConnection $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function getTableName(): string
    {
        return $this->resource->getTableName(self::TABLE_NAME);
    }

    public function exists(): bool
    {
        return $this->resource->getConnection()->isTableExists($this->getTableName());
    }

    public function ensureExists(): string
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->getTableName();

        if ($connection->isTableExists($tableName)) {
            return $tableName;
        }

        $table = $connection->newTable($tableName)
            ->addColumn(
                'media_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'Media ID'
            )
            ->addColumn(
                'draft_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'Draft ID'
            )
            ->addColumn(
                'file_path',
                Table::TYPE_TEXT,
                500,
                ['nullable' => false],
                'Relative path under pub/media'
            )
            ->addColumn(
                'roles',
                Table::TYPE_TEXT,
                100,
                ['nullable' => false, 'default' => ''],
                'Comma-separated roles: base,small,thumbnail'
            )
            ->addColumn(
                'position',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true, 'default' => 0],
                'Sort Position'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addIndex(
                $connection->getIndexName($tableName, ['draft_id']),
                ['draft_id']
            )
            ->setComment('ArtM Draft Product Media');

        $connection->createTable($table);

        return $tableName;
    }
}