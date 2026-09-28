<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Draft;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class TabList extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;

    public function __construct(Context $context, ResourceConnection $resource)
    {
        parent::__construct($context);
        $this->resource = $resource;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_massproducts_draft');

            $schema = $connection->describeTable($table);
            $existingColumns = array_keys($schema);

            $wantedColumns = [
                'entity_id',
                'sku',
                'product_name',
                'attribute_set',
                'product_type',
                'operation_type',
                'status',
                'missing_attributes_count',
                'updated_at'
            ];

            $selectColumns = [];
            foreach ($wantedColumns as $col) {
                if (in_array($col, $existingColumns, true)) {
                    $selectColumns[] = $col;
                }
            }
            if (empty($selectColumns)) {
                $selectColumns = ['entity_id'];
            }

            $sku = trim((string)$this->getRequest()->getParam('sku', ''));
            $attributeSet = trim((string)$this->getRequest()->getParam('attribute_set', ''));
            $status = trim((string)$this->getRequest()->getParam('status', ''));
            $operationType = trim((string)$this->getRequest()->getParam('operation_type', ''));

            $select = $connection->select()->from($table, $selectColumns);

            if ($sku !== '') {
                $select->where('sku LIKE ?', '%' . $sku . '%');
            }
            if ($attributeSet !== '') {
                $select->where('attribute_set LIKE ?', '%' . $attributeSet . '%');
            }
            if ($status !== '') {
                $select->where('status = ?', $status);
            }
            if ($operationType !== '') {
                $select->where('operation_type = ?', $operationType);
            }

            $select->order('updated_at DESC');

            $items = $connection->fetchAll($select);


            foreach ($items as &$item) {
                if (!array_key_exists('product_name', $item)) {
                    $item['product_name'] = '';
                }
                if (!array_key_exists('product_type', $item)) {
                    $item['product_type'] = 'simple';
                }
                if (!array_key_exists('operation_type', $item)) {
                    $item['operation_type'] = 'create';
                }
                if (!array_key_exists('status', $item)) {
                    $item['status'] = 'incomplete';
                }
                if (!array_key_exists('missing_attributes_count', $item)) {
                    $item['missing_attributes_count'] = 0;
                }

                // Extract parent and child from raw_data JSON
                $item['parent'] = '';
                $item['child'] = '';
                if (isset($item['raw_data'])) {
                    $rawData = json_decode($item['raw_data'], true);
                    if (is_array($rawData)) {
                        foreach ($rawData as $key => $val) {
                            $k = strtolower(trim((string)$key));
                            if ($k === 'parent') {
                                $item['parent'] = (string)$val;
                            }
                            if ($k === 'child') {
                                $item['child'] = (string)$val;
                            }
                        }
                    }
                }
            }
            unset($item);

            return $result->setData([
                'success' => true,
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
