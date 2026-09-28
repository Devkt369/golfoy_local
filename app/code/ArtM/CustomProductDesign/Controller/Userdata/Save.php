<?php
namespace ArtM\CustomProductDesign\Controller\Userdata;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use ArtM\CustomProductDesign\Model\UserdataFactory;
use ArtM\Common\Model\ImageCompressor;

class Save extends Action
{
    protected $userdataFactory;
    protected $imageCompressor;

    public function __construct(
        Context $context, 
        UserdataFactory $userdataFactory,
        ImageCompressor $imageCompressor)
    {
        parent::__construct($context);
        $this->userdataFactory = $userdataFactory;
        $this->imageCompressor = $imageCompressor;
    }

    public function execute()
    {
        $result = ['success' => false];
        $config = $this->getRequest()->getParam('config');
        $assetData = $this->getRequest()->getParam('asset_data');
        $imagePath = '';
        $imageData = $this->getRequest()->getParam('image_data');
        $sessionId = $this->getRequest()->getParam('session_id');
        $timestamp = $this->getRequest()->getParam('timestamp');
        
        // Debug: log incoming POST data
        $debugLog = BP . '/var/log/custom_print_debug.log';
        $debugMsg = "=== NEW SAVE REQUEST ===\n";
        $debugMsg .= "image_data: " . substr($imageData, 0, 100) . "\n";
        $debugMsg .= "session_id: $sessionId\n";
        $debugMsg .= "timestamp: $timestamp\n";
        $debugMsg .= "asset_data: " . $assetData . "\n";
        $debugMsg .= "========================\n";
        file_put_contents($debugLog, $debugMsg, FILE_APPEND);
        
        $assetPaths = [];
        
        if ($imageData && $sessionId && $timestamp) {
            $mediaDir = BP . '/pub/media/custom_printing/' . $sessionId . '/' . $timestamp;
            $assetsDir = $mediaDir . '/assets';
            
            if (!is_dir($mediaDir)) {
                mkdir($mediaDir, 0777, true);
            }
            if (!is_dir($assetsDir)) {
                mkdir($assetsDir, 0777, true);
            }
            
            // Save preview image
            $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $filePath = $mediaDir . '/custom_print.png';
            $writeResult = file_put_contents($filePath, base64_decode($imageData));
            $imagePath = 'custom_printing/' . $sessionId . '/' . $timestamp . '/custom_print.png';
            
            // Process and save asset data (uploaded images and text data)
            if ($assetData) {
                $assets = json_decode($assetData, true);
                foreach ($assets as $areaId => $assetInfo) {
                    if ($assetInfo['type'] === 'image' && isset($assetInfo['value'])) {
                        if (strpos($assetInfo['value'], 'data:image') === 0) {
                            // It's a base64 uploaded image
                            $imageExt = 'png';
                            if (strpos($assetInfo['value'], 'data:image/jpeg') === 0) $imageExt = 'jpg';
                            elseif (strpos($assetInfo['value'], 'data:image/jpg') === 0) $imageExt = 'jpg';
                            elseif (strpos($assetInfo['value'], 'data:image/gif') === 0) $imageExt = 'gif';
                            
                            $assetImageData = preg_replace('#^data:image/\w+;base64,#i', '', $assetInfo['value']);
                            $assetImageData = str_replace(' ', '+', $assetImageData);
                            
                            $assetFileName = 'area_' . $areaId . '_image.' . $imageExt;
                            $assetFilePath = $assetsDir . '/' . $assetFileName;
                            
                            if (file_put_contents($assetFilePath, base64_decode($assetImageData))) {
                                $assetPaths[$areaId] = [
                                    'type' => 'image',
                                    'path' => 'custom_printing/' . $sessionId . '/' . $timestamp . '/assets/' . $assetFileName
                                ];
                            }
                            $this->imageCompressor->maybeCompressImage(BP . '/pub/media/custom_printing/' . $sessionId . '/' . $timestamp . '/assets/' . $assetFileName);
                        } elseif (strpos($assetInfo['value'], '/media/customprint/cliparts/') === 0 || 
                                 strpos($assetInfo['value'], '/customprint/cliparts/') === 0) {
                            // It's a clipart URL - copy the clipart file to assets directory
                            $clipartUrl = $assetInfo['value'];
                            
                            // Remove leading slash and /media prefix if present
                            $clipartPath = $clipartUrl;
                            if (strpos($clipartPath, '/media/') === 0) {
                                $clipartPath = substr($clipartPath, 7); // Remove '/media/'
                            } elseif (strpos($clipartPath, '/') === 0) {
                                $clipartPath = substr($clipartPath, 1); // Remove leading '/'
                            }
                            
                            $sourceFile = BP . '/pub/media/' . $clipartPath;
                            
                            // Get file extension from clipart filename
                            $pathInfo = pathinfo($sourceFile);
                            $imageExt = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : 'png';
                            
                            $assetFileName = 'area_' . $areaId . '_clipart.' . $imageExt;
                            $assetFilePath = $assetsDir . '/' . $assetFileName;
                            
                            // Copy the clipart file to assets directory
                            if (file_exists($sourceFile) && copy($sourceFile, $assetFilePath)) {
                                $assetPaths[$areaId] = [
                                    'type' => 'image',
                                    'path' => 'custom_printing/' . $sessionId . '/' . $timestamp . '/assets/' . $assetFileName,
                                    'source_clipart' => $clipartUrl
                                ];
                                
                                // Debug log for clipart copying
                                $debugMsg = "Clipart copied successfully:\n";
                                $debugMsg .= "Source: $sourceFile\n";
                                $debugMsg .= "Destination: $assetFilePath\n";
                                $debugMsg .= "Asset path: custom_printing/$sessionId/$timestamp/assets/$assetFileName\n";
                                file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                            } else {
                                // Debug log for clipart copy failure
                                $debugMsg = "Clipart copy failed:\n";
                                $debugMsg .= "Source exists: " . (file_exists($sourceFile) ? 'Yes' : 'No') . "\n";
                                $debugMsg .= "Source: $sourceFile\n";
                                $debugMsg .= "Destination: $assetFilePath\n";
                                file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                            }
                        }
                    } elseif ($assetInfo['type'] === 'text' && isset($assetInfo['value'])) {
                        // Save text data to JSON file for high-quality rendering
                        $textData = [
                            'text' => $assetInfo['value'],
                            'font' => $assetInfo['font'] ?? 'Arial',
                            'size' => $assetInfo['size'] ?? 18,
                            'color' => $assetInfo['color'] ?? '#000000',
                            'align' => $assetInfo['align'] ?? 'left'
                        ];
                        
                        $textFileName = 'area_' . $areaId . '_text.json';
                        $textFilePath = $assetsDir . '/' . $textFileName;
                        
                        if (file_put_contents($textFilePath, json_encode($textData, JSON_PRETTY_PRINT))) {
                            $assetPaths[$areaId] = [
                                'type' => 'text',
                                'path' => 'custom_printing/' . $sessionId . '/' . $timestamp . '/assets/' . $textFileName,
                                'data' => $textData
                            ];
                        }
                    }
                }
            }
            
            // Debug: log file write result
            file_put_contents($debugLog, "filePath: $filePath\nwriteResult: $writeResult\nassetPaths: " . json_encode($assetPaths) . "\n", FILE_APPEND);
        }
        if ($config) {
            $data = json_decode($config, true);
            $userdata = $this->userdataFactory->create();
            // Build additional_data JSON including price breakdown and asset paths
            $additionalData = [
                'price_breakdown' => isset($data['price_breakdown']) ? $data['price_breakdown'] : [],
                'total_price' => isset($data['total_price']) ? $data['total_price'] : 0,
                'asset_paths' => $assetPaths // Include asset paths for high-quality printing
            ];
            $userdata->setData([
                'product_id' => $data['product_id'],
                'customer_id' => $data['customer_id'],
                'session_id' => $data['session_id'],
                'image' => isset($data['images'][0]) ? $data['images'][0] : '',
                'printable_data' => json_encode($data),
                'img_path' => $imagePath,
                'additional_data' => json_encode($additionalData)
            ]);
            $userdata->save();
            $result['success'] = true;
            $result['id'] = $userdata->getId();
            $result['asset_paths'] = $assetPaths; // Return asset paths in response
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
