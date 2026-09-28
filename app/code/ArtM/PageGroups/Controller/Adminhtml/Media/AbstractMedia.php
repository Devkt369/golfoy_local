<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Media;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

abstract class AbstractMedia extends Action
{
    const ADMIN_RESOURCE = 'ArtM_PageGroups::main';
    const MEDIA_ROOT = 'pagegroups';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * @var File
     */
    protected $fileDriver;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem,
        File $fileDriver,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->fileDriver = $fileDriver;
        $this->storeManager = $storeManager;
    }

    /**
     * @return Json
     */
    protected function jsonResult(): Json
    {
        return $this->resultJsonFactory->create();
    }

    /**
     * @param string $path
     * @return string
     */
    protected function normalizePath(string $path = ''): string
    {
        $path = str_replace('\\', '/', trim($path));
        $parts = [];

        foreach (explode('/', trim($path, '/')) as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                continue;
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    /**
     * @param string $relativePath
     * @return string
     */
    protected function relativeToRoot(string $relativePath = ''): string
    {
        $relativePath = $this->normalizePath($relativePath);
        return self::MEDIA_ROOT . ($relativePath ? '/' . $relativePath : '');
    }

    /**
     * @param string $relativePath
     * @return string
     */
    protected function absolutePath(string $relativePath = ''): string
    {
        return $this->mediaDirectory->getAbsolutePath($this->relativeToRoot($relativePath));
    }

    /**
     * @param string $relativePath
     * @return array
     */
    protected function buildBreadcrumbs(string $relativePath = ''): array
    {
        $relativePath = $this->normalizePath($relativePath);
        $breadcrumbs = [
            [
                'label' => 'Home',
                'path' => ''
            ]
        ];

        if ($relativePath === '') {
            return $breadcrumbs;
        }

        $segments = explode('/', $relativePath);
        $path = '';
        foreach ($segments as $segment) {
            $path = ltrim($path . '/' . $segment, '/');
            $breadcrumbs[] = [
                'label' => $segment,
                'path' => $path
            ];
        }

        return $breadcrumbs;
    }

    /**
     * @param string $relativePath
     * @return string
     */
    protected function mediaUrl(string $relativePath): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
            . $this->relativeToRoot($relativePath);
    }

    /**
     * @param string $fileName
     * @return bool
     */
    protected function isAllowedImage(string $fileName): bool
    {
        $extension = strtolower((string)pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true);
    }
}
