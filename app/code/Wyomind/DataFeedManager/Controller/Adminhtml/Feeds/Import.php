<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Controller\Adminhtml\Feeds;

/**
 * Class Import
 * @package Wyomind\DataFeedManager\Controller\Adminhtml\Feeds
 */
class Import extends \Wyomind\DataFeedManager\Controller\Adminhtml\Feeds\AbstractFeeds
{
    public $_uploader;
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $this->_uploader = new \Magento\Framework\File\Uploader('datafeed');
        if ($this->_uploader->getFileExtension() != 'dfm') {
            $this->messageManager->addError(__('Wrong file type (') . $this->_uploader->getFileExtension() . __(').<br>Choose a dfm file.'));
        } else {
            $rootDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
            $this->_uploader->save($rootDir . '/var/tmp', 'import-datafeedmanager.csv');
            // Get content
            $file = new \Magento\Framework\Filesystem\Driver\File;
            $dfm = new \Magento\Framework\File\Csv($file);
            $data = $dfm->getData($rootDir . '/var/tmp/' . $this->_uploader->getUploadedFileName());

            if ($this->framework->getStoreConfig('datafeedmanager/system/trans_domain_export')) {
                $key = "dfm-empty-key";
            } else {
                $key = $this->framework->getStoreConfig('datafeedmanager/license/activation_code');
            }

            $template = openssl_decrypt($data[0][0], 'AES-128-ECB', $key);

            if ($this->dfmModel->load(0)->getResource()->importDataFeed($template)) {
                $this->messageManager->addSuccess(__('The data feed has been imported.'));
            } else {
                $this->messageManager->addError(__('An error occurred when importing the data feed.'));
            }
            $file->deleteFile($rootDir . '/var/tmp/' . $this->_uploader->getUploadedFileName());
        }

        $result = $this->_resultRedirectFactory->create()->setPath('datafeedmanager/feeds/index');
        return $result;
    }
}
