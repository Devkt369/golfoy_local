<?php

/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementAdminUi\Controller\Adminhtml\OrdersPotentialError;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Result\PageFactory;
use Magestore\InventoryMovement\Model\Flag;
use Magestore\InventoryMovement\Model\FlagFactory;
use Magento\Framework\Message\Factory as MessageFactory;

/**
 * Class Index
 *
 * Use for orders potential error list action
 */
class Index extends Action implements HttpGetActionInterface
{
    const DIRECT_REFRESH_PATH = 'inventorymovement/orderspotentialerror/refresh';
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_InventoryMovementAdminUi::orders_potential_error';

    /**
     * Active menu
     */
    const MENU_ITEM = 'Magestore_InventoryMovementAdminUi::orders_potential_error';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var FlagFactory
     */
    protected $flagFactory;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param FlagFactory $flagFactory
     * @param TimezoneInterface $timezone
     * @param MessageFactory $messageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        FlagFactory $flagFactory,
        TimezoneInterface $timezone,
        MessageFactory $messageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->flagFactory = $flagFactory;
        $this->timezone = $timezone;
        $this->messageFactory = $messageFactory;
    }

    /**
     * Orders potential error list action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $this->showLastExecutionTime();
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::MENU_ITEM);
        $resultPage->getConfig()->getTitle()->prepend(__('Potential Error Orders'));
        return $resultPage;
    }

    /**
     * Show Last Execution Time
     *
     * @return $this
     */
    public function showLastExecutionTime()
    {
        /** @var Flag $flag */
        $flag = $this->flagFactory->create();
        try {
            $updatedAt = $flag->getOrdersPotentialErrorUpdatedAt();
        } catch (LocalizedException $e) {
            $updatedAt = null;
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        if ($updatedAt) {
            $updatedAt = $this->timezone->formatDate(
                $updatedAt,
                \IntlDateFormatter::MEDIUM,
                true
            );
        } else {
            $updatedAt = 'undefined';
        }

        $directRefreshLink = $this->getUrl(self::DIRECT_REFRESH_PATH);
        $this->messageManager->addMessage(
            $this->messageFactory->create(
                MessageInterface::TYPE_NOTICE,
                __(
                    'Last updated: %1. To update the latest report, click <a href="%2">here</a>.',
                    $updatedAt,
                    $directRefreshLink
                )
            )
        );
        return $this;
    }
}
