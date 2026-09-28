<?php
namespace ArtM\RecommendedFilter\Controller\Reindex;
 
 
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
 
 
class Updatepoints extends Action
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
        
        $id = $this->getRequest()->getParam('id');
        
        $result = $this->_resultJsonFactory->create();
        $resultPage = $this->_resultPageFactory->create();
        
        $this->helperData->CalculatePoints( $id );
       /* try {
            $this->helperData->UpdateAllPointsToCategoryTable() ;
            $result->setData(['result' => true]);
        } catch (\Exception $e) {
           echo $e->getMessage();
            $result->setData(['result' => false]);
        }  */
        
        return $result;
    }
 
}