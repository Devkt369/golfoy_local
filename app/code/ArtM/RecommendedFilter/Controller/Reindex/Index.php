<?php

namespace ArtM\RecommendedFilter\Controller\Reindex;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
class Index extends Action
{
    protected $ResultFactory;
    protected $PageFactory;
    
    public function __construct(
        Context $context,
        PageFactory $PageFactory
    )
    {
        $this->PageFactory = $PageFactory;
        parent::__construct($context);
    }

    public function execute()
    {        
        $page = $this->PageFactory->create();
        $page->getConfig()->setRobots('NOINDEX,NOFOLLOW');
        
        return $page;
    }
}
