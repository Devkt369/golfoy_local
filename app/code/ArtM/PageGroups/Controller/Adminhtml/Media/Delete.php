<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Media;

class Delete extends AbstractMedia
{
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->jsonResult();

        if (!$this->getRequest()->isPost()) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid request method.'),
            ]);
        }

        $path = $this->normalizePath((string)$this->getRequest()->getParam('path', ''));
        if ($path === '' || !$this->isAllowedImage($path)) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid image path.'),
            ]);
        }

        try {
            $absolutePath = $this->absolutePath($path);
            if (!$this->fileDriver->isExists($absolutePath) || $this->fileDriver->isDirectory($absolutePath)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Image not found.'),
                ]);
            }

            $this->fileDriver->deleteFile($absolutePath);

            return $resultJson->setData([
                'success' => true,
                'message' => __('Image deleted successfully.'),
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to delete image: %1', $e->getMessage()),
            ]);
        }
    }
}
