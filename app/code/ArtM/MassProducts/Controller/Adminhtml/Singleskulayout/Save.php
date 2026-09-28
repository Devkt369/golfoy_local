<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Singleskulayout;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    /** @var ResourceConnection */
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
            $setId = (int)$this->getRequest()->getParam('attribute_set_id', 0);
            $rowsRaw = (string)$this->getRequest()->getParam('rows', '[]');
            $rows = json_decode($rowsRaw, true);
            if (!is_array($rows)) {
                throw new \InvalidArgumentException('Invalid rows payload.');
            }

            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_single_sku_layout');

            $connection->beginTransaction();
            try {
                $connection->delete($table, ['attribute_set_id = ?' => $setId]);

                $insertRows = [];
                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $attributeCode = strtolower(trim((string)($row['attribute_code'] ?? '')));
                    if ($attributeCode === '') {
                        continue;
                    }
                    $insertRows[] = [
                        'attribute_set_id' => $setId,
                        'attribute_code' => $attributeCode,
                        'group_name' => trim((string)($row['group_name'] ?? 'Basic Info')) ?: 'Basic Info',
                        'is_enabled' => !empty($row['is_enabled']) ? 1 : 0,
                        'sort_order' => max(0, (int)($row['sort_order'] ?? 0)),
                    ];
                }

                if ($insertRows) {
                    $connection->insertMultiple($table, $insertRows);
                }

                $connection->commit();
            } catch (\Throwable $e) {
                $connection->rollBack();
                throw $e;
            }

            return $result->setData([
                'success' => true,
                'message' => 'Single SKU layout configuration saved.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
