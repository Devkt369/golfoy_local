<?php
namespace ArtM\Loginbyphone\Controller\Loginpage;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    protected $resultFactory;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\ResultFactory $resultFactory
    )
    {
        $this->_pageFactory = $pageFactory;
        $this->resultFactory = $resultFactory;
        return parent::__construct($context);
    }
    
    public function execute()
    {  
        $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $redirect->setUrl('/');
        return $redirect;
        //return $this->_pageFactory->create();
    }
}