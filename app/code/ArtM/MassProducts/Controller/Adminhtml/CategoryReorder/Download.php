<?php
namespace ArtM\MassProducts\Controller\Adminhtml\CategoryReorder;

use ArtM\MassProducts\Model\CategoryReorder\Manager;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;

class Download extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private Manager $manager;
    private FileFactory $fileFactory;

    public function __construct(
        Context $context,
        Manager $manager,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->manager = $manager;
        $this->fileFactory = $fileFactory;
    }

    public function execute()
    {
        try {
            $categoryId = (int)$this->getRequest()->getParam('category_id');
            $downloadMode = (string)$this->getRequest()->getParam('download_mode', 'all');
            $skipConfigurableChildren = $downloadMode === 'skip_children';
            if ($categoryId <= 0) {
                return $this->_redirect('massproducts/index/index');
            }

            $user = $this->_auth->getUser();
            $username = $user ? (string)$user->getUserName() : 'system';

            $file = $this->manager->createDownloadSheet($categoryId, $username, $skipConfigurableChildren);

            return $this->fileFactory->create(
                (string)$file['file_name'],
                [
                    'type' => 'filename',
                    'value' => (string)$file['relative_path'],
                    'rm' => false,
                ],
                DirectoryList::VAR_DIR
            );
        } catch (\Throwable $e) {
            return $this->_redirect('massproducts/index/index', ['active_tab' => 'products_upload', 'child_tab' => 'reorder_products']);
        }
    }
}
