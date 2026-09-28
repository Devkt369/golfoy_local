<?php
namespace ArtM\PriceCompare\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends Action
{
    protected $resultJsonFactory;
    protected $resourceConnection;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function execute()
    {
        $type = $this->getRequest()->getParam('type');
        $name = $this->getRequest()->getParam('name');
        $result = $this->resultJsonFactory->create();
        if (!$type || !$name) {
            return $result->setData(['success' => false, 'message' => 'Missing type or name.']);
        }
        $table = null;
        if ($type === 'brand') {
            $table = $this->resourceConnection->getTableName('artm_pricecompare_brand');
        } elseif ($type === 'category') {
            $table = $this->resourceConnection->getTableName('artm_pricecompare_category');
        }
        if (!$table) {
            return $result->setData(['success' => false, 'message' => 'Invalid type.']);
        }
        $connection = $this->resourceConnection->getConnection();
        $connection->insert($table, ['name' => $name]);
        $id = $connection->lastInsertId($table);
        return $result->setData(['success' => true, 'id' => $id, 'name' => $name]);
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_PriceCompare::manageproducts');
    }
}
