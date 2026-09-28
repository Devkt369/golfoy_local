<?php

namespace ArtM\BrandInventory\Controller\Adminhtml\Data;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\BrandInventory\Model\BrandInventoryFactory;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\ResourceConnection;
use ArtM\BrandInventory\Model\Service\BatchHistoryService;

class Process extends Action
{
    protected $resultJsonFactory;
    protected $brandInventoryFactory;
    protected $adminSession;
    protected $resource;
    protected $batchHistoryService;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        BrandInventoryFactory $brandInventoryFactory,
        AdminSession $adminSession,
        ResourceConnection $resource,
        BatchHistoryService $batchHistoryService
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->brandInventoryFactory = $brandInventoryFactory;
        $this->adminSession = $adminSession;
        $this->resource = $resource;
        $this->batchHistoryService = $batchHistoryService;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $selectedCols = $this->getRequest()->getParam('selected_columns');
            $sourceCode   = $this->getRequest()->getParam('source_code');
            $fileName     = $this->getRequest()->getParam('fileName');
            $sourceType   = $this->getRequest()->getParam('sourceType');
            $formatType   = $this->getRequest()->getParam('formatType');
            $headers      = $this->getRequest()->getParam('headers');

            // ✅ Optional row filter data
            $rowsJson      = $this->getRequest()->getParam('rows');
            $skippedCount  = (int)$this->getRequest()->getParam('skipped_count', 0);
            $rowFiltersJson = $this->getRequest()->getParam('row_filters', '[]');

            $adminUser = $this->adminSession->getUser();
            $uploadedBy = $adminUser ? $adminUser->getUsername() : 'guest';

            $model = $this->brandInventoryFactory->create();

            $modelData = [
                'selected_filter'  => json_encode($selectedCols),
                'file_path'        => $fileName,
                'source_code'      => $sourceCode,
                'uploaded_by'      => $uploadedBy,
                'sheet_column'     => json_encode($headers),
                'created_at'       => date('Y-m-d H:i:s')
            ];

            // ✅ Only add filter-related fields if they were sent
            if ($rowsJson) {
                $modelData['filtered_rows'] = $rowsJson;
            }
            if ($skippedCount > 0) {
                $modelData['skipped_count'] = $skippedCount;
            }
            if ($rowFiltersJson && $rowFiltersJson !== '[]') {
                $modelData['row_filters'] = $rowFiltersJson;
            }
            // ✅ Add format_type if KF source
            if ($sourceCode === 'KF' && $formatType) {
                $modelData['format_type'] = $formatType;
            }

            $model->setData($modelData);
            $model->save();

            // Save initial upload history
            $this->batchHistoryService->appendComment($model->getId(), 'uploaded', $uploadedBy);
            
            // Save selected attributes
            $this->saveSelectedAttributes($sourceCode, $selectedCols, $sourceType, $formatType);

            return $this->resultJsonFactory->create()->setData([
                'success' => true,
                'message' => 'Data saved successfully',
                'skipped' => $skippedCount
            ]);

        } catch (\Exception $e) {
            return $this->resultJsonFactory->create()->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function saveSelectedAttributes($sourceCode, $selectedColumns, $sourceType, $formatType)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('artm_brandinventory_selected_attr');

        $data = [
            'source'            => $sourceCode,
            'source_type'       => $sourceType,
            'chosen_attributes' => json_encode(array_values($selectedColumns)),
            'created_at'        => date('Y-m-d H:i:s'),
        ];

        if ($sourceCode === 'KF' && $formatType) {
            $data['format_type'] = $formatType;
        }

        $existingId = null;

        if ($sourceCode === 'KF' && $formatType) {
            $existingId = $connection->fetchOne(
                "SELECT id FROM {$table} 
                WHERE source = ? AND format_type = ? 
                ORDER BY id DESC LIMIT 1",
                [$sourceCode, $formatType]
            );
        } else {
            $existingId = $connection->fetchOne(
                "SELECT id FROM {$table} WHERE source = ? ORDER BY id DESC LIMIT 1",
                [$sourceCode]
            );
        }

        if ($existingId) {
            $connection->update($table, $data, ['id = ?' => $existingId]);
        } else {
            $connection->insert($table, $data);
        }
    }

    protected function _isAllowed()
    {
        return true;
    }
}