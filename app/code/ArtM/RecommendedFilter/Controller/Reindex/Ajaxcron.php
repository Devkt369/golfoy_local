<?php
namespace ArtM\RecommendedFilter\Controller\Reindex;
 
 
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
 
 
class Ajaxcron extends Action
{
 
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;
 
    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;
 
    protected $helperData;
    /**
     * View constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(Context $context, PageFactory $resultPageFactory,
                    JsonFactory $resultJsonFactory,
                    \ArtM\RecommendedFilter\Helper\Data $helperData)
    {
 
        $this->_resultPageFactory = $resultPageFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->helperData         = $helperData;
        parent::__construct($context);
    }
 
 
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->_resultJsonFactory->create();
        $resultPage = $this->_resultPageFactory->create();
        $params = $this->getRequest()->getParams();
        $attrid = (int) $params['rel'];
        $type = $params['type'];
        try {
            $this->helperData->runCronForAttrset($attrid, $type) ;
            $result->setData(['result' => true]);
        } catch (\Exception $e) {
          //echo $e->getMessage();
            $result->setData(['result' => false]);
        }
        
        return $result;
    }
 
}