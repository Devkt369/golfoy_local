<?php
/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\Delivery;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magestore\BranchRequest\Model\InventoryTransfer\Delivery;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\DeliveryProduct\CollectionFactory
    as DeliveryProductCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\Information;

/**
 * Export delivery
 */
class Export extends \Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\InventoryTransfer implements
    HttpGetActionInterface
{
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvProcessor;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\DeliveryProduct\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Framework\Filesystem\DriverInterface
     */
    protected $filesystemDriver;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * Export constructor.
     *
     * @param \Magestore\BranchRequest\Controller\Adminhtml\Context $context
     * @param DeliveryProductCollectionFactory $collectionFactory
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystemDriver
     * @param DateTime $dateTime
     */
    public function __construct(
        \Magestore\BranchRequest\Controller\Adminhtml\Context $context,
        DeliveryProductCollectionFactory                      $collectionFactory,
        \Magento\Framework\File\Csv                           $csvProcessor,
        \Magento\Framework\App\Response\Http\FileFactory      $fileFactory,
        \Magento\Framework\Filesystem                         $filesystem,
        \Magento\Framework\Filesystem\DriverInterface         $filesystemDriver,
        DateTime                                              $dateTime
    ) {
        parent::__construct($context);
        $this->_collectionFactory = $collectionFactory;
        $this->csvProcessor = $csvProcessor;
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        $this->filesystemDriver = $filesystemDriver;
        $this->dateTime = $dateTime;
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $curUser = $this->authSession->getUser();
        $deliveryId = $this->_request->getParam("id");
        /** @var Delivery $delivery */
        $delivery = $this->_deliveryFactory->create();
        $this->_deliveryResource->load($delivery, $deliveryId);

        /** @var \Magestore\BranchRequest\Model\InventoryTransfer $inventoryTransfer */
        $inventoryTransfer = $this->inventoryTransferFactory->create();
        $this->inventoryTransferResource->load($inventoryTransfer, $delivery->getInventorytransferId());

        $name = sprintf(
            '%s_delivery_note_%s.csv',
            $delivery->getDeliveryCode(),
            $this->dateTime->date('Y-m-d_H-i-s')
        );
        $this->getBaseDirMedia()->create('magestore/branchrequest');
        $filename = $this->getBaseDirMedia()->getAbsolutePath('magestore/branchrequest/' . $name);
        $company = $this->scopeConfigInterface->getValue(Information::XML_PATH_STORE_INFO_NAME);
        $phone = $this->scopeConfigInterface->getValue(Information::XML_PATH_STORE_INFO_PHONE);
        $address = $this->scopeConfigInterface->getValue(Information::XML_PATH_STORE_INFO_STREET_LINE1);
        $data = [
            [
                $company, '', '', '', '', '', 'Print Date: ' . $this->dateTime->date(
                    'Y-m-d'
                )
            ],
            ['TEL: ' . $phone, '', '', '', '', '', 'Print Time: ' . $this->dateTime->date('H:i:s')],
            [$address],
            [],
            ['', '', '', 'Delivery Note', '', ''],
            [],
            [
                'Delivery Note No.: ' . $delivery->getDeliveryCode(),
                '',
                '',
                '',
                '',
                '',
                'Date: ' . $this->dateTime->date('Y-m-d', $delivery->getCreatedOn())
            ],
            ['Delivery From: ' . $inventoryTransfer->getFrom()],
            ['Delivery To: ' . $inventoryTransfer->getTo()],
            ['Ref. Brn.Req. No.: ' . $delivery->getNote()],
            [],
            ['Sr. No.', 'Part No.', 'Item Name', 'Warehouse', 'Avail Qty', 'Qty', 'UOM', 'POS Price', 'Total Price']
        ];
        $deliveryProductCollection = $delivery->getDeliveryItems();
        $sendingSourceCode = $inventoryTransfer->getDesWarehouseCode();
        $sourceItemTable = $deliveryProductCollection->getTable('inventory_source_item');
        $deliveryProductCollection->getSelect()->joinLeft(
            ['current_inventory_source_item' => $sourceItemTable],
            "main_table.product_sku = current_inventory_source_item.sku AND
                current_inventory_source_item.source_code = '$sendingSourceCode'",
            ['racket']
        );
        $data = array_merge($data, $this->generateItemsData($deliveryProductCollection));
        $data[] = ['Remark: ' . $delivery->getRemarks()];
        $data[] = ['( Generated By )', '', '', '( Checked By )', '', '', '', '( Verified By )'];
        $data[] = [];
        $data[] = [];
        $data[] = [];
        $data[] = ['Printed By: ' . $curUser->getUserName()];
        $this->csvProcessor->saveData($filename, $data);

        return $this->fileFactory->create(
            $name,
            $this->filesystemDriver->fileGetContents($filename),
            DirectoryList::VAR_DIR
        );
    }

    /**
     * Get base dir media
     *
     * @return \Magento\Framework\Filesystem\Directory\WriteInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getBaseDirMedia()
    {
        return $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * Generate data
     *
     * @param mixed $deliveryProductCollection
     * @return array
     */
    public function generateItemsData($deliveryProductCollection)
    {
        $data = [];
        $i = 0;
        $totalAmount = 0;
        foreach ($deliveryProductCollection as $product) {
            $i++;
            $amount = (float)$product->getData('qty') * (float)$product->getData('product_price');
            $totalAmount += $amount;
            $data[] = [
                $i,
                $product->getData('product_sku'),
                $product->getData('product_name'),
                $product->getData('racket'),
                (float)$product->getData('avail_qty'),
                (float)$product->getData('qty'),
                $product->getData('UOM'),
                (float)$product->getData('product_price'),
                $amount,
            ];
        }
        $data[] = [];
        $data[] = ['', '', '', '', '', '', 'Amount', $totalAmount];
        $data[] = ['', '', '', '', '', '', 'Tax Amount', 0];
        $data[] = ['', '', '', '', '', '', 'Total Amount', $totalAmount];
        return $data;
    }
}
