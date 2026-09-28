<?php
namespace ArtM\CustomProductDesign\Controller\Area;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\CustomProductDesign\Model\AreaFactory;

class Save extends Action
{
    protected $resultJsonFactory;
    protected $areaFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AreaFactory $areaFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->areaFactory = $areaFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $data = $this->getRequest()->getPostValue();
        $response = ['success' => false];
        if (!empty($data['product_id']) && !empty($data['image']) && !empty($data['area_id']) && !empty($data['content'])) {
            try {
                $area = $this->areaFactory->create()->load($data['area_id']);
                if ($area->getId()) {
                    $area->setData('custom_content', json_encode($data['content']));
                    $area->save();
                    $response['success'] = true;
                }
            } catch (\Exception $e) {
                $response['error'] = $e->getMessage();
            }
        } else {
            $response['error'] = 'Missing required data.';
        }
        return $result->setData($response);
    }
}
