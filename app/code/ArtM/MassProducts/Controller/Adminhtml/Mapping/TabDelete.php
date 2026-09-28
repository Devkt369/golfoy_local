<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Mapping;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class TabDelete extends Action
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
            if ($entityId <= 0) {
                throw new LocalizedException(__('Invalid mapping ID.'));
            }

            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_massproducts_column_mapping');
            $connection->delete($table, ['entity_id = ?' => $entityId]);

            return $result->setData([
                'success' => true,
                'message' => 'Mapping deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
