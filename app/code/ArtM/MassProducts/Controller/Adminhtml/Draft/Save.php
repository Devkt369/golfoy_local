<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Draft;

use ArtM\MassProducts\Model\Import\DraftCompletenessService;
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
    private $completenessService;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        DraftCompletenessService $completenessService
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->completenessService = $completenessService;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $draftId = (int)$this->getRequest()->getParam('draft_id');
            $values = (array)$this->getRequest()->getParam('values', []);
            $storeId = (int)$this->getRequest()->getParam('store_id', 0);

            if ($draftId <= 0) {
                throw new LocalizedException(__('Invalid draft id.'));
            }

            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');
            $valueTable = $connection->getTableName('artm_massproducts_draft_values');

            $draft = $connection->fetchRow(
                $connection->select()->from($draftTable)->where('entity_id = ?', $draftId)
            );
            if (!$draft) {
                throw new LocalizedException(__('Draft not found.'));
            }

            $now = (new \DateTime())->format('Y-m-d H:i:s');
            foreach ($values as $code => $value) {
                $attributeCode = trim((string)$code);
                if ($attributeCode === '') {
                    continue;
                }

                $storedValue = is_array($value) ? implode(',', $value) : (string)$value;
                $connection->insertOnDuplicate(
                    $valueTable,
                    [
                        'draft_id' => $draftId,
                        'attribute_code' => $attributeCode,
                        'value' => $storedValue,
                        'store_id' => $storeId,
                        'updated_at' => $now,
                    ],
                    ['value', 'updated_at']
                );
            }

            $draft = $connection->fetchRow(
                $connection->select()->from($draftTable)->where('entity_id = ?', $draftId)
            );
            $summary = $this->completenessService->computeDraftCompleteness((array)$draft);

            $connection->update(
                $draftTable,
                [
                    'status' => (string)$summary['status'],
                    'missing_attributes_count' => (int)$summary['missing_count'],
                    'updated_at' => $now,
                ],
                ['entity_id = ?' => $draftId]
            );

            return $result->setData([
                'success' => true,
                'message' => 'Draft saved successfully.',
                'status' => $summary['status'],
                'missing_attributes_count' => $summary['missing_count'],
                'filled_required' => $summary['filled_required'],
                'total_required' => $summary['total_required'],
                'progress_percent' => $summary['progress_percent'],
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
