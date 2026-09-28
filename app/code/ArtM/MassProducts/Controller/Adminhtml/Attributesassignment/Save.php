<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Attributesassignment;

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
            $setId = (int)$this->getRequest()->getParam('attribute_set_id', 0);
            $enabledCodes = (array)$this->getRequest()->getParam('enabled_codes', []);

            $connection = $this->resource->getConnection();
            $assignmentTable = $connection->getTableName('artm_massproducts_attr_assignment');
            if (!$connection->isTableExists($assignmentTable)) {
                throw new LocalizedException(__('Assignment table is missing. Please run setup:upgrade.'));
            }

            $enabledMap = [];
            foreach ($enabledCodes as $code) {
                $c = trim((string)$code);
                if ($c !== '') {
                    $enabledMap[$c] = 1;
                }
            }

            // load existing rows for this set and update/insert accordingly
            $existing = $connection->fetchAll(
                $connection->select()->from($assignmentTable, ['attribute_code'])->where('attribute_set_id = ?', $setId)
            );

            $existingCodes = [];
            foreach ($existing as $row) {
                $existingCodes[] = (string)$row['attribute_code'];
            }

            $allCodes = array_unique(array_merge($existingCodes, array_keys($enabledMap)));
            $now = (new \DateTime())->format('Y-m-d H:i:s');
            foreach ($allCodes as $code) {
                $isEnabled = isset($enabledMap[$code]) ? 1 : 0;
                $connection->insertOnDuplicate(
                    $assignmentTable,
                    [
                        'attribute_set_id' => $setId,
                        'attribute_code' => $code,
                        'is_enabled' => $isEnabled,
                        'updated_at' => $now,
                    ],
                    ['is_enabled', 'updated_at']
                );
            }

            return $result->setData([
                'success' => true,
                'message' => 'Assignments saved successfully.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
