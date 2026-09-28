<?php

/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Controller\Store;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magestore\ClickAndCollect\Helper\Data as HelperData;
use Magestore\ClickAndCollectFrontend\Model\DataProvider\Store\View\CurrentStoreProvider;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class View
 *
 * Use for store view action
 */
class View extends Action implements HttpGetActionInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CurrentStoreProvider
     */
    protected $currentStoreProvider;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * View constructor.
     * @param Context $context
     * @param RequestInterface $request
     * @param LocationRepositoryInterface $locationRepository
     * @param LoggerInterface $logger
     * @param CurrentStoreProvider $currentStoreProvider
     * @param HelperData $helperData
     */
    public function __construct(
        Context $context,
        RequestInterface $request,
        LocationRepositoryInterface $locationRepository,
        LoggerInterface $logger,
        CurrentStoreProvider $currentStoreProvider,
        HelperData $helperData
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->locationRepository = $locationRepository;
        $this->logger = $logger;
        $this->currentStoreProvider = $currentStoreProvider;
        $this->helperData = $helperData;
    }

    /**
     * Store view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        if (!$this->helperData->getConfigShowStoreListOnFrontend()) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }
        $id = $this->request->getParam('id');
        try {
            $store = $this->locationRepository->getById($id);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $store = null;
        }
        if (!$store || !$store->getLocationId()) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }

        $this->currentStoreProvider->setCurrentStore($store);
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->set(__('Store Detail'));
        return $resultPage;
    }
}
