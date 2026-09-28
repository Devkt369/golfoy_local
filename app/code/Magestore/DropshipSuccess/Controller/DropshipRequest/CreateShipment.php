<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\DropshipSuccess\Controller\DropshipRequest;

use Exception;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Shipment;
use Magestore\DropshipSuccess\Api\Data\DropshipShipmentInterface;
use Magestore\DropshipSuccess\Controller\AbstractSupplier;

/**
 * Create shipment from dropship request
 */
class CreateShipment extends AbstractSupplier implements HttpPostActionInterface
{
    /**
     * Default customer account page
     *
     * @return void
     */
    public function execute()
    {
        try {
            $this->checkLogin();
            $data = $this->getRequest()->getPostValue();
            $items = $data['shipment']['items'];
            if (array_sum($items) <= 0) {
                $this->messageManager->addErrorMessage(__('Please select qty for product(s) to create shipment!'));
                return $this->_redirect('*/*/viewDropship', ['dropship_id' => $data['dropship_request_id']]);
            }
            $carrier = $this->getRequest()->getPost('tracking_carrier');
            $number = $this->getRequest()->getPost('tracking_number');
            $title = $this->getRequest()->getPost('tracking_title');

            $shipmentLoader = $this->shipmentLoader;
            $shipmentLoader->setOrderId($data['order_id']);
            $shipmentLoader->setShipmentId(null);
            $shipmentLoader->setShipment($data['shipment']);

            $trackingData = [];
            if ($carrier && $number) {
                $trackingData[1] = [
                    'carrier_code' => $carrier,
                    'title' => $title,
                    'number' => $number
                ];
            }
            $shipmentLoader->setTracking($trackingData);
            $this->coreRegistry->register(DropshipShipmentInterface::CREATE_SHIPMENT_BY_DROPSHIP, true);
            $shipment = $shipmentLoader->load();
            if ($shipment) {
                /** create sales shipment track */
                $shipment->register();
                $this->_eventManager->dispatch('inventory_movement_log_skip_default');
                $this->_saveShipment($shipment);
                $this->shipmentSender->send($shipment);
                $dropshipRequestId = $data['dropship_request_id'];
                $dropshipRequest = $this->dropshipRequestRepository->getById($dropshipRequestId);

                /** @var array $dataUpdateShippedQtys */
                $dataUpdateShippedQtys = $this->dropshipRequestService->getDataToUpdateShippedQty(
                    $data['shipment']['items']
                );

                /** update shipped qty to dropship request */
                $this->dropshipRequestService->updateShippedQtys($dropshipRequestId, $dataUpdateShippedQtys);

                /** create dropshipment*/
                $this->dropshipRequestService->createDropshipShipmentByShipment($shipment, $dropshipRequestId);

                /** update prepare to ship */
                $this->dropshipRequestService->updatePrepareShipQty($shipment);

                /** update dropship request (status) */
                $this->dropshipRequestService->updateDropshipRequest($dropshipRequestId);

                /** update supplier and shipment */
                $this->dropshipRequestService->updateSupplierShipment($dropshipRequest, $shipment);

                /** send email confirm shipped to admin */
                $this->emailService->sendDropshipShipmentToAdmin($shipment, $dropshipRequest);

                $this->messageManager->addSuccessMessage(__('You created shipment!'));
                $this->_redirect('*/*/viewDropship', ['dropship_id' => $data['dropship_request_id']]);
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Cannot create shipment! Please try again!'));
            $this->_redirect('*/*/viewDropship', ['dropship_id' => $data['dropship_request_id']]);
        }
    }

    /**
     * Save shipment and order in one transaction
     *
     * @param Shipment $shipment
     * @return $this
     */
    public function _saveShipment($shipment)
    {
        $shipment->getOrder()->setIsInProcess(true);
        $transaction = $this->_objectManager->create(
            Transaction::class
        );
        $transaction->addObject(
            $shipment
        )->addObject(
            $shipment->getOrder()
        )->save();

        return $this;
    }
}
