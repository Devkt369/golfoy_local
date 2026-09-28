<?php
namespace ArtM\BrandInventory\Controller\Adminhtml\Data;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\BrandInventory\Model\Service\InventorySyncService;
use ArtM\BrandInventory\Model\Service\BatchHistoryService;

class SyncInventory extends Action
{
    protected $resultJsonFactory;
    protected $inventorySyncService;
    protected $batchHistoryService;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        InventorySyncService $inventorySyncService,
        BatchHistoryService $batchHistoryService
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->inventorySyncService = $inventorySyncService;
        $this->batchHistoryService = $batchHistoryService;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $batchId = $this->getRequest()->getParam('batch_id');

        if (!$batchId) {
            return $resultJson->setData([
                'success' => false,
                'message' => 'Batch ID required'
            ]);
        }

        try {
            // ✅ Delegate to service — controller knows nothing about business logic
            $result = $this->inventorySyncService->syncBatch($batchId);

            $adminUser = $this->_auth->getUser();
            $syncedBy = $adminUser ? $adminUser->getUsername() : 'system';
            $this->batchHistoryService->appendComment($batchId, 'synced_inventory', $syncedBy);

            return $resultJson->setData($result);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}