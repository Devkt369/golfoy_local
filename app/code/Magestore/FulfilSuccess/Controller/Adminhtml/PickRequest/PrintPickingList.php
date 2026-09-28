<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\FulfilSuccess\Controller\Adminhtml\PickRequest;

use Exception;
use Laminas\Barcode\Barcode;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Backend\App\Action\Context;
use Magestore\FulfilSuccess\Model\ResourceModel\PickRequest\PickRequest\CollectionFactory;
use Magestore\FulfilSuccess\Service\PickRequest\PickRequestPrintService;

/**
 * Class PrintPickingList
 *
 * Used to print picking list
 * @SuppressWarnings(PHPMD)
 */
class PrintPickingList extends Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    
    /**
     * @var PickRequestPrintService
     */
    protected $printService;
  
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * PrintPickingList constructor.
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param PickRequestPrintService $printService
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        PickRequestPrintService $printService,
        PageFactory $resultPageFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->printService = $printService;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return Redirect
     * @throws LocalizedException|Exception
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $groupType = $this->_request->getParam('type');
        $printData = $this->printService->printPickingList($collection->getItems(), $groupType);

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->getConfig()->getTitle()->prepend(__('Picking List'));

        /* check if install barcode module */
        // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
        $isInstallBarcode = class_exists(Barcode::class);
        if (!$isInstallBarcode) {
            $resultPage->getLayout()->getBlock('fulfilsuccess.pickrequest.printpickinglist')
            ->setTemplate('Magestore_FulfilSuccess::pickRequest/print/require_barcode.phtml');
        } else {
            $resultPage->getLayout()->getBlock('fulfilsuccess.pickrequest.printpickinglist')
                ->setPrintData($printData);
        }
        return $resultPage;
    }

    /**
     * Init page
     *
     * @param Page $resultPage
     * @return Page
     */
    protected function initPage($resultPage)
    {
        $resultPage->addBreadcrumb(__('Fulfillment Success'), __('Fulfillment Success'))
            ->addBreadcrumb(__('Picking Items'), __('Picking Items'));
        return $resultPage;
    }
}
