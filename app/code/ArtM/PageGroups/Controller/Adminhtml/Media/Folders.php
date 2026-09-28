<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Media;

class Folders extends AbstractMedia
{
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->jsonResult();
        $path = $this->normalizePath((string)$this->getRequest()->getParam('path', ''));

        try {
            $absolutePath = $this->absolutePath($path);
            if (!$this->fileDriver->isExists($absolutePath)) {
                $this->mediaDirectory->create($this->relativeToRoot($path));
            }

            $folders = [];
            foreach ($this->fileDriver->readDirectory($absolutePath) as $childPath) {
                if (!$this->fileDriver->isDirectory($childPath)) {
                    continue;
                }

                $name = basename($childPath);
                $folders[] = [
                    'name' => $name,
                    'path' => ltrim(($path ? $path . '/' : '') . $name, '/'),
                ];
            }

            usort($folders, function ($left, $right) {
                return strcmp($left['name'], $right['name']);
            });

            return $resultJson->setData([
                'success' => true,
                'current_path' => $path,
                'breadcrumbs' => $this->buildBreadcrumbs($path),
                'items' => $folders,
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to load folders: %1', $e->getMessage()),
            ]);
        }
    }
}
