<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Media;

class CreateFolder extends AbstractMedia
{
    /**
     * Create a sub-folder inside pub/media/pagegroups/.
     *
     * POST params:
     *   path  string  parent path relative to MEDIA_ROOT (may be empty for root)
     *   name  string  new folder name (alphanumeric, dashes, underscores only)
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->jsonResult();

        if (!$this->getRequest()->isPost()) {
            return $resultJson->setData(['success' => false, 'message' => __('Invalid request method.')]);
        }

        $parentPath = trim((string)$this->getRequest()->getParam('path', ''));
        $name = trim((string)$this->getRequest()->getParam('name', ''));

        if ($name === '') {
            return $resultJson->setData(['success' => false, 'message' => __('Folder name is required.')]);
        }

        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $name)) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Folder name may only contain letters, numbers, dashes, and underscores.'),
            ]);
        }

        try {
            $parentRelative = $this->normalizePath($parentPath);
            $newRelative    = $parentRelative ? $parentRelative . '/' . $name : $name;
            $fullRelative   = $this->relativeToRoot($newRelative);

            if ($this->mediaDirectory->isDirectory($fullRelative)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Folder "%1" already exists.', $name),
                ]);
            }

            $this->mediaDirectory->create($fullRelative);

            return $resultJson->setData([
                'success' => true,
                'message' => __('Folder "%1" created.', $name),
                'path'    => $newRelative,
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData(['success' => false, 'message' => __('Error: %1', $e->getMessage())]);
        }
    }
}
