<?php
namespace ArtM\CustomProductDesign\Controller\Adminhtml\Area;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;

class Delete extends Action
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
        $areaData = $this->getRequest()->getParam('area_data');
        $deleteAll = $this->getRequest()->getParam('delete_all');
        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_customprint_area');
        $success = false;
        $debug = [];

        if ($deleteAll && $productId && $imageFile) {
            // Bulk delete all areas for this product/image
            try {
                $where = [
                    'product_id = ?' => $productId,
                    'image = ?' => $imageFile
                ];
                $connection->delete($table, $where);
                $success = true;
            } catch (\Exception $e) {
                $debug['error'] = $e->getMessage();
            }
        } elseif ($productId && $imageFile && $areaData) {
            // Single area delete
            try {
                $select = $connection->select()
                    ->from($table, ['area_id'])
                    ->where('product_id = ?', $productId)
                    ->where('image = ?', $imageFile)
                    ->where('area_data = ?', $areaData);
                $areaId = $connection->fetchOne($select);
                if ($areaId) {
                    $connection->delete($table, ['area_id = ?' => $areaId]);
                    $success = true;
                }
            } catch (\Exception $e) {
                $debug['error'] = $e->getMessage();
            }
        }
        return $result->setData(['success' => $success, 'debug' => $debug]);
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_CustomProductDesign::customprint_save');
    }
}
