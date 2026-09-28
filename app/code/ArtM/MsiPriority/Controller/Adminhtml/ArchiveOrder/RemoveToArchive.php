<?php
/**
 * ArtM MsiPriority Module
 *
 * @category  ArtM
 * @package   ArtM_MsiPriority
 * @author    Dev
 * @license   Open Software License ("OSL") v. 3.0
 */
namespace ArtM\MsiPriority\Controller\Adminhtml\ArchiveOrder;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use ArtM\MsiPriority\Model\ArchiveOrdersFactory;

class RemoveToArchive extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    
    /**
     * @var ArchiveOrdersFactory
     */
    protected $archiveOrdersFactory;

    /**
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        ArchiveOrdersFactory $archiveOrdersFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->archiveOrdersFactory = $archiveOrdersFactory;
    } 

    /**
     * Execute method for controller
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultRedirectFactory = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');
        $archiveOrdersModel = $this->archiveOrdersFactory->create()->load($id);
        try{
            if ($archiveOrdersModel->getId()) {
                $archiveOrdersModel->delete();
                $this->messageManager->addSuccessMessage(__("Record Delete Successfully."));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("We can't delete record, Please try again."));
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('msipriority/archiveorder/index');

    }
}