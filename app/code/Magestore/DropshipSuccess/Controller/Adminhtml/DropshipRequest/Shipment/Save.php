<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\DropshipSuccess\Controller\Adminhtml\DropshipRequest\Shipment;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use Magestore\DropshipSuccess\Api\Data\DropshipShipmentInterface;
use Magestore\DropshipSuccess\Api\DropshipRequestRepositoryInterface;
use Magestore\DropshipSuccess\Api\DropshipShipmentRepositoryInterface;
use Magestore\DropshipSuccess\Model\DropshipRequest\DropshipShipmentFactory;
use Magestore\DropshipSuccess\Service\DropshipRequest\DropshipShipmentService;
use Magestore\DropshipSuccess\Service\DropshipRequestService;

/**
 * Create shipment from dropship
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends \Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magestore_DropshipSuccess::save_dropship_shipment';

    /**
     * @var DropshipShipmentFactory
     */
    protected $dropshipShipmentFactory;

    /**
     * @var DropshipShipmentService
     */
    protected $dropshipShipmentService;

    /**
     * @var DropshipShipmentRepositoryInterface
     */
    protected $dropshipShipmentRepository;

    /**
     * @var DropshipRequestService
     */
    protected $dropshipRequestService;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @var DropshipRequestRepositoryInterface
     */
    protected $dropshipRequestRepository;

    /**
     * Save constructor.
     * @param Action\Context $context
     * @param ShipmentLoader $shipmentLoader
     * @param LabelGenerator $labelGenerator
     * @param ShipmentSender $shipmentSender
     * @param DropshipShipmentFactory $dropshipShipmentFactory
     * @param DropshipShipmentService $dropshipShipmentService
     * @param DropshipShipmentRepositoryInterface $dropshipShipmentRepository
     * @param DropshipRequestService $dropshipRequestService
     * @param Registry $coreRegistry
     * @param DropshipRequestRepositoryInterface $dropshipRequestRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Action\Context $context,
        ShipmentLoader $shipmentLoader,
        LabelGenerator $labelGenerator,
        ShipmentSender $shipmentSender,
        DropshipShipmentFactory $dropshipShipmentFactory,
        DropshipShipmentService $dropshipShipmentService,
        DropshipShipmentRepositoryInterface $dropshipShipmentRepository,
        DropshipRequestService $dropshipRequestService,
        Registry $coreRegistry,
        DropshipRequestRepositoryInterface $dropshipRequestRepository
    ) {
        parent::__construct($context, $shipmentLoader, $labelGenerator, $shipmentSender);
        $this->dropshipShipmentFactory = $dropshipShipmentFactory;
        $this->dropshipShipmentService = $dropshipShipmentService;
        $this->dropshipShipmentRepository = $dropshipShipmentRepository;
        $this->dropshipRequestService = $dropshipRequestService;
        $this->coreRegistry = $coreRegistry;
        $this->dropshipRequestRepository = $dropshipRequestRepository;
    }
    
    /**
     * View order detail
     *
     * @return Page|Redirect
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $isValidFormKey = $this->_formKeyValidator->validate($this->getRequest());
        $isPost = $this->getRequest()->isPost();
        if (!$isValidFormKey || !$isPost) {
            $this->messageManager->addErrorMessage(__('We can\'t save the dropship shipment right now.'));
            return $resultRedirect->setPath('dropshipsuccess/dropshiprequest/edit', ['_current' => true]);
        }

        $data = $this->getRequest()->getParams();
        $data = $this->dropshipShipmentService->validateShipmentData($data);
        try {
            /** skip update qty to warehouse after create shipment by dropship */
            $this->coreRegistry->register(DropshipShipmentInterface::CREATE_SHIPMENT_BY_DROPSHIP, true);

            $this->_eventManager->dispatch('inventory_movement_log_skip_default');
            $shipment = $this->createShipment($data);
            
            $dropship = $this->dropshipShipmentFactory->create();
            $dropship->setShipmentId($shipment->getId());
            $dropship->addData($data);
            $this->dropshipShipmentRepository->save($dropship);
            $this->dropshipShipmentService->createDropshipItem($dropship, $shipment);

            /** update prepare to ship */
            $this->dropshipRequestService->updatePrepareShipQty($shipment);

            /** update dropship request (status) */
            $this->dropshipRequestService->updateDropshipRequest($this->getRequest()->getParam('id'));

            $dropshipRequest = $this->dropshipRequestRepository->getById($this->getRequest()->getParam('id'));
            /** update supplier and shipment */
            $this->dropshipRequestService->updateSupplierShipment($dropshipRequest, $shipment);
            
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $this->messageManager->addErrorMessage(__('Cannot save dropship shipment.'));
        }
        return $resultRedirect->setPath('dropshipsuccess/dropshiprequest/edit', ['_current' => true]);
    }

    /**
     * Create magento shipment.
     *
     * @param array $data
     * @return Shipment
     * @throws LocalizedException
     */
    public function createShipment($data = [])
    {
        $shipmentLoader = $this->shipmentLoader;
        $shipmentLoader->setOrderId($data['order_id']);
        $shipmentLoader->setShipmentId(null);
        $shipmentLoader->setShipment($data['shipment']);
        $shipment = $shipmentLoader->load();
        if (!$shipment) {
            $this->_forward('noroute');
            return;
        }

        $shipment->register();
        $shipment->getOrder()->setCustomerNoteNotify(true);
        $this->_saveShipment($shipment);
        $this->shipmentSender->send($shipment);
        
        $this->messageManager->addSuccessMessage(
            __('The shipment has been created.')
        );
        return $shipment;
    }
}
