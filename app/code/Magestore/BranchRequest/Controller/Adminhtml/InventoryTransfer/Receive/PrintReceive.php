<?php
/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\Receive;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\Information;
use Magestore\BranchRequest\Model\InventoryTransfer;
use Magestore\BranchRequest\Model\InventoryTransfer\Receive;
use Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\ReceiveProduct\CollectionFactory
    as ReceiveProductCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Pricing\Helper\Data;

/**
 * Branch Request Print Receive
 *
 * @SuppressWarnings(PHPMD)
 */
class PrintReceive extends \Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer\InventoryTransfer implements
    HttpGetActionInterface
{
    /**
     * Pdf
     *
     * @var \Zend_Pdf
     */
    public $pdf;

    /**
     * Y coordinate
     *
     * @var int
     */
    public $x = 50;

    /**
     * Y coordinate
     *
     * @var int
     */
    public $y = 800;

    /**
     * Number
     *
     * @var int
     */
    public $number = 0;

    /**
     * Height
     *
     * @var int
     */
    public $height = 15;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    public $fileFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    public $filesystem;

    /**
     * @var \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\ReceiveProduct\CollectionFactory
     */
    public $_collectionFactory;

    /**
     * @var DateTime
     */
    public $dateTime;

    /**
     * @var Data
     */
    public $pricingHelper;

    /**
     * Print construct
     *
     * @param \Magestore\BranchRequest\Controller\Adminhtml\Context $context
     * @param ReceiveProductCollectionFactory $collectionFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param DateTime $dateTime
     * @param Data $pricingHelper
     */
    public function __construct(
        \Magestore\BranchRequest\Controller\Adminhtml\Context $context,
        ReceiveProductCollectionFactory                       $collectionFactory,
        \Magento\Framework\App\Response\Http\FileFactory      $fileFactory,
        \Magento\Framework\Filesystem                         $filesystem,
        DateTime                                              $dateTime,
        Data                                                  $pricingHelper
    ) {
        parent::__construct($context);
        $this->_collectionFactory = $collectionFactory;
        $this->fileFactory = $fileFactory;
        $this->filesystem = $filesystem;
        $this->dateTime = $dateTime;
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     * @SuppressWarnings(PHPMD)
     */
    public function execute()
    {
        $receiveId = $this->_request->getParam("id");
        /** @var Receive $receive */
        $receive = $this->_receiveFactory->create();
        $this->_receiveResource->load($receive, $receiveId);

        /** @var InventoryTransfer $inventoryTransfer */
        $inventoryTransfer = $this->inventoryTransferFactory->create();
        $this->inventoryTransferResource->load($inventoryTransfer, $receive->getInventorytransferId());

        $name = sprintf(
            '%s_receive_note_%s.pdf',
            $receive->getReceiveCode(),
            $this->dateTime->date('Y-m-d_H-i-s')
        );
        $receiveProductCollection = $receive->getReceiveItems();
        $sendingSourceCode = $inventoryTransfer->getDesWarehouseCode();
        $sourceItemTable = $receiveProductCollection->getTable('inventory_source_item');
        $receiveProductCollection->getSelect()->joinLeft(
            ['current_inventory_source_item' => $sourceItemTable],
            "main_table.product_sku = current_inventory_source_item.sku AND
                current_inventory_source_item.source_code = '$sendingSourceCode'",
            ['racket']
        );
        $pdf = new \Zend_Pdf();
        $this->pdf = $pdf;

        $page = $this->newPage();
        $this->_setFontBold($page);
        $page->drawText(__('Receipt Note'), $this->x + 230, $this->y, 'UTF-8');
        $page->drawText(__('Receipt Note No:'), $this->x, $this->y - 2 * $this->height, 'UTF-8');
        $page->drawText(__('Against Delivery Note:'), $this->x, $this->y - 3 * $this->height, 'UTF-8');
        $page->drawText(__('Received From:'), $this->x, $this->y - 4 * $this->height, 'UTF-8');
        $page->drawText(__('Receive To:'), $this->x, $this->y - 5 * $this->height, 'UTF-8');

        $this->_setFontRegular($page);
        $page->drawText($receive->getReceiveCode(), $this->x + 120, $this->y - 2 * $this->height, 'UTF-8');
        if ($receive->getNote()) {
            $page->drawText($receive->getNote(), $this->x + 120, $this->y - 3 * $this->height, 'UTF-8');
        }
        $page->drawText($inventoryTransfer->getFrom(), $this->x + 120, $this->y - 4 * $this->height, 'UTF-8');
        $page->drawText($inventoryTransfer->getTo(), $this->x + 120, $this->y - 5 * $this->height, 'UTF-8');
        $page->drawText(
            $this->dateTime->date('Y-m-d', $receive->getCreatedOn()),
            $this->x + 428,
            $this->y - 2 * $this->height,
            'UTF-8'
        );
        $this->_setFontBold($page);
        $page->drawText(__('Date:'), $this->x + 400, $this->y - 2 * $this->height, 'UTF-8');
        $this->y -= 7 * $this->height;
        $page->drawText(__('Sr. No.'), $this->x, $this->y, 'UTF-8');
        $page->drawText(__('Part No.'), $this->x + 40, $this->y, 'UTF-8');
        $page->drawText(__('Item Name'), $this->x + 120, $this->y, 'UTF-8');
        $page->drawText(__('Warehouse'), $this->x + 290, $this->y, 'UTF-8');
        //$page->drawText(__('UOM'), $this->x + 320, $this->y, 'UTF-8');
        $page->drawText(__('Rec. Qty.'), $this->x + 360, $this->y, 'UTF-8');
        $page->drawText(__('Cost Price'), $this->x + 410, $this->y, 'UTF-8');
        $page->drawText(__('Amount'), $this->x + 480, $this->y, 'UTF-8');
        $i = 0;
        $totalAmount = 0;
        $this->_setFontRegular($page);
        foreach ($receiveProductCollection as $receiveItem) {
            $this->y -= $this->height;
            if ($this->y < $this->height + 20) {
                $page = $this->newPage();
            }
            $i++;
            $amount = (float)$receiveItem->getData('qty') * (float)$receiveItem->getData('product_price');
            $totalAmount += $amount;
            $page->drawText($i, $this->x, $this->y, 'UTF-8');
            $page->drawText($receiveItem->getData('product_sku'), $this->x + 40, $this->y, 'UTF-8');
            $page->drawText($receiveItem->getData('product_name'), $this->x + 120, $this->y, 'UTF-8');
            $page->drawText($receiveItem->getData('racket'), $this->x + 290, $this->y, 'UTF-8');
            //$page->drawText($receiveItem->getData('UOM'), $this->x + 320, $this->y, 'UTF-8');
            $page->drawText((float)$receiveItem->getData('qty'), $this->x + 360, $this->y, 'UTF-8');
            $page->drawText(
                $this->pricingHelper->currency(
                    (float)$receiveItem->getData('product_price'),
                    true,
                    false
                ),
                $this->x + 410,
                $this->y,
                'UTF-8'
            );
            $page->drawText(
                $this->pricingHelper->currency($amount, true, false),
                $this->x + 480,
                $this->y,
                'UTF-8'
            );
        }

        $this->y -= 2 * $this->height;
        if ($this->y < $this->height + 20) {
            $page = $this->newPage();
        }
        $this->_setFontBold($page);
        $page->drawText(__('Amount'), $this->x + 410, $this->y, 'UTF-8');
        $this->_setFontRegular($page);
        $page->drawText(
            $this->pricingHelper->currency($totalAmount, true, false),
            $this->x + 480,
            $this->y,
            'UTF-8'
        );

        $this->y -= $this->height;
        if ($this->y < $this->height + 20) {
            $page = $this->newPage();
        }
        $taxAmount = 0;
        $this->_setFontBold($page);
        $page->drawText(__('Tax Amount'), $this->x + 410, $this->y, 'UTF-8');
        $this->_setFontRegular($page);
        $page->drawText(
            $this->pricingHelper->currency($taxAmount, true, false),
            $this->x + 480,
            $this->y,
            'UTF-8'
        );

        $this->y -= $this->height;
        if ($this->y < $this->height + 20) {
            $page = $this->newPage();
        }
        $this->_setFontBold($page);
        $page->drawText(__('Total Amount'), $this->x + 410, $this->y, 'UTF-8');
        $this->_setFontRegular($page);
        $page->drawText(
            $this->pricingHelper->currency($totalAmount + $taxAmount, true, false),
            $this->x + 480,
            $this->y,
            'UTF-8'
        );

        $this->y -= 2 * $this->height;
        if ($this->y < $this->height + 20) {
            $page = $this->newPage();
        }
        $this->_setFontBold($page);
        $page->drawText(__('Remarks:'), $this->x, $this->y, 'UTF-8');
        $this->_setFontRegular($page);
        if ($receive->getRemarks()) {
            $page->drawText($receive->getRemarks(), $this->x + 48, $this->y, 'UTF-8');
        }

        $this->y -= 2 * $this->height;
        if ($this->y < 3 * $this->height + 20) {
            $page = $this->newPage();
        }
        $this->_setFontBold($page);
        $page->drawText(__('( Generated By )'), $this->x, $this->y, 'UTF-8');
        $page->drawText(__('( Checked By )'), $this->x + 200, $this->y, 'UTF-8');
        $page->drawText(__('( Verified By )'), $this->x + 400, $this->y, 'UTF-8');

        $fileContent = ['type' => 'string', 'value' => $pdf->render(), 'rm' => true];
        return $this->fileFactory->create(
            $name,
            $fileContent,
            DirectoryList::VAR_DIR,
            'application/pdf'
        );
    }

    /**
     * New Page
     *
     * @return \Zend_Pdf_Page
     * @throws \Zend_Pdf_Exception
     */
    public function newPage()
    {
        $company = $this->scopeConfigInterface->getValue(Information::XML_PATH_STORE_INFO_NAME);
        $phone = $this->scopeConfigInterface->getValue(Information::XML_PATH_STORE_INFO_PHONE);
        $address = $this->scopeConfigInterface->getValue(Information::XML_PATH_STORE_INFO_STREET_LINE1);
        $this->number++;
        $this->y = 800;
        $page = $this->pdf->newPage(\Zend_Pdf_Page::SIZE_A4);
        $this->_setFontBold($page);
        if ($company) {
            $page->drawText($company, $this->x, $this->y, 'UTF-8');
        }
        $page->drawText(__('Print Date:'), $this->x + 420, $this->y, 'UTF-8');
        $page->drawText(__('Print Time:'), $this->x + 420, $this->y - $this->height, 'UTF-8');
        $this->_setFontRegular($page);
        if ($phone) {
            $page->drawText(__('TEL: %1', $phone), $this->x, $this->y - $this->height, 'UTF-8');
        }
        if ($address) {
            $page->drawText($address, $this->x, $this->y - 2 * $this->height, 'UTF-8');
        }
        $page->drawText($this->dateTime->date('Y-m-d'), $this->x + 472, $this->y, 'UTF-8');
        $page->drawText(
            $this->dateTime->date('H:i:s'),
            $this->x + 475,
            $this->y - $this->height,
            'UTF-8'
        );
        $curUser = $this->authSession->getUser();
        $page->drawText(__('Printed By: %1', $curUser->getUserName()), $this->x, 20, 'UTF-8');
        $page->drawText(__('Page %1', $this->number), $this->x + 500, 20, 'UTF-8');
        $this->y -= 4 * $this->height;
        $this->pdf->pages[] = $page;
        return $page;
    }

    /**
     * Set font as regular
     *
     * @param \Zend_Pdf_Page $object
     * @param int $size
     * @return \Zend_Pdf_Resource_Font
     */
    protected function _setFontRegular($object, $size = 10)
    {
        $libDir = $this->filesystem->getDirectoryRead(DirectoryList::LIB_INTERNAL);
        $font = \Zend_Pdf_Font::fontWithPath(
            $libDir->getAbsolutePath('GnuFreeFont/FreeSerif.ttf')
        );
        $object->setFont($font, $size);
        return $font;
    }

    /**
     * Set font as bold
     *
     * @param \Zend_Pdf_Page $object
     * @param int $size
     * @return \Zend_Pdf_Resource_Font
     */
    protected function _setFontBold($object, $size = 10)
    {
        $libDir = $this->filesystem->getDirectoryRead(DirectoryList::LIB_INTERNAL);
        $font = \Zend_Pdf_Font::fontWithPath(
            $libDir->getAbsolutePath('GnuFreeFont/FreeSerifBold.ttf')
        );
        $object->setFont($font, $size);
        return $font;
    }
}
