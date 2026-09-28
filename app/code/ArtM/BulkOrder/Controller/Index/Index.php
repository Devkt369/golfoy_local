<?php

namespace ArtM\BulkOrder\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
class Index extends Action
{
    protected $ResultFactory;
    protected $PageFactory;
    
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        Context $context,
        PageFactory $PageFactory
    )
    {
        $this->messageManager = $messageManager;
        $this->PageFactory = $PageFactory;
        parent::__construct($context);
    }

    public function execute()
    {        
        $page = $this->PageFactory->create();
        
        $page->getConfig()->getTitle()->set((__('Request Bulk Purchase')));
        $page->getConfig()->setDescription("Looking to purchase golf gear in bulk? Submit a request for a quote on Golfoy.com. We offer a wide selection of wholesale golf products at the best prices.");
        $page->getConfig()->setKeywords("Request Bulk Purchase, Bulk order requests");
        
        $page->getConfig()->addRemotePageAsset(
            "https://staging-golfoy.hyperx.cloud/request_bulk_purchase",
            'canonical',
            ['attributes' => ['rel' => 'canonical']]
        );


        $formdata = $this->getRequest()->getParams();
        if(isset($formdata['email'])){
            if (stripos($formdata['email'], 'example.com') !== false) {
                $this->messageManager->addError(__("Please enter valid input"));
                return $page;
            }
        }
        if(isset($formdata['name'])){
            $formdata['name'] = preg_replace('/[\'"^%*();<>&+]/', '', $formdata['name']);
            if($this->isInputSafe($formdata['name']) == false){
                $this->messageManager->addError(__("Please enter valid input"));
                return $page;
            }
        }
        if(isset($formdata['comment'])){
            $formdata['comment'] = preg_replace('/[\'"^%*();<>&+]/', '', $formdata['comment']);
            if($this->isInputSafe($formdata['comment']) == false){
                $this->messageManager->addError(__("Please enter valid input"));
                return $page;
            }
        }
        if(isset($formdata['email'])){

        }
        
        $block = $page->getLayout()->getBlock('bulk_order');
        
        $block->setData('formdata', $formdata); 

        return $page;
    }

    public function isInputSafe($input)
    {
        $blacklist = [
            'select', 'insert', 'update', 'delete', 'drop', 'union', 'alter', 'create',
            'truncate', 'replace', 'rename', 'declare', 'exec', 'sleep', 'benchmark'
        ];

        $inputLower = strtolower($input);
        foreach ($blacklist as $keyword) {
            if (strpos($inputLower, $keyword) !== false) {
                return false;
            }
        }
        if (preg_match('/\b(select|sleep|union|benchmark)\b/i', $input)) {
            return false;
        }

        return true;
    }
}
