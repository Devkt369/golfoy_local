<?php
namespace ArtM\CustomProductDesign\Controller\Area;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\CustomProductDesign\Model\AreaFactory;

class Load extends Action
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
        $productId = $this->getRequest()->getParam('product_id');
        $image = $this->getRequest()->getParam('image');
        $areas = [];

        if ($productId) {
            $collection = $this->areaFactory->create()->getCollection()
                ->addFieldToFilter('product_id', $productId);
            if ($image) {
                $collection->addFieldToFilter('image', $image);
            }
            foreach ($collection as $item) {
                $areas[] = $item->getData();
            }
        }
        return $result->setData(['areas' => $areas]);
    }
}
