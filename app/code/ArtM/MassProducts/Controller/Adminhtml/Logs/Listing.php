<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Logs;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Listing extends Action
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
            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_massproduct_activity_log');
            if (!$connection->isTableExists($table)) {
                return $result->setData(['success' => true, 'items' => []]);
            }

            $sku = trim((string)$this->getRequest()->getParam('sku', ''));
            $admin = trim((string)$this->getRequest()->getParam('admin', ''));
            $action = trim((string)$this->getRequest()->getParam('action', ''));
            $page = max(1, (int)$this->getRequest()->getParam('page', 1));
            $perPage = (int)$this->getRequest()->getParam('per_page', 50);
            if (!in_array($perPage, [50, 100, 200, 500, 1000], true)) {
                $perPage = 50;
            }
            $offset = ($page - 1) * $perPage;

            $select = $connection->select()->from($table)
                ->order('occurred_at DESC')
                ->order('entity_id DESC')
                ->limit($perPage, $offset);

            $countSelect = $connection->select()->from($table, ['cnt' => new \Zend_Db_Expr('COUNT(*)')]);

            if ($sku !== '') {
                $select->where('sku LIKE ?', '%' . $sku . '%');
                $countSelect->where('sku LIKE ?', '%' . $sku . '%');
            }
            if ($admin !== '') {
                $select->where('admin_username LIKE ?', '%' . $admin . '%');
                $countSelect->where('admin_username LIKE ?', '%' . $admin . '%');
            }
            if ($action !== '') {
                $select->where('action_type = ?', $action);
                $countSelect->where('action_type = ?', $action);
            }

            $rows = $connection->fetchAll($select);
            $total = (int)$connection->fetchOne($countSelect);

            return $result->setData([
                'success' => true,
                'items' => $rows,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $perPage > 0 ? (int)ceil($total / $perPage) : 1,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
