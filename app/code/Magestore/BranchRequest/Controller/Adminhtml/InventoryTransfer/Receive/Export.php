<?php
/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\Receive;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\Information;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\ReceiveProduct\CollectionFactory
    as ReceiveProductCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Export receive
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
     * @var \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\ReceiveProduct\CollectionFactory
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
     * @param ReceiveProductCollectionFactory $collectionFactory
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\DriverInterface $filesystemDriver
     * @param DateTime $dateTime
     */
    public function __construct(
        \Magestore\BranchRequest\Controller\Adminhtml\Context $context,
        ReceiveProductCollectionFactory                       $collectionFactory,
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
        $receiveId = $this->_request->getParam("id");
        /** @var \Magestore\BranchRequest\Model\InventoryTransfer\Receive $receive */
        $receive = $this->_receiveFactory->create();
        $this->_receiveResource->load($receive, $receiveId);

        /** @var \Magestore\BranchRequest\Model\InventoryTransfer $inventoryTransfer */
        $inventoryTransfer = $this->inventoryTransferFactory->create();
        $this->inventoryTransferResource->load($inventoryTransfer, $receive->getInventorytransferId());

        $name = sprintf(
            '%s_receive_note_%s.csv',
            $receive->getReceiveCode(),
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
            ['', '', '', 'Receipt Note', '', ''],
            [],
            [
                'Receipt Note No: ' . $receive->getReceiveCode(),
                '',
                '',
                '',
                '',
                '',
                'Date: ' . $this->dateTime->date('Y-m-d', $receive->getCreatedOn())
            ],
            ['Against Delivery Note: ' . $receive->getNote()],
            ['Received From: ' . $inventoryTransfer->getFrom()],
            ['Receive To: ' . $inventoryTransfer->getTo()],
            [],
            ['Sr. No.', 'Part No.', 'Item Name', 'Warehouse', 'UOM', 'Rec. Qty.', 'Cost Price', 'Amount']
        ];
        $receiveProductCollection = $receive->getReceiveItems();
        $sendingSourceCode = $inventoryTransfer->getDesWarehouseCode();
        $sourceItemTable = $receiveProductCollection->getTable('inventory_source_item');
        $receiveProductCollection->getSelect()->joinLeft(
            ['current_inventory_source_item' => $sourceItemTable],
            "main_table.product_sku = current_inventory_source_item.sku AND
                current_inventory_source_item.source_code = '$sendingSourceCode'",
            ['racket']
        );
        $data = array_merge($data, $this->generateItemsData($receiveProductCollection));
        $data[] = ['Remark: ' . $receive->getRemarks()];
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
     * @param mixed $receiveProductCollection
     * @return array
     */
    public function generateItemsData($receiveProductCollection)
    {
        $data = [];
        $i = 0;
        $totalAmount = 0;
        foreach ($receiveProductCollection as $product) {
            $i++;
            $amount = (float)$product->getData('qty') * (float)$product->getData('product_price');
            $totalAmount += $amount;
            $data[] = [
                $i,
                $product->getData('product_sku'),
                $product->getData('product_name'),
                $product->getData('racket'),
                $product->getData('UOM'),
                (float)$product->getData('qty'),
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
