<?php
namespace ArtM\PriceCompare\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Brand extends Action
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
        $result = $this->resultJsonFactory->create();
        $brandName = $this->getRequest()->getParam('name');
        if (!$brandName) {
            return $result->setData(['success' => false, 'message' => 'No brand name provided.']);
        }
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('artm_pricecompare_brand');
        $connection->insert($table, ['name' => $brandName]);
        $brandId = $connection->lastInsertId($table);
        return $result->setData(['success' => true, 'id' => $brandId, 'name' => $brandName]);
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_PriceCompare::manageproducts');
    }
}
