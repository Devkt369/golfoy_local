<?php
namespace ArtM\CustomProductDesign\Controller\Cliparts;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class ClipartList extends Action
{
    protected $resultJsonFactory;
    protected $filesystem;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            $clipartPath = 'customprint/cliparts/';
            $cliparts = [];
            
            if ($mediaDirectory->isExist($clipartPath)) {
                $files = $mediaDirectory->read($clipartPath);
                foreach ($files as $file) {
                    $fullPath = $mediaDirectory->getAbsolutePath($file);
                    if (is_file($fullPath) && preg_match('/\.(jpg|jpeg|png|gif|svg)$/i', $file)) {
                        // Extract just the filename from the relative path
                        $filename = basename($file);
                        $hiddenFile = $clipartPath . '.' . $filename . '.hidden';
                        
                        // Debug: Log what we're checking
                        error_log("Checking file: " . $filename . ", Hidden file path: " . $hiddenFile . ", Exists: " . ($mediaDirectory->isExist($hiddenFile) ? 'YES' : 'NO'));
                        
                        // Only include the image if it's not hidden
                        if (!$mediaDirectory->isExist($hiddenFile)) {
                            $cliparts[] = '/media/' . $file;
                        }
                    }
                }
            }
            
            // Fallback to static list if directory is empty
            if (empty($cliparts)) {
                $cliparts = [
                    '/media/customprint/cliparts/sample1.png',
                    '/media/customprint/cliparts/sample2.png',
                    '/media/customprint/cliparts/sample3.png'
                ];
            }
            
            // Add debug info to response
            $debugInfo = [];
            if ($mediaDirectory->isExist($clipartPath)) {
                $files = $mediaDirectory->read($clipartPath);
                foreach ($files as $file) {
                    if (preg_match('/\.hidden$/', $file)) {
                        $debugInfo['hidden_files'][] = basename($file);
                    }
                }
            }
            
            return $result->setData([
                'cliparts' => $cliparts, 
                'debug' => $debugInfo
            ]);
        } catch (\Exception $e) {
            return $result->setData(['cliparts' => [], 'error' => $e->getMessage()]);
        }
    }
}
