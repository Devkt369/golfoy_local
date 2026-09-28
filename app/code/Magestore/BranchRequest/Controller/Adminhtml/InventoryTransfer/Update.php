<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;
use Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\InventoryTransfer as InventoryTransferController;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class BranchRequest Update
 */
class Update extends InventoryTransferController implements HttpPostActionInterface
{
    /**
     * History action.
     *
     * @return ResultInterface
     * @SuppressWarnings(PHPMD)
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        $isGenerallyUpdate = true;

        /** @var \Magestore\BranchRequest\Model\InventoryTransfer $model */
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
            $model->setNotiferEmails($data['transfer_summary']['general_information']['notifer_emails']);
            $model->setRemarks($data['transfer_summary']['general_information']['remarks']);
            $this->inventoryTransferResource->save($model);
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
        }

        // save transfer items
        if (isset($data['transfer_summary']['product_list']['product_dynamic_grid'])
            && $model->getStatus() == InventoryTransferInterface::STATUS_NEW
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

        if ($this->getRequest()->getParam('action') == 'start') {
            $sendResult = $this->transferManagement->startBranchRequest($model->getId());
            if ($sendResult['status']) {
                $this->messageManager->addSuccessMessage($sendResult['message']);
            } else {
                $this->messageManager->addErrorMessage($sendResult['message']);
            }
            $isGenerallyUpdate = false;
        }

        if ($this->getRequest()->getParam('action') == 'edit_product') {
            if ($model->getStatus() == InventoryTransferInterface::STATUS_WAIT_APPROVE) {
                try {
                    $model->setStatus(InventoryTransferInterface::STATUS_NEW)->save();
                    $this->messageManager->addSuccessMessage(__('Branch request was backed to new successfully'));
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                }
            } else {
                $this->messageManager->addErrorMessage(__('Cannot back to new branch request!'));
            }
            $isGenerallyUpdate = false;
        }
        if ($this->getRequest()->getParam('action') == 'download_summary') {
            return $this->downloadSummary($model->getId());
        }

        if ($this->getRequest()->getParam('action') == 'send_email') {
            $isGenerallyUpdate = false;
            $send = $model->sendNotiferEmails();
            if (isset($send['error']) && $send['error']) {
                $this->messageManager->addErrorMessage($send['message']);
            } else {
                $this->messageManager->addSuccessMessage($send['message']);
            }
        }

        if ($this->getRequest()->getParam('action') == 'delivery') {
            /** @var SerializerInterface $serializer */
            $serializer = ObjectManager::getInstance()
                ->get(SerializerInterface::class);
            $dataDelivery = $data['transfer_summary']['delivery_modal'];
            $this->deliveryProducts(
                $model->getId(),
                $dataDelivery['delivery_information'],
                $serializer->unserialize(
                    $dataDelivery['delivery_product_list']['delivery_product_dynamic_grid']['links']['product_list']
                )
            );
            $isGenerallyUpdate = false;
        }

        if ($this->getRequest()->getParam('action') == 'receive') {
            /** @var SerializerInterface $serializer */
            $serializer = ObjectManager::getInstance()
                ->get(SerializerInterface::class);
            $dataDelivery = $data['transfer_summary']['receive_modal'];
            $this->receiveProducts(
                $model->getId(),
                $dataDelivery['receive_information'],
                $serializer->unserialize(
                    $dataDelivery['receive_product_list']['receive_product_dynamic_grid']['links']['product_list']
                )
            );
            $isGenerallyUpdate = false;
        }

        if ($isGenerallyUpdate) {
            $this->messageManager->addSuccessMessage(__('General information was saved successfully.'));
        }
        return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
    }

    /**
     * Delivery tranfering products
     *
     * @param int $transferId
     * @param array $general
     * @param array $productList
     */
    public function deliveryProducts($transferId, $general, $productList)
    {
        $dataDelivery = [];
        foreach ($productList as $item) {
            $dataDelivery[] = [
                'product_id' => $item['id'],
                'product_sku' => $item['sku'],
                'product_name' => $item['name'],
                'avail_qty' => $item['qty'],
                'qty' => $item['qty_to_delivery']
            ];
        }
        try {
            if (!count($dataDelivery)) {
                $this->messageManager->addErrorMessage(__('Click to add selected product to delivery products'));
            } elseif ($this->transferManagement->deliveryProducts($transferId, $general, $dataDelivery)) {
                $this->messageManager->addSuccessMessage(__('Delivering was created successfully!'));
            } else {
                $this->messageManager->addErrorMessage(__('Cannot delivery products in this branch request.'));
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }

    /**
     * Receive tranfering products
     *
     * @param int $transferId
     * @param array $general
     * @param array $productList
     */
    public function receiveProducts($transferId, $general, $productList)
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
            if (!count($dataReceive)) {
                $this->messageManager->addErrorMessage(__('Click to add selected product to receive products'));
            } elseif ($this->transferManagement->receiveProducts($transferId, $general, $dataReceive)) {
                $this->messageManager->addSuccessMessage(__('Receiving was created successfully!'));
            } else {
                $this->messageManager->addErrorMessage(__('Cannot receive products in this inventory transfer.'));
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $this;
    }

    /**
     * Download Summary
     *
     * @param mixed $transferId
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function downloadSummary($transferId)
    {
        $name = sha1(microtime());
        $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->create('branchrequest');
        $filename = DirectoryList::VAR_DIR . '/download_summary_' . $name . '.csv';

        $stream = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)
            ->openFile($filename, 'w+');
        $stream->lock();
        $data = [
            [__('Product Sku'), __('Product Name'), __('Qty Requested'), __('Qty Delivered'), __('Qty Received')]
        ];
        $productFromInventoryTransfer = $this->transferManagement->getProductFromInventoryTransfer($transferId);
        $dataRequest = [];
        foreach ($productFromInventoryTransfer as $productRequest) {
            $dataRequest[] = [
                'product_sku' => $productRequest->getProductSku(),
                'product_name' => $productRequest->getProductName(),
                'qty_requested' => $productRequest->getQtyRequested(),
                'qty_delivered' => $productRequest->getQtyDelivered(),
                'qty_received' => $productRequest->getQtyReceived(),
            ];
        }
        $data = array_merge($data, $dataRequest);
        foreach ($data as $row) {
            $stream->writeCsv($row);
        }
        $stream->unlock();
        $stream->close();

        return $this->fileFactory->create(
            'download_summary_branch_request.csv',
            [
                'type' => 'filename',
                'value' => $filename,
                'rm' => true  // can delete file after use
            ],
            DirectoryList::VAR_DIR
        );
    }
}
