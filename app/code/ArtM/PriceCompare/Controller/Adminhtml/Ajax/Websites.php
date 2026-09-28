<?php
namespace ArtM\PriceCompare\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Websites extends Action
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
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('artm_pricecompare_website');
        $websites = $connection->fetchAll('SELECT website_id, name FROM ' . $table);
        return $result->setData(['success' => true, 'websites' => $websites]);
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_PriceCompare::manageproducts');
    }
}
