<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Media;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;

class Deleteajaxupload extends Action
{
    protected $filesystem;
    protected $resultJsonFactory;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {

            $path = $this->getRequest()->getParam('path');

            if (!$path) {
                throw new \Exception('Invalid path');
            }
            // 🔐 SECURITY: allow only specific folder
            if (strpos($path, 'temp/product_media/') === false) {
                throw new \Exception('Invalid file path');
            }

            $mediaDirectory = $this->filesystem
                ->getDirectoryWrite(DirectoryList::MEDIA);

            if ($mediaDirectory->isExist($path)) {
                $mediaDirectory->delete($path);
            }

            return $resultJson->setData([
                'success' => true
            ]);

        } catch (\Exception $e) {

            return $resultJson->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // 🔐 ACL (IMPORTANT)
    protected function _isAllowed()
    {
        return true; // or define proper ACL
    }
}