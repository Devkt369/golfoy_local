<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Mapping;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class TabSave extends Action
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
            $entityId = (int)$this->getRequest()->getParam('entity_id', 0);
            $csvColumn = trim((string)$this->getRequest()->getParam('csv_column', ''));
            $attributeCode = trim((string)$this->getRequest()->getParam('attribute_code', ''));


            $csvColLower = strtolower($csvColumn);
            if ($csvColLower === 'parent' || $csvColLower === 'child') {
                return $result->setData([
                    'success' => false,
                    'message' => 'Mapping for parent/child columns is not allowed.'
                ]);
            }
            if ($csvColumn === '' || $attributeCode === '') {
                throw new LocalizedException(__('Column name and mapped attribute id are required.'));
            }

            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_massproducts_column_mapping');

            $duplicateId = (int)$connection->fetchOne(
                $connection->select()
                    ->from($table, ['entity_id'])
                    ->where('LOWER(csv_column) = ?', strtolower($csvColumn))
                    ->where('entity_id != ?', $entityId)
                    ->limit(1)
            );
            if ($duplicateId > 0) {
                throw new LocalizedException(__('Column mapping already exists.'));
            }

            $now = (new \DateTime())->format('Y-m-d H:i:s');
            if ($entityId > 0) {
                $connection->update(
                    $table,
                    [
                        'csv_column' => $csvColumn,
                        'attribute_code' => $attributeCode,
                        'updated_at' => $now,
                    ],
                    ['entity_id = ?' => $entityId]
                );
            } else {
                $connection->insert($table, [
                    'csv_column' => $csvColumn,
                    'attribute_code' => $attributeCode,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $entityId = (int)$connection->lastInsertId($table);
            }

            return $result->setData([
                'success' => true,
                'entity_id' => $entityId,
                'message' => 'Mapping saved successfully.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
