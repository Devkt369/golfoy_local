<?php
namespace ArtM\Forms\Controller\Adminhtml\Forms;

use Magento\Backend\App\Action;

class Index extends Action
{
    const ADMIN_RESOURCE = 'ArtM_Forms::forms';

    public function execute()
    {
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('ArtM_Forms::forms');
        $resultPage->getConfig()->getTitle()->prepend(__('Forms Grid'));
        return $resultPage;
    }
}
