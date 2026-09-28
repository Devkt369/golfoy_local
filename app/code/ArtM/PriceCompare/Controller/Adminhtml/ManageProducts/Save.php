<?php
namespace ArtM\PriceCompare\Controller\Adminhtml\ManageProducts;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

use Magento\MediaStorage\Model\File\UploaderFactory;

class Save extends Action
{
    protected $resultRedirectFactory;
    protected $resourceConnection;
    protected $filesystem;
    protected $uploaderFactory;

    public function __construct(
        Context $context,
        RedirectFactory $resultRedirectFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory
    ) {
        parent::__construct($context);
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->resourceConnection = $resourceConnection;
        $this->filesystem = $filesystem;
        $this->uploaderFactory = $uploaderFactory;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            $this->messageManager->addErrorMessage(__('No data found to save.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('artm_pricecompare_product');
        $productImage = null;
        try {
            if (isset($_FILES['product_image']) && $_FILES['product_image']['name']) {
                $uploader = $this->uploaderFactory->create(['fileId' => 'product_image']);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'svg']);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(false);
                $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
                $result = $uploader->save($mediaDirectory->getAbsolutePath('pricecompare/products'));
                if ($result['file']) {
                    $productImage = 'pricecompare/products/' . $result['file'];
                }
            }
            // Validate website URLs
            $websiteUrls = isset($data['website_urls']) && is_array($data['website_urls']) ? $data['website_urls'] : [];
            $websites = isset($data['websites']) && is_array($data['websites']) ? $data['websites'] : [];
            $allUrlsFilled = true;
            foreach ($websites as $websiteId) {
                if (!isset($websiteUrls[$websiteId]) || trim($websiteUrls[$websiteId]) === '') {
                    $allUrlsFilled = false;
                }
            }
            if (!$allUrlsFilled) {
                $this->messageManager->addErrorMessage(__('Please enter URLs for all selected websites.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('*/*/index');
                return $resultRedirect;
            }
            $connection->insert($table, [
                'name' => $data['product_name'],
                'sku' => $data['sku'],
                'image' => $productImage,
                'brand_id' => $data['brand'],
                'category_id' => $data['category'],
                'custom_selector' => isset($data['custom_selector']) ? $data['custom_selector'] : null,
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $productId = $connection->lastInsertId($table);
            // Save product-website links with product_url
            if (!empty($websites)) {
                $linkTable = $this->resourceConnection->getTableName('artm_pricecompare_product_website');
                foreach ($websites as $websiteId) {
                    $connection->insert($linkTable, [
                        'product_id' => $productId,
                        'website_id' => $websiteId,
                        'product_url' => $websiteUrls[$websiteId],
                        'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                    ]);
                }
            }
            $this->messageManager->addSuccessMessage(__('Product saved successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error saving product: ') . $e->getMessage());
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/index');
        return $resultRedirect;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_PriceCompare::manageproducts');
    }
}
