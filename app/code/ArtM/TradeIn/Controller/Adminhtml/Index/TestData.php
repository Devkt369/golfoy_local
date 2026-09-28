<?php

namespace ArtM\TradeIn\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\TradeIn\Model\ResourceModel\TradeIn\CollectionFactory;

class TestData extends Action
{
    protected $jsonFactory;
    protected $collectionFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Test data action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        
        try {
            $collection = $this->collectionFactory->create();
            $data = [];
            
            foreach ($collection as $item) {
                $data[] = $item->getData();
            }
            
            return $resultJson->setData([
                'success' => true,
                'count' => $collection->getSize(),
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_TradeIn::tradein');
    }
}
