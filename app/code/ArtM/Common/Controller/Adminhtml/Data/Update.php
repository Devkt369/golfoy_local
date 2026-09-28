<?php
namespace Artm\Common\Controller\Adminhtml\Data;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class Update extends Action
{
    protected $resultPageFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        die('hii');
        return $this->resultPageFactory->create();
    }
}