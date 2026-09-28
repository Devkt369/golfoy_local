<?php
namespace ArtM\Forms\Controller\Adminhtml\Submissions;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class MarkAllRead extends Action
{
    protected $resultFactory;
    protected $submissionsBlock;

    public function __construct(
        Action\Context $context,
        \ArtM\Forms\Block\Adminhtml\Submissions $submissionsBlock
    ) {
        parent::__construct($context);
        $this->submissionsBlock = $submissionsBlock;
    }

    public function execute()
    {
        $formIdentifier = $this->getRequest()->getParam('form_identifier');
        $result = ['success' => false];
        if ($formIdentifier) {
            $this->submissionsBlock->markAllRead($formIdentifier);
            $result['success'] = true;
        }
        /** @var \Magento\Framework\Controller\Result\Json $jsonResult */
        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $jsonResult->setData($result);
        return $jsonResult;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_Forms::submissions');
    }
}
