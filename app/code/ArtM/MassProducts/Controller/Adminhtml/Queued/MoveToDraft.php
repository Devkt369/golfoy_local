<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Queued;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class MoveToDraft extends Action
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
            $ids = (array)$this->getRequest()->getParam('ids', []);
            if (empty($ids)) {
                $single = (int)$this->getRequest()->getParam('id', 0);
                if ($single > 0) {
                    $ids = [$single];
                }
            }

            $ids = array_values(array_filter(array_map('intval', $ids)));
            if (empty($ids)) {
                throw new LocalizedException(__('No queued rows selected.'));
            }

            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');

            $updated = $connection->update(
                $draftTable,
                [
                    'status' => 'draft',
                    'updated_at' => (new \DateTime())->format('Y-m-d H:i:s')
                ],
                [
                    'entity_id IN (?)' => $ids
                ]
            );

            return $result->setData([
                'success' => true,
                'updated' => (int)$updated,
                'message' => (string)__('Rows moved to Draft: %1', (int)$updated),
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
