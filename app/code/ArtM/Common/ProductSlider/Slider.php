<?php
namespace ArtM\Common\Controller\ProductSlider;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;

class Slider extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
 
    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;
    public function __construct(Context $context, PageFactory $resultPageFactory, JsonFactory $resultJsonFactory)
    {
 
        $this->_resultPageFactory = $resultPageFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
 
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        $resultPage = $this->_resultPageFactory->create();
        $block = $resultPage->getLayout()
                ->createBlock('ArtM\Common\Block\ProductSlider\Slider')
                ->setTemplate('ArtM_Common::productslider/slider.phtml')
                ->toHtml();
        $result->setData(['output' => $block]);
        return $result;
    }
}