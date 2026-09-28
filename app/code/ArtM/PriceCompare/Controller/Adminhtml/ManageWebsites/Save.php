<?php
namespace ArtM\PriceCompare\Controller\Adminhtml\ManageWebsites;

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
        $table = $this->resourceConnection->getTableName('artm_pricecompare_website');
        $logo = null;
        try {
            if (isset($_FILES['website_logo']) && $_FILES['website_logo']['name']) {
                $uploader = $this->uploaderFactory->create(['fileId' => 'website_logo']);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'svg']);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(false);
                $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
                $result = $uploader->save($mediaDirectory->getAbsolutePath('pricecompare/websites'));
                if ($result['file']) {
                    $logo = 'pricecompare/websites/' . $result['file'];
                }
            }
            $connection->insert($table, [
                'name' => $data['website_name'],
                'url' => $data['website_url'],
                'logo' => $logo,
                'price_selectors' => $data['price_selectors'],
            ]);
            $this->messageManager->addSuccessMessage(__('Website saved successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error saving website: ') . $e->getMessage());
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_PriceCompare::managewebsites');
    }
}
