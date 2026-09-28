<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Controller\Adminhtml\Location\Location;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterfaceFactory;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\Webpos\Api\WebposManagementInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magestore\Webpos\Controller\Adminhtml\Location\Location;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * Controller Save Location
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends Location implements HttpPostActionInterface
{
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $resultLayoutFactory
     * @param ForwardFactory $resultForwardFactory
     * @param Registry $registry
     * @param LocationRepositoryInterface $locationRepository
     * @param LocationInterfaceFactory $locationInterfaceFactory
     * @param EventManager $eventManager
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        LayoutFactory $resultLayoutFactory,
        ForwardFactory $resultForwardFactory,
        Registry $registry,
        LocationRepositoryInterface $locationRepository,
        LocationInterfaceFactory $locationInterfaceFactory,
        EventManager $eventManager
    ) {
        parent::__construct(
            $context,
            $resultPageFactory,
            $resultJsonFactory,
            $resultLayoutFactory,
            $resultForwardFactory,
            $registry,
            $locationRepository,
            $locationInterfaceFactory
        );
        $this->eventManager = $eventManager;
    }

    /**
     * Function execute
     *
     * @return Page
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('location_id');
        $data = $this->getRequest()->getPostValue();
        $arr1 = $data['general_information']['sub_general_information'];
        $arr2 = isset($data['general_information']['stock_selection']) ?
            $data['general_information']['stock_selection'] :
            [];
        $arr3 = $data['general_information']['address_information'];
        $arr4 = $data['custom_receipt']['container_receipt_header'];
        $arr5 = $data['custom_receipt']['container_receipt_footer'];
        $arr6 = $data['store_selection'];
        $arr = array_merge($arr1, $arr2, $arr3, $arr4, $arr5, $arr6);

        if (!$arr) {
            return $this->redirectResult('*/*/');
        }

        /** @var WebposManagementInterface $webposManagement */
        $webposManagement = $this->_objectManager->get(WebposManagementInterface::class);
        if ($webposManagement->isMSIEnable() && $webposManagement->isWebposStandard()) {
            /** @var DefaultStockProviderInterface $defaultStockProvider */
            $defaultStockProvider = $this->_objectManager->create(DefaultStockProviderInterface::class);
            $arr['stock_id'] = $defaultStockProvider->getId();
        }

        if ($id) {
            $model = $this->locationRepository->getById($id);
        } else {
            $model = $this->locationInterfaceFactory->create();
        }

        $model->setData($arr);

        $this->eventManager->dispatch(
            'save_schedule_info_for_location',
            [
                'data' => $data,
                'model' => $model
            ]
        );

        try {
            $this->locationRepository->save($model);
            $this->eventManager->dispatch(
                'save_special_days_location_after',
                [
                    'data' => $data,
                    'locationId' => $model->getLocationId()
                ]
            );
            $this->messageManager->addSuccessMessage(__('You saved the location.'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        if ($this->getRequest()->getParam('back') == 'edit') {
            return $this->redirectResult('*/*/edit', ['id' => $model->getLocationId()]);
        }

        return $this->redirectResult('*/*/');
    }
}
