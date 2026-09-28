<?php
namespace ArtM\CustomProductDesign\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Magento\Framework\Escaper;

class Data extends AbstractHelper
{
    protected $storeManager;
    protected $filesystem;
    protected $resource;
    protected $json;
    protected $mediaUrl;
    protected $urlBuilder;
    protected $escaper;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        ResourceConnection $resource,
        Json $json,
        UrlInterface $urlBuilder,
        Escaper $escaper
    ) {
        $this->storeManager = $storeManager;
        $this->filesystem = $filesystem;
        $this->resource = $resource;
        $this->json = $json;
        $this->urlBuilder = $urlBuilder;
        $this->escaper = $escaper;
        $this->mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        parent::__construct($context);
    }

    public function getCustomPrintImageUrl($imgPath)
    {
        if (!$imgPath) {
            return null;
        }
        
        try {
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            return $mediaUrl . $imgPath;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function checkImageExists($imgPath)
    {
        if (!$imgPath) {
            return false;
        }
        
        try {
            $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
            return $mediaDirectory->isFile($imgPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getCustomizationData($userdataId)
    {
        if (!$userdataId) {
            return null;
        }

        try {
            $ids = [];

            // Handle different input formats
            if (is_numeric($userdataId)) {
                $ids[] = $userdataId;
            } elseif (is_string($userdataId)) {
                // First, check if it's a comma-separated list
                if (strpos($userdataId, ',') !== false) {
                    $ids = explode(',', $userdataId);
                    $ids = array_map('trim', $ids);
                } else {
                    // For strings, only try JSON if it looks like JSON
                    if (substr($userdataId, 0, 1) === '{' || substr($userdataId, 0, 1) === '[') {
                        try {
                            $data = $this->json->unserialize($userdataId);
                            if (isset($data['id'])) {
                                $ids[] = $data['id'];
                            }
                        } catch (\Exception $e) {
                            $ids[] = $userdataId;
                        }
                    } else {
                        $ids[] = $userdataId;
                    }
                }
                $ids[] = $userdataId;
            } elseif (is_array($userdataId)) {
                if (isset($userdataId['id'])) {
                    // Single array item with id
                    $ids[] = $userdataId['id'];
                } else {
                    // Multiple items
                    foreach ($userdataId as $data) {
                        if (isset($data['id'])) {
                            $ids[] = $data['id'];
                        }
                    }
                }
            }

            if (empty($ids)) {
                return null;
            }

            $connection = $this->resource->getConnection();
            $tableName = $this->resource->getTableName('artm_customprint_userdata');
            
            $select = $connection->select()
                ->from($tableName)
                ->where('id IN (?)', $ids)
                ->order(new \Zend_Db_Expr('FIELD(id,' . implode(',', $ids) . ')'));
            
            // Debug SQL query
            
            $results = $connection->fetchAll($select);
            
            return !empty($results) ? $results : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getCustomizationLink($userdataId)
    {
        if (!$userdataId) {
            return 'No customization data available';
        }

        try {
            $ids = [];
            
            // Handle different input formats
            if (is_string($userdataId)) {
                // Try to parse as JSON first
                try {
                    $data = $this->json->unserialize($userdataId);
                    if (isset($data['id'])) {
                        $ids[] = $data['id'];
                    }
                } catch (\Exception $e) {
                    // If not JSON, check if it's multiple JSON objects
                    if (strpos($userdataId, '}{') !== false) {
                        $jsonStrings = preg_split('/}{'.'/', $userdataId);
                        foreach ($jsonStrings as $index => $jsonString) {
                            // Add braces back if they were removed by split
                            if ($index > 0) {
                                $jsonString = '{' . $jsonString;
                            }
                            if ($index < count($jsonStrings) - 1) {
                                $jsonString .= '}';
                            }
                            
                            try {
                                $data = $this->json->unserialize($jsonString);
                                if (isset($data['id'])) {
                                    $ids[] = $data['id'];
                                }
                            } catch (\Exception $e) {
                                continue;
                            }
                        }
                    } else {
                        // Treat as single ID
                        $ids[] = $userdataId;
                    }
                }
            } elseif (is_array($userdataId)) {
                if (isset($userdataId['id'])) {
                    // Single array item with id
                    $ids[] = $userdataId['id'];
                } else {
                    // Multiple items
                    foreach ($userdataId as $data) {
                        if (isset($data['id'])) {
                            $ids[] = $data['id'];
                        }
                    }
                }
            }

            if (empty($ids)) {
                return 'No customization data available';
            }
            // Create URL with multiple IDs
            return sprintf(
                '<a href="#" onclick="openCustomizationPopup(\'%s\'); return false;">Customisation available, view details >></a>',
                $this->urlBuilder->getUrl('customprint/userdata/popup', ['id' => implode(',', $ids)])
            );
        } catch (\Exception $e) {
            return 'No customization data available';
        }
    }

public function getCustomizationPopupHtml($customizationData)
{
    try {
        if (!$customizationData) {
            return '<div class="message-error">No customization data available</div>';
        }

        // Ensure we have an array of data
            // Ensure we have an array of data
            $dataArray = [];
            if (isset($customizationData[0])) {
                $dataArray = $customizationData;
            } elseif (is_array($customizationData) && !empty($customizationData)) {
                $dataArray = [$customizationData];
            }

            if (empty($dataArray)) {
                return '<div class="message-error">No customization data available</div>';
            }

            $html = '<div class="custom-print-data-blocks">';

        // Start with CSS - only once for all entries
        $html = '<style>
            .custom-print-data-blocks {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 0 auto;
            }
            .custom-print-data-block_heading {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                margin-bottom: 15px;
                border-bottom: 1px solid #ddd;
            }
            .customize-text {
                font-size: 16px;
                font-weight: bold;
            }
            .total-cost {
                color: #333;
            }
            .custom-print-data {
                margin-bottom: 20px;
                border-bottom: 1px solid #eee;
                padding-bottom: 20px;
            }
            .custom-print-inner-blocks {
                display: flex;
                gap: 20px;
            }
            .custom-print-data-img {
                flex: 0 0 100px;
            }
            .custom-print-data-img img {
                max-width: 100px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .right-boxes {
                flex: 1;
            }
            .grid-apply {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            .custom-card-block {
                border: 1px solid #e0e0e0;
                padding: 15px;
                background: #f9f9f9;
                border-radius: 4px;
            }
            .custom-area {
                margin-bottom: 10px;
                font-weight: bold;
            }
            .custom-area span {
                color: #666;
            }
            .custom-content p {
                margin: 5px 0;
                font-size: 12px;
                line-height: 1.4;
            }
            .custom-content p span {
                font-weight: bold;
            }
            .custom-content a {
                color: #0066cc;
                text-decoration: underline;
            }
        </style>';
        
        $html .= '<div class="custom-print-data-blocks">';
        
        $hasData = false;
        // Process each customization entry
        foreach ($dataArray as $singleData) {
            // Try both 'config' and other possible fields
            $configFields = ['config', 'additional_data'];
            $jsonData = null;
            
            foreach ($configFields as $field) {
                if (isset($singleData[$field]) && !empty($singleData[$field])) {
                    $jsonData = $singleData[$field];
                    break;
                }
            }

            if (!$jsonData) {
                continue;
            }

            try {
                $options = $this->json->unserialize($jsonData);
            } catch (\Exception $e) {
                continue;
            }

            $hasData = true;

            // Add heading with total price
            if (isset($options['total_price'])) {
                $html .= '<div class="custom-print-data-block_heading">';
                $html .= '<span class="customize-text">Customized Details</span>';
                $html .= '<span class="customize-text total-cost">Printing Cost: ₹' . $this->escaper->escapeHtml(number_format($options['total_price'], 2)) . '</span>';
                $html .= '</div>';
            }

            if (!empty($options['price_breakdown'])) {
                $html .= '<div class="custom-print-data">';
                $html .= '<div class="custom-print-inner-blocks">';
                
                // Add the main preview image
                if (!empty($singleData['img_path'])) {
                    $imageUrl = $this->getCustomPrintImageUrl($singleData['img_path']);
                    if ($imageUrl) {
                        $html .= '<div class="custom-print-data-img">';
                        $html .= '<a href="' . $this->escaper->escapeUrl($imageUrl) . '" target="_blank" rel="noopener noreferrer">';
                        $html .= '<img src="' . $this->escaper->escapeUrl($imageUrl) . '" alt="Custom Print Image" style="max-width:100px;">';
                        $html .= '</a>';
                        $html .= '</div>';
                    }
                }
                
                // Add customization details
                $html .= '<div class="right-boxes grid-apply">';
                
                foreach ($options['price_breakdown'] as $item) {
                    $areaId = $item['area_id'];
                    $assetData = isset($options['asset_paths'][$areaId]) ? $options['asset_paths'][$areaId] : null;
                    
                    $html .= '<div class="custom-card-block">';
                    
                    // Area name
                    $html .= '<div class="custom-area">';
                    $html .= '<span>Area:</span> ' . $this->escaper->escapeHtml($item['areaname']);
                    $html .= '</div>';
                    
                    // Content section
                    $html .= '<div class="custom-content ' . $item['area_filled_with'] . '">';
                    
                    // Printing type
                    $html .= '<p>Printing type: ' . $this->escaper->escapeHtml($item['area_filled_with']) . '</p>';
                    
                    // Asset details
                    if ($assetData) {
                        if ($assetData['type'] === 'text' && isset($assetData['data'])) {
                            $textData = $assetData['data'];
                            $fontDetails = sprintf('%s / %spx / %s / %s',
                                $textData['text'],
                                $textData['size'],
                                $textData['color'],
                                $textData['align']
                            );
                            $html .= '<p>' . $this->escaper->escapeHtml($fontDetails) . '</p>';
                        } elseif ($assetData['type'] === 'image') {
                            $assetUrl = $this->getCustomPrintImageUrl($assetData['path']);
                            if ($assetUrl) {
                                $html .= '<p>Asset image: <a href="' . $this->escaper->escapeUrl($assetUrl) . '" target="_blank">View image</a></p>';
                            }
                        }
                    }
                    
                    // Price
                    $html .= '<p>Price: <span>₹' . $this->escaper->escapeHtml(number_format($item['price_for_this_type'], 2)) . '</span></p>';
                    
                    $html .= '</div>'; // Close custom-content
                    $html .= '</div>'; // Close custom-card-block
                }
                
                $html .= '</div>'; // Close right-boxes
                $html .= '</div>'; // Close custom-print-inner-blocks
                $html .= '</div>'; // Close custom-print-data
            }
        }

        if (!$hasData) {
            return '<div class="message-error">No configuration data found</div>';
        }

        $html .= '</div>'; // Close custom-print-data-blocks
        return $html;
    } catch (\Exception $e) {
        return '<div class="message-error">Error loading customization data</div>';
    }
}
}