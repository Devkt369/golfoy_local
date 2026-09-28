<?php

/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Controller\Store;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\Action;
use Magestore\ClickAndCollect\Helper\Data as HelperData;

/**
 * Class Index
 *
 * Use for store list action
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * Index constructor.
     * @param Context $context
     * @param HelperData $helperData
     */
    public function __construct(
        Context $context,
        HelperData $helperData
    ) {
        parent::__construct($context);
        $this->helperData = $helperData;
    }

    /**
     * Store list action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        if (!$this->helperData->getConfigShowStoreListOnFrontend()) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Stores'));
        return $resultPage;
    }
}
