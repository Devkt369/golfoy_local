<?php
namespace ArtM\Common\Controller\Index;
 
 
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
 
 
class Subcatontop extends Action
{
 
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;
 
    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;
 
 
    /**
     * View constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(Context $context, PageFactory $resultPageFactory, JsonFactory $resultJsonFactory)
    {
 
        $this->_resultPageFactory = $resultPageFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
 
        parent::__construct($context);
    }
 
 
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        $resultPage = $this->_resultPageFactory->create();
        //$currentProductId = $this->getRequest()->getParam('currentproduct');
        $params = $this->getRequest()->getParams();
       // $catId = $params['catId'];  $subcatConf = $params['subcatConf'];
        $block = $resultPage->getLayout()
                ->createBlock('ArtM\Common\Block\Subcat')
                ->setTemplate('ArtM_Common::subcat.phtml')
                ->setData('data',$params)
                ->toHtml();
 
        $result->setData(['output' => $block]);
        return $result;
    }
 
}