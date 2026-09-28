<?php
namespace ArtM\SSO\Controller\Gethtml;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;

class SendOTP extends Action
{
    protected $resultPageFactory;

    public function __construct(
        Context $context, 
        PageFactory $resultPageFactory,
        JsonFactory $jsonResultFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonResultFactory = $jsonResultFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        echo "ewfewf";
        exit;
    }
}