<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\DropshipSuccess\Controller\Supplier;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Controller UploadPricelist
 *
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class UploadPricelist extends \Magestore\DropshipSuccess\Controller\AbstractSupplier
{
    /**
     * Default customer account page
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $this->checkLogin();
        $supplierId = $this->supplierSession->getSupplierId();
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if (!$this->specialCharValidate($data['title'])) {
            $this->messageManager->addWarningMessage(__('Special symbols are not allowed in filenames!'));
        } else {
            $pricelistPath = $this->pricelistUploadService->getDropshipUploadPath($supplierId);
            $mediaDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
            $mediapath = rtrim($mediaDir, '/');

            $uploader = $this->uploaderFactory->create(['fileId' => 'upload_pricelist_file']);
            $uploader->setAllowedExtensions(['csv']);
            $uploader->setAllowRenameFiles(true);
            $path = $mediapath . '/' . $pricelistPath;
            try {
                $newFileName = hash('sha256', microtime()) . '.csv';
                $uploader->save($path, $newFileName);

                /** @var \Magestore\DropshipSuccess\Model\Supplier\PricelistUpload $pricelistUpload */
                $pricelistUpload = $this->pricelistUploadInterface->setSupplierId($supplierId)
                    ->setFileUpload($newFileName)
                    ->setTitle($data['title']);

                $this->pricelistUploadRepositoryInterface->save($pricelistUpload);

                /** Send email upload new pricelist to store owner */
                $this->emailService->sendEmailPricelistToAdmin($pricelistUpload);

                $this->messageManager->addSuccessMessage(__('You have successfully uploaded pricelist!'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        $resultRedirect->setPath('dropship/supplier/pricelist');
        return $resultRedirect;
    }

    /**
     * Function check if exist special symbol in filename
     *
     * @param string $filename
     * @return boolean
     */
    private function specialCharValidate($filename): bool
    {
        $regex = "/^[a-zA-Z0-9]*$/";
        return preg_match($regex, $filename) === 1;
    }
}
