<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Mapping;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;

    public function __construct(
        Context $context,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resource = $resource;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $uploadId = (int)$this->getRequest()->getParam('upload_id');
            $mappingsJson = (string)$this->getRequest()->getParam('mappings', '[]');
            $mappings = json_decode($mappingsJson, true);

            if ($uploadId <= 0) {
                throw new LocalizedException(__('Invalid upload ID.'));
            }
            if (!is_array($mappings)) {
                throw new LocalizedException(__('Invalid mappings payload.'));
            }

            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_massupload_mapping');
            $globalTable = $connection->getTableName('artm_massproducts_column_mapping');
            $tableColumns = $connection->describeTable($table);
            $hasIsNewColumn = isset($tableColumns['is_new']);
            $connection->delete($table, ['upload_id = ?' => $uploadId]);

            $existingGlobalRows = $connection->fetchAll(
                $connection->select()->from($globalTable, ['csv_column', 'attribute_code'])
            );
            $existingGlobalMap = [];
            foreach ($existingGlobalRows as $gr) {
                $csv = strtolower(trim((string)($gr['csv_column'] ?? '')));
                if ($csv === '') {
                    continue;
                }
                $existingGlobalMap[$csv] = trim((string)($gr['attribute_code'] ?? ''));
            }

            $rows = [];
            foreach ($mappings as $mapping) {
                $csvColumn = isset($mapping['csv_column']) ? trim((string)$mapping['csv_column']) : '';
                $attributeCode = isset($mapping['attribute_code']) ? trim((string)$mapping['attribute_code']) : '';
                if ($csvColumn === '' || $attributeCode === '') {
                    continue;
                }

                $lookupKey = strtolower($csvColumn);
                $previous = $existingGlobalMap[$lookupKey] ?? null;
                $isNewMapping = ($previous === null || strcasecmp((string)$previous, $attributeCode) !== 0) ? 1 : 0;

                $rows[] = [
                    'upload_id' => $uploadId,
                    'csv_column' => $csvColumn,
                    'attribute_code' => $attributeCode,
                    'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                ];
                if ($hasIsNewColumn) {
                    $rows[count($rows) - 1]['is_new'] = $isNewMapping;
                }
            }

            if (!empty($rows)) {
                $connection->insertMultiple($table, $rows);

                $now = (new \DateTime())->format('Y-m-d H:i:s');
                foreach ($rows as $row) {
                    $isNewFlag = array_key_exists('is_new', $row) ? (int)$row['is_new'] : 1;
                    if ($isNewFlag === 1) {
                        $connection->insertOnDuplicate(
                            $globalTable,
                            [
                                'csv_column' => (string)$row['csv_column'],
                                'attribute_code' => (string)$row['attribute_code'],
                                'created_at' => $now,
                                'updated_at' => $now,
                            ],
                            ['attribute_code', 'updated_at']
                        );
                    }
                }
            }

            return $result->setData([
                'success' => true,
                'saved_rows' => count($rows),
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
