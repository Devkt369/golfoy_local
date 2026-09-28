<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformanceAdminUi\Controller\Adminhtml\Index;

use IntlDateFormatter;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Result\PageFactory;
use Magestore\WebposPerformance\Model\FlagFactory;
use Magestore\WebposPerformance\Model\Statistics\StatisticInterface;

/**
 * Class Overview
 *
 * Use for performance admin ui
 */
class Overview extends Action implements HttpGetActionInterface
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_WebposPerformanceAdminUi::reports';

    /**
     * Active menu
     */
    const MENU_ITEM = 'Magestore_WebposPerformanceAdminUi::reports_overview';

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
     * @var StatisticInterface
     */
    protected $statistic;

    /**
     * Index constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param FlagFactory $flagFactory
     * @param TimezoneInterface $timezone
     * @param StatisticInterface $statistic
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        FlagFactory $flagFactory,
        TimezoneInterface $timezone,
        StatisticInterface $statistic
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->flagFactory = $flagFactory;
        $this->timezone = $timezone;
        $this->statistic = $statistic;
    }

    /**
     * Webpos performance overview action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $this->_showLastExecutionTime();
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::MENU_ITEM);
        $resultPage->getConfig()->getTitle()->prepend(__('All Performance Logs'));
        return $resultPage;
    }

    /**
     * Add refresh statistics links
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _showLastExecutionTime()
    {
        $statistic = $this->statistic;
        $flag = $this->flagFactory->create();
        $updatedAt = $flag->getPosPerformanceStatisticUpdatedAt($statistic);
        if ($updatedAt) {
            $updatedAt = $this->timezone->formatDate(
                $updatedAt,
                IntlDateFormatter::MEDIUM,
                true
            );
        } else {
            $updatedAt = 'undefined';
        }

        $refreshStatsLink = $this->getUrl('reports/report_statistics');
        $directRefreshLink = $this->getUrl('reports/report_statistics/refreshRecent');

        $this->messageManager->addNotice(
            __(
                'Last updated: %1. To refresh last day\'s <a href="%2">statistics</a>, ' .
                'click <a href="#2" data-post="%3">here</a>.',
                $updatedAt,
                $refreshStatsLink,
                str_replace(
                    '"',
                    '&quot;',
                    json_encode(['action' => $directRefreshLink, 'data' => ['code' => $statistic->getStatisticId()]])
                )
            )
        );
        return $this;
    }
}
