<?php
/**
 * Copyright © 2019 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\AdjustStock\Controller\Adminhtml\AdjustStock;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Class Export
 *
 * Export adjust stock controller
 */
class Export extends AdjustStock implements HttpGetActionInterface
{
    const SAMPLE_QTY = 1;

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->getBaseDirMedia()->create('magestore/inventory/adjuststock');
        $filename = $this->getBaseDirMedia()->getAbsolutePath('magestore/inventory/adjuststock/adjusted_products.csv');
        $headers = [
            __('No.'),
            __('SKU'),
            __('Product Name')
        ];
        $moduleManager = ObjectManager::getInstance()->get(ModuleManager::class);
        if ($moduleManager->isEnabled('Magestore_BarcodeSuccess')) {
            $headers[] = __('Barcode');
        }
        $headers = array_merge(
            $headers,
            [
                __('Old Qty'),
                __('Adjust Qty'),
                __('New Qty')
            ]
        );
        $data = array_merge([
            $headers,
        ], $this->getProductCollection());
        $this->csvProcessor->saveData($filename, $data);
        return $this->fileFactory->create(
            'adjusted_products.csv',
            $this->driverFile->fileGetContents($filename),
            DirectoryList::VAR_DIR
        );
    }

    /**
     * Get csv url
     *
     * @return string
     */
    public function getCsvLink()
    {
        $path = 'magestore/inventory/adjuststock/adjusted_products.csv';
        $url =  $this->_url->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]) . $path;
        return $url;
    }

    /**
     * Get base dir media
     *
     * @return \Magento\Framework\Filesystem\Directory\WriteInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getBaseDirMedia()
    {
        return $this->filesystem->getDirectoryWrite('media');
    }

    /**
     * Get adjusted product collection
     *
     * @param
     * @return array
     */
    public function getProductCollection()
    {
        $adjustStockId = $this->getRequest()->getParam('id');
        $data = [];

        if (isset($adjustStockId)) {
            $adjustStock = $this->adjustStockFactory->create();
            $adjustStock->setId($adjustStockId);
            $productCollection = $adjustStock->getProductCollection();
            $number = 1;
            foreach ($productCollection as $productModel) {
                $productData = [
                    $number,
                    $productModel->getData('product_sku'),
                    $productModel->getData('product_name')
                ];
                $moduleManager = ObjectManager::getInstance()->get(ModuleManager::class);
                if ($moduleManager->isEnabled('Magestore_BarcodeSuccess')) {
                    $productData[] = $productModel->getData('barcode');
                }
                $productData[] = $productModel->getData('old_qty');
                $productData[] = $productModel->getData('change_qty');
                $productData[] = $productModel->getData('new_qty');
                $data[]= $productData;
                $number ++;
            }
        }
        return $data;
    }
}
