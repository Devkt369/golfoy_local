<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Draft;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use ArtM\MassProducts\Model\Import\DraftCompletenessService;

class TabSetMoveToQueue extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;
    private $completenessService;

    public function __construct(Context $context, ResourceConnection $resource, DraftCompletenessService $completenessService)
    {
        parent::__construct($context);
        $this->resource = $resource;
        $this->completenessService = $completenessService;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $ids = (array)$this->getRequest()->getParam('ids', []);
            $ids = array_values(array_filter(array_map('intval', $ids)));
            if (empty($ids)) {
                throw new LocalizedException(__('No drafts selected.'));
            }

            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');

            $rows = $connection->fetchAll(
                $connection->select()->from($draftTable)->where('entity_id IN (?)', $ids)
            );
            foreach ($rows as $row) {
                if ((string)($row['status'] ?? '') !== 'ready') {
                    // Compute completeness to get debug info
                    $completeness = $this->completenessService->computeDraftCompleteness($row);
                    $missingCodes = $completeness['missing_codes'] ?? [];
                    $totalRequired = $completeness['total_required'] ?? 0;
                    $filledRequired = $completeness['filled_required'] ?? 0;
                    
                    $debugInfo = "Draft ID: {$row['entity_id']}\n";
                    $debugInfo .= "Required attributes: {$totalRequired}\n";
                    $debugInfo .= "Filled attributes: {$filledRequired}\n";
                    if (!empty($missingCodes)) {
                        $debugInfo .= "Missing attributes: " . implode(', ', $missingCodes) . "\n";
                    }
                    
                    throw new LocalizedException(__('Draft contains missing required attributes. Debug info: %1', $debugInfo));
                }
            }

            // Update status to 'ready' for selected drafts
            $connection->update($draftTable, [
                'status' => 'ready',
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ], ['entity_id IN (?)' => $ids]);

            return $result->setData([
                'success' => true,
                'message' => 'Selected drafts moved to ready.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
