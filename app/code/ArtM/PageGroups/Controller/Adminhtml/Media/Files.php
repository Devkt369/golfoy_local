<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Media;

class Files extends AbstractMedia
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

            $files = [];
            foreach ($this->fileDriver->readDirectory($absolutePath) as $childPath) {
                if ($this->fileDriver->isDirectory($childPath)) {
                    continue;
                }

                $name = basename($childPath);
                if (!$this->isAllowedImage($name)) {
                    continue;
                }

                $relativeFilePath = ltrim(($path ? $path . '/' : '') . $name, '/');
                $files[] = [
                    'name' => $name,
                    'path' => $relativeFilePath,
                    'url' => $this->mediaUrl($relativeFilePath),
                ];
            }

            usort($files, function ($left, $right) {
                return strcmp($left['name'], $right['name']);
            });

            return $resultJson->setData([
                'success' => true,
                'current_path' => $path,
                'items' => $files,
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to load files: %1', $e->getMessage()),
            ]);
        }
    }
}
