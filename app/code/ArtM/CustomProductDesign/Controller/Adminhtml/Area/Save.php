<?php
namespace ArtM\CustomProductDesign\Controller\Adminhtml\Area;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResourceConnection;

class Save extends Action implements HttpPostActionInterface
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
        $areas = $this->getRequest()->getParam('areas');
        if (!$areas) {
            return $result->setData(['success' => false, 'message' => 'No data received.', 'debug' => $_POST]);
        }
        $areas = json_decode($areas, true);
        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_customprint_area');
        $debugData = [];
        try {
            foreach ($areas as $area) {
                try {
                    // Check if area exists for product_id, image, and area_data (x, y, width, height)
                    $select = $connection->select()
                        ->from($table, ['area_id'])
                        ->where('product_id = ?', $area['product_id'])
                        ->where('image = ?', $area['image_file'])
                        ->where('area_data = ?', json_encode([
                            'x' => $area['x'],
                            'y' => $area['y'],
                            'width' => $area['width'],
                            'height' => $area['height']
                        ]));
                    $existingId = $connection->fetchOne($select);
                    $data = [
                        'product_id' => $area['product_id'],
                        'image' => $area['image_file'],
                        'area_data' => json_encode([
                            'x' => $area['x'],
                            'y' => $area['y'],
                            'width' => $area['width'],
                            'height' => $area['height']
                        ]),
                        'allowed_types' => $area['printing_type'],
                        'extra_price_text' => $area['extra_price_text'],
                        'extra_price_image' => $area['extra_price_image'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'additional_data' => isset($area['additional_data']) ? $area['additional_data'] : null
                    ];
                    if ($existingId) {
                        $connection->update($table, $data, ['area_id = ?' => $existingId]);
                        $debugData[] = ['area' => $area, 'status' => 'updated'];
                    } else {
                        $data['created_at'] = date('Y-m-d H:i:s');
                        $connection->insert($table, $data);
                        $debugData[] = ['area' => $area, 'status' => 'inserted'];
                    }
                } catch (\Exception $ex) {
                    $debugData[] = ['area' => $area, 'error' => $ex->getMessage()];
                }
            }
            return $result->setData(['success' => true, 'message' => 'Configuration saved.', 'debug' => $debugData]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage(), 'debug' => $debugData]);
        }
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_CustomProductDesign::customprint_save');
    }
}
