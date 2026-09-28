<?php
namespace ArtM\PartnersAuthentication\Controller\Adminhtml\Dealer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\PartnersAuthentication\Model\ContactUsFactory;

class DeleteRequest extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ContactUsFactory
     */
    protected $contactUsFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ContactUsFactory $contactUsFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->contactUsFactory = $contactUsFactory;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $this->contactUsFactory->create()->load($id)->delete();
                $this->messageManager->addSuccessMessage(__('Dealer account removal requested successfully.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Error occurred while requesting dealer account removal.'));
            }
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/index');

    }
}