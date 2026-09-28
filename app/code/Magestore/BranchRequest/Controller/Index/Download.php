<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\BranchRequest\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magestore\BranchRequest\Api\TransferManagementInterface;
use Magento\Framework\App\Response\Http\FileFactory;

/**
 * Class BranchRequest Download
 *
 * @SuppressWarnings(PHPMD)
 */
class Download extends \Magento\Framework\App\Action\Action
{
    /**
     * @var FileFactory
     */
    public $fileFactory;

    /**
     * @var TransferManagementInterface
     */
    public $transferManagement;

    /**
     * @var Filesystem
     */
    public $filesystem;

    /**
     * Download construct
     *
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param TransferManagementInterface $transferManagement
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        TransferManagementInterface $transferManagement,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->transferManagement = $transferManagement;
        $this->filesystem = $filesystem;
    }

    /**
     * Download execute
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $transferId = $this->getRequest()->getParam('id');
        $productFromInventoryTransfer = $this->transferManagement->getProductFromInventoryTransfer($transferId);
        $name = sha1(microtime());
        $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->create('branchrequest');
        $filename = DirectoryList::VAR_DIR . '/download_summary_' . $name . '.csv';

        $stream = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)
            ->openFile($filename, 'w+');
        $stream->lock();
        $data = [
            [__('Product Sku'), __('Product Name'), __('Qty Requested')]
        ];
        $dataRequest = [];
        foreach ($productFromInventoryTransfer as $productRequest) {
            $dataRequest[] = [
                'product_sku' => $productRequest->getProductSku(),
                'product_name' => $productRequest->getProductName(),
                'qty_requested' => $productRequest->getQtyRequested()
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
