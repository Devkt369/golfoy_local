<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Processing;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class TabMatrix extends Action
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
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');
            $rows = $connection->fetchAll(
                $connection->select()->from($draftTable)->where('status = ?', 'processing')->order('entity_id ASC')
            );
            return $result->setData(['success' => true, 'rows' => $rows]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
