<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Draft;

use ArtM\MassProducts\Model\DraftFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resultPageFactory;
    private $draftFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        DraftFactory $draftFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->draftFactory = $draftFactory;
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        if ($id <= 0) {
            $this->messageManager->addErrorMessage(__('Invalid draft id.'));
            /** @var Redirect $redirect */
            $redirect = $this->resultRedirectFactory->create();
            return $redirect->setPath('*/*/index');
        }

        $draft = $this->draftFactory->create()->load($id);
        if (!$draft->getId()) {
            $this->messageManager->addErrorMessage(__('Draft not found.'));
            /** @var Redirect $redirect */
            $redirect = $this->resultRedirectFactory->create();
            return $redirect->setPath('*/*/index');
        }

        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('ArtM_MassProducts::mass_upload_product');
        $page->getConfig()->getTitle()->prepend(__('Edit Draft #%1', $id));
        return $page;
    }
}
