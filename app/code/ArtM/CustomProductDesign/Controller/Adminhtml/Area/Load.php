<?php
namespace ArtM\CustomProductDesign\Controller\Adminhtml\Area;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;

class Load extends Action
{
    protected $resultJsonFactory;
    protected $resource;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resource
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resource = $resource;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $productId = $this->getRequest()->getParam('product_id');
        $imageFile = $this->getRequest()->getParam('image_file');
        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_customprint_area');
        $areas = [];
        if ($productId && $imageFile) {
            $select = $connection->select()
                ->from($table)
                ->where('product_id = ?', $productId)
                ->where('image = ?', $imageFile);
            $areas = $connection->fetchAll($select);
        }
        return $result->setData(['areas' => $areas]);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_CustomProductDesign::customprint_save');
    }
}
