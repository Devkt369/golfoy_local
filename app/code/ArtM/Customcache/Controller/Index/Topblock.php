<?php
namespace ArtM\Customcache\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ObjectManager;

class Topblock extends Action
{
    protected $resultRawFactory;
    protected $resultFactory;
    protected $objectManager;

    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        ResultFactory $resultFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->resultFactory = $resultFactory;
        $this->objectManager = ObjectManager::getInstance();
    }

    public function execute()
    {
        $catId = (int) $this->getRequest()->getParam('cat_id');
        $block = $this->objectManager->create(\ArtM\Customcache\Block\TopBlock::class);
        $block->setData('cat_id', $catId);
        $html = $block->toHtml();

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setHeader('Content-Type', 'text/html');
        $resultRaw->setContents($html);
        return $resultRaw;
    }
}
