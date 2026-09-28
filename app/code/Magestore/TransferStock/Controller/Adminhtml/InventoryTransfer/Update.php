<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\TransferStock\Controller\Adminhtml\InventoryTransfer;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magestore\TransferStock\Model\InventoryTransfer\Option\Stage;
use Magestore\TransferStock\Controller\Adminhtml\InventoryTransfer\InventoryTransfer as InventoryTransferController;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Class Index
 */
class Update extends InventoryTransferController implements HttpPostActionInterface
{
    /**
     * History action.
     *
     * @return ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        $isGenerallyUpdate = true;

        /** @var \Magestore\TransferStock\Model\InventoryTransfer $model */
        $model = $this->inventoryTransferFactory->create();
        $model->load($data['transfer_summary']['general_information']['inventorytransfer_id']);
        if (!$model->getId()) {
            $this->messageManager->addErrorMessage(
                __(
                    'Could not find inventory transfer request with ID: %1',
                    $data['transfer_summary']['general_information']['inventorytransfer_id']
                )
            );
        }
        try {
            $model->setReason($data['transfer_summary']['general_information']['reason']);
            $this->inventoryTransferResource->save($model);
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }

        // save transfer items
        if (isset($data['transfer_summary']['product_list']['product_dynamic_grid'])
            && $model->getStage() == Stage::STAGE_NEW
        ) {
            /** @var SerializerInterface $serializer */
            $serializer = ObjectManager::getInstance()
                ->get(SerializerInterface::class);
            $this->transferManagement->setProductsForInventoryTransfer(
                $model->getId(),
                $serializer->unserialize(
                    $data['transfer_summary']['product_list']['product_dynamic_grid']['links']['product_list']
                )
            );
        }

        if ($this->getRequest()->getParam('back') == 'send') {
            $sendResult = $this->transferManagement->startToSendStock($model->getId());
            if ($sendResult['status']) {
                $this->messageManager->addSuccessMessage($sendResult['message']);
            } else {
                $this->messageManager->addErrorMessage($sendResult['message']);
            }
            $isGenerallyUpdate = false;
        }

        if (($model->getStage() == Stage::STAGE_SENT || $model->getStage() == Stage::STAGE_RECEIVING)
            && isset($data['transfer_summary']['receive_modal']['receive_product_list']['receive_product_dynamic_grid'])
        ) {
            /** @var SerializerInterface $serializer */
            $serializer = ObjectManager::getInstance()
                ->get(SerializerInterface::class);
            $dataReceiveProductList = $data['transfer_summary']['receive_modal']['receive_product_list'];
            $this->receiveProducts(
                $model->getId(),
                $serializer->unserialize(
                    $dataReceiveProductList['receive_product_dynamic_grid']['links']['product_list']
                )
            );
            $isGenerallyUpdate = false;
        }

        if ($isGenerallyUpdate) {
            $this->messageManager->addSuccessMessage(__('General information was saved successfully.'));
        }

        if ($this->getRequest()->getParam('back') == 'edit') {
            return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
        }
        return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
    }

    /**
     * Receive tranfering products
     *
     * @param int $transferId
     * @param array $productList
     */
    public function receiveProducts($transferId, $productList)
    {
        $dataReceive = [];
        foreach ($productList as $item) {
            $dataReceive[] = [
                'product_id' => $item['id'],
                'product_sku' => $item['sku'],
                'product_name' => $item['name'],
                'qty' => $item['qty_to_receive']
            ];
        }
        try {
            if ($this->transferManagement->receiveProducts($transferId, $dataReceive)) {
                $this->messageManager->addSuccessMessage(__('Receiving was created successfully!'));
            } else {
                $this->messageManager->addErrorMessage(__('Cannot receive products in this inventory transfer.'));
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
}
