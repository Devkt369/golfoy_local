<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;

class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::group_save';

    public function __construct(
        Context $context,
        private readonly ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Forward
    {
        return $this->resultForwardFactory->create()->forward('edit');
    }
}
