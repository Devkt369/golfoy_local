<?php

namespace ArtM\TradeIn\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class DebugInlineEdit extends Action
{
    protected $jsonFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Debug inline edit data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        
        $requestData = [
            'isAjax' => $this->getRequest()->getParam('isAjax'),
            'items' => $this->getRequest()->getParam('items', []),
            'all_params' => $this->getRequest()->getParams()
        ];
        
        return $resultJson->setData([
            'debug' => true,
            'request_data' => $requestData
        ]);
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
