<?php
namespace ArtM\TradeIn\Controller\Index;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Adminajax extends \Magento\Framework\App\Action\Action
{
    protected $scopeConfig;
    protected $_pageFactory;
    protected $resultFactory;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
	    \Magento\Customer\Model\Session $session,
        \ArtM\TradeIn\Model\TradeInFactory $formmodel,
        ScopeConfigInterface $scopeConfig,
        \ArtM\TradeIn\Helper\Data $helper        
    )
    {
        $this->_pageFactory = $pageFactory;
        $this->formmodel = $formmodel;  
        $this->resultJsonFactory = $resultJsonFactory;
	    $this->_session = $session;
	    $this->_helper = $helper;
        $this->scopeConfig = $scopeConfig;
        return parent::__construct($context);
    }
    public function execute()
    {
        $payment_comment = $this->getRequest()->getParam('payment_comment');
        $id = $this->getRequest()->getParam('id');
        $ref_id = $this->getRequest()->getParam('ref_id');
        $amount = $this->getRequest()->getParam('amount');

        $json = $this->resultJsonFactory->create();

        if (!$id) {
            return $json->setData([
                'success' => false,
                'message' => 'Missing ID',
            ]);
        }

        $formmodelvar = $this->formmodel->create()->load($id);

        if ($formmodelvar->getId()) {
            // Combine all values into one JSON string
            $dataToSave = json_encode([
                'payment_comment' => $payment_comment,
                'ref_id' => $ref_id,
                'amount' => $amount
            ]);

            // Save JSON to single column
            $formmodelvar->setData('payment_comment', $dataToSave);

            try {
                $formmodelvar->save();

                return $json->setData([
                    'success' => true,
                    'message' => 'Data saved as JSON successfully',
                    'saved_data' => $dataToSave
                ]);
            } catch (\Exception $e) {
                return $json->setData([
                    'success' => false,
                    'message' => 'Error saving data: ' . $e->getMessage()
                ]);
            }
        } else {
            return $json->setData([
                'success' => false,
                'message' => 'ID not found'
            ]);
        }
    }
}

