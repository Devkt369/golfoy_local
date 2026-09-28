<?php
namespace ArtM\CategoryPage\Controller\Ajax;

use ArtM\CategoryPage\Model\ImageUploader;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class UploadImage extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CategoryPage::catalog_showcase';

    private $imageUploader;

    public function __construct(Context $context, ImageUploader $imageUploader)
    {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $field = (string)$this->getRequest()->getParam('field', 'image');
            $folder = (string)$this->getRequest()->getParam('folder', 'categories');
            $file = $this->getRequest()->getFiles($field);
            if (!$file || empty($file['tmp_name'])) {
                throw new \RuntimeException('No file uploaded.');
            }

            $saved = $this->imageUploader->saveUploadedFile($field, $folder);
            return $result->setData(['success' => true, 'file' => $saved]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
