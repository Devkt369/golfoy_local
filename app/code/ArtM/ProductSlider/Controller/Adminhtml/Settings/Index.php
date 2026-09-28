<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::settings';

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect|ResponseInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('adminhtml/system_config/edit', ['section' => 'artm_productslider']);

        return $resultRedirect;
    }
}
