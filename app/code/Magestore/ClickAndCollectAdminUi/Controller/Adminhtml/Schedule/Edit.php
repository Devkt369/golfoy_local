<?php

/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Controller\Adminhtml\Schedule;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magestore\ClickAndCollectApi\Api\DataProvider\GetScheduleByIdInterface;

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
    const ADMIN_RESOURCE = 'Magestore_ClickAndCollectAdminUi::schedule';

    /**
     * Active menu
     */
    const MENU_ITEM = 'Magestore_ClickAndCollectAdminUi::schedule';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var GetScheduleByIdInterface
     */
    protected $getScheduleById;

    /**
     * Edit constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param GetScheduleByIdInterface $getScheduleById
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        GetScheduleByIdInterface $getScheduleById
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->getScheduleById = $getScheduleById;
    }

    /**
     * Stocktaking list action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::MENU_ITEM);
        if ($this->getRequest()->getParam('schedule_id')) {
            try {
                $schedule = $this->getScheduleById->execute(
                    (int) $this->getRequest()->getParam('schedule_id')
                );
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Schedule %1', $schedule->getScheduleName()));
            } catch (\Exception $exception) {
                $this->messageManager->addErrorMessage(__('The schedule does not exist.'));
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Schedule'));
        }

        return $resultPage;
    }
}
