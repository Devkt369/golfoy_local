<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Model\ResourceModel\Location;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadata;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Zend_Db;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Resource model location
 */
class Location extends AbstractDb
{
    /**
     * @var EntityMetadata
     */
    private $metadata;
    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * Location constructor.
     * @param Context $context
     * @param MetadataPool $metadataPool
     * @param TimezoneInterface $localeDate
     * @param string|null $connectionName
     * @throws \Exception
     */
    public function __construct(
        Context $context,
        MetadataPool $metadataPool,
        TimezoneInterface $localeDate,
        $connectionName = null
    ) {
        $this->metadata = $metadataPool->getMetadata(
            ProductInterface::class
        );
        $this->localeDate = $localeDate;
        parent::__construct($context, $connectionName);
    }

    /**
     * Construct
     */
    protected function _construct()
    {
        $this->_init('webpos_location', 'location_id');
    }

    /**
     * Get invoice data
     *
     * @param int $locationId
     * @param string|null $lastUpdatedTime
     */
    public function getInvoiceData(int $locationId, string $lastUpdatedTime = null)
    {
        if (!$lastUpdatedTime) {
            $lastUpdatedTime = $this->localeDate->date()->format('Y-m-d') . ' 00:00:00';
        }
        $invoiceSelect = $this->getConnection()->select()
            ->from($this->getTable('sales_invoice'))
            ->where("created_at > '" . $lastUpdatedTime . "'")
            ->where('pos_location_id = ?', $locationId);
        $invoiceItemSelect = $this->getConnection()->select()
            ->from(
                ['it' => $this->getTable('sales_invoice_item')],
                [
                    'sku',
                    'base_unit_price' => new \Zend_Db_Expr(
                        'it.base_price_incl_tax + ROUND(it.base_tax_amount / it.qty, 2)'
                    ),
                    'qty',
                    'product_id'
                ]
            )
            ->joinInner(
                ['i' => $invoiceSelect],
                'i.entity_id = it.parent_id',
                ['timestamp' => 'i.created_at']
            );

        return $this->getConnection()->fetchAll($invoiceItemSelect);
    }

    /**
     * Get product attributes
     *
     * @param int $storeId
     * @param array $attributeTypes
     * @param array $productIds
     * @return array
     */
    public function getProductAttributes($storeId, array $attributeTypes, array $productIds = [])
    {
        $result = [];
        $selects = [];
        $ifStoreValue = $this->getConnection()->getCheckSql(
            't_store.value_id > 0',
            't_store.value',
            't_default.value'
        );
        $linkField = $this->metadata->getLinkField();

        $productLinkFieldsSelect = $this->getConnection()->select()->from(
            ['cpe' => $this->getTable('catalog_product_entity')],
            [$linkField, 'entity_id']
        );
        if (!empty($productIds)) {
            $productLinkFieldsSelect->where(
                'cpe.entity_id IN (?)',
                $productIds,
                Zend_Db::INT_TYPE
            );
        }
        $productLinkFieldsToEntityIdMap = $this->getConnection()->fetchPairs($productLinkFieldsSelect);

        foreach ($attributeTypes as $backendType => $attributeIds) {
            if ($attributeIds) {
                $tableName = $this->getTable('catalog_product_entity_' . $backendType);

                $select = $this->getConnection()->select()->from(
                    ['t' => $tableName],
                    [
                        $linkField => 't.' . $linkField,
                        'attribute_id' => 't.attribute_id',
                        'value' => $this->unifyField($ifStoreValue, $backendType),
                    ]
                )->joinLeft(
                    ['t_store' => $tableName],
                    $this->getConnection()->quoteInto(
                        't.' . $linkField . '=t_store.' . $linkField .
                        ' AND t.attribute_id=t_store.attribute_id' .
                        ' AND t_store.store_id = ?',
                        $storeId
                    ),
                    []
                )->joinLeft(
                    ['t_default' => $tableName],
                    $this->getConnection()->quoteInto(
                        't.' . $linkField . '=t_default.' . $linkField .
                        ' AND t.attribute_id=t_default.attribute_id' .
                        ' AND t_default.store_id = ?',
                        0
                    ),
                    []
                )->where(
                    't.attribute_id IN (?)',
                    $attributeIds
                )->where(
                    't.' . $linkField . ' IN (?)',
                    array_keys($productLinkFieldsToEntityIdMap)
                )->distinct();
                $selects[] = $select;
            }
        }

        if ($selects) {
            $select = $this->getConnection()->select()->union($selects, Select::SQL_UNION_ALL);
            $query = $this->getConnection()->query($select);
            while ($row = $query->fetch()) {
                $entityId = $productLinkFieldsToEntityIdMap[$row[$linkField]];
                $result[$entityId][$row['attribute_id']] = $row['value'];
            }
        }

        return $result;
    }

    /**
     * Returns expression for field unification
     *
     * @param string $field
     * @param string $backendType
     * @return \Zend_Db_Expr
     */
    private function unifyField(string $field, string $backendType = 'varchar')
    {
        if ($backendType == 'datetime') {
            $expr = $this->getConnection()->getDateFormatSql($field, '%Y-%m-%d %H:%i:%s');
        } else {
            $expr = $field;
        }

        return $expr;
    }
}
