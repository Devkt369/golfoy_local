<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Controller\Adminhtml\SpecialDay;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;
use Magestore\ClickAndCollectApi\Api\DataProvider\GetSpecialDayByIdInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Edit
 *
 * Use for Edit action
 */
class Edit extends Action implements HttpGetActionInterface
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_ClickAndCollectAdminUi::specialday';

    /**
     * Active menu
     */
    const MENU_ITEM = 'Magestore_ClickAndCollectAdminUi::specialday';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var GetSpecialDayByIdInterface
     */
    protected $getSpecialDayById;

    /**
     * Edit constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param LoggerInterface $logger
     * @param GetSpecialDayByIdInterface $getSpecialDayById
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        LoggerInterface $logger,
        GetSpecialDayByIdInterface $getSpecialDayById
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->getSpecialDayById = $getSpecialDayById;
    }

    /**
     * Execute
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::MENU_ITEM);
        if ($this->getRequest()->getParam(SpecialDayInterface::SPECIAL_DAY_ID)) {
            try {
                $specialDay = $this->getSpecialDayById->execute(
                    (int) $this->getRequest()->getParam(SpecialDayInterface::SPECIAL_DAY_ID)
                );
                $resultPage->getConfig()->getTitle()->prepend(
                    __('Edit Special Day %1', $specialDay->getSpecialDayName())
                );
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                $this->messageManager->addErrorMessage(__('The special day does not exist.'));
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Special Day'));
        }

        return $resultPage;
    }
}
