<?php

namespace ArtM\MsiPriority\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use ArtM\MsiPriority\Model\VinRequestFactory;
use Magento\Framework\Encryption\EncryptorInterface;

class Data extends AbstractHelper
{
    const XML_PATH_GENERAL_ENABLED = 'msi_priority/general/sync_enabled';
    const XML_PATH_GENERAL_POS_START_DATE = 'msi_priority/general/pos_start_date';
    const XML_PATH_GENERAL_SELECT_BRANDS = 'msi_priority/general/select_brands';
    const XML_PATH_GENERAL_API_ENDPOINT = 'msi_priority/general/api_endpoint';
    const XML_PATH_GENERAL_API_KEY = 'msi_priority/general/api_key';
    const XML_PATH_GENERAL_API_OWNER = 'msi_priority/general/api_owner';

    protected $logger;
    protected $vinRequestFactory;
    protected $encryptor;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        VinRequestFactory $vinRequestFactory,
        EncryptorInterface $encryptor
    ) {
        $this->logger = $logger;
        $this->vinRequestFactory = $vinRequestFactory;
        $this->encryptor = $encryptor;
        parent::__construct($context);
    }

    /**
     * Check if POS sync is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_GENERAL_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get POS orders start date
     *
     * @return string|null
     */
    public function getStartDate()
    {
        $configDate = $this->scopeConfig->getValue(
            self::XML_PATH_GENERAL_POS_START_DATE,
            ScopeInterface::SCOPE_STORE
        );
        
        if ($configDate) {
            // If date is set in config, format it properly
            // Handle various input formats (Y-m-d, Y-m-d H:i:s, etc.)
            $timestamp = strtotime($configDate);
            if ($timestamp !== false) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        }
        
        // If not set in config or invalid date, return date from 2 months ago as default
        return date('Y-m-d H:i:s', strtotime('-2 months'));
    }

    /**
     * Get selected brands for POS orders
     *
     * @return array
     */
    public function getSelectedBrands()
    {
        $brands = $this->scopeConfig->getValue(
            self::XML_PATH_GENERAL_SELECT_BRANDS,
            ScopeInterface::SCOPE_STORE
        );
        
        // Convert comma-separated values to array
        if ($brands) {
            return explode(',', $brands);
        }
        
        return [];
    }

    /**
     * Get API endpoint
     *
     * @return string
     */
    public function getApiEndpoint()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_GENERAL_API_ENDPOINT,
            ScopeInterface::SCOPE_STORE
        ) ?: 'https://golfoy.vineretail.com/RestWS/api/eretail/v2/stock/updateInventory';
    }

    /**
     * Get API key
     *
     * @return string
     */
    public function getApiKey()
    {
        $apiKey = $this->scopeConfig->getValue(
            self::XML_PATH_GENERAL_API_KEY,
            ScopeInterface::SCOPE_STORE
        );
        
        return $apiKey ?: '0ca18d925f024e8dbc0b61daacf029d1da28bb07ac03411093ab6e5';
    }

    /**
     * Get API owner
     *
     * @return string
     */
    public function getApiOwner()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_GENERAL_API_OWNER,
            ScopeInterface::SCOPE_STORE
        ) ?: 'Rohit';
    }

    /**
     * Check if auto sync is enabled
     *
     * @return bool
     */
    public function isAutoSyncEnabled()
    {
        // Return false since auto sync is not in simplified config
        return false;
    }

    /**
     * Get sync interval in minutes
     *
     * @return int
     */
    public function getSyncInterval()
    {
        // Return default 60 minutes
        return 60;
    }

    /**
     * Get batch size for sync operations
     *
     * @return int
     */
    public function getBatchSize()
    {
        // Return default 50
        return 50;
    }

    /**
     * Call VIN Retail inventory update API - now using configuration values
     *
     * @param array $inventoryList
     * @param string $posOrderId
     * @return array
     */
    public function callUpdateInventoryApi(array $inventoryList, $posOrderId = 'UNKNOWN')
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'POS Sync is disabled in configuration'];
        }

        $url = $this->getApiEndpoint();

        $headers = [
            "Content-Type: application/x-www-form-urlencoded"
        ];

        $postFields = [
            'ApiOwner'    => $this->getApiOwner(),
            'ApiKey'      => $this->getApiKey(),
            'RequestBody' => json_encode(['inventoryList' => $inventoryList])
        ];

        try {
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Add timeout

            $response = curl_exec($ch);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                $this->logger->error('Inventory API CURL Error: ' . $curlErr);
                return ['success' => false, 'error' => $curlErr];
            }

            $result = json_decode($response, true);

            if (!$result) {
                $this->logger->error('Inventory API Invalid JSON Response: ' . $response);
                return ['success' => false, 'error' => 'Invalid JSON'];
            }

            // Save request if successful
            if (isset($result['responseCode']) && $result['responseCode'] == 0 &&
                isset($result['responseMessage']) && strtolower($result['responseMessage']) === 'success') {

                try {
                    $vinRequest = $this->vinRequestFactory->create();
                    $vinRequest->setData([
                        'pos_order_id'     => $posOrderId,
                        'request_payload'  => json_encode($inventoryList),
                        'response_payload' => json_encode($result),
                        'status'           => 'Success'
                    ]);
                    $vinRequest->save();
                } catch (\Exception $e) {
                    $this->logger->error('VIN Save DB Error: ' . $e->getMessage());
                }
            }

            return ['success' => true, 'response' => $result];
        } catch (\Exception $e) {
            $this->logger->error('Inventory API Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if order contains J.L products (for filtering unsynced orders)
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function hasJLProducts($order)
    {
        foreach ($order->getAllItems() as $item) {
            $product = $item->getProduct();
            if ($product) {
                $productName = $product->getName();
                // Check for J Lindeberg products (also check brand attribute if available)
                if (stripos($productName, 'J Lindeberg') !== false || 
                    stripos($productName, 'J.L') !== false ||
                    stripos($productName, 'JL') !== false) {
                    return true;
                }
                
                // Also check brand attribute
                $brand = $product->getAttributeText('brand');
                if ($brand && (stripos($brand, 'J Lindeberg') !== false || 
                              stripos($brand, 'J.L') !== false ||
                              stripos($brand, 'JL') !== false)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if order is from selected brands
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isFromSelectedBrands($order)
    {
        $selectedBrands = $this->getSelectedBrands();
        if (empty($selectedBrands)) {
            return true; // If no brands selected, include all orders
        }

        foreach ($order->getAllItems() as $item) {
            $product = $item->getProduct();
            if ($product) {
                $productName = $product->getName();
                
                // Check brand attribute
                $brandValue = $product->getAttributeText('brand');
                
                // Check manufacturer attribute as fallback
                $manufacturerValue = $product->getAttributeText('manufacturer');
                
                foreach ($selectedBrands as $selectedBrand) {
                    // Check if brand matches (partial match for flexibility)
                    if (($brandValue && stripos($brandValue, $selectedBrand) !== false) ||
                        ($manufacturerValue && stripos($manufacturerValue, $selectedBrand) !== false) ||
                        (stripos($productName, $selectedBrand) !== false)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Check if order is after the configured start date
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isOrderAfterStartDate($order)
    {
        $startDate = $this->getStartDate();
        if (!$startDate) {
            return true; // If no start date configured, include all orders
        }

        $orderDate = $order->getCreatedAt();
        $configuredDate = date('Y-m-d H:i:s', strtotime($startDate));
        
        return strtotime($orderDate) >= strtotime($configuredDate);
    }

    /**
     * Check if order should be included in unsynced POS orders
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function shouldIncludeInUnsyncedOrders($order)
    {
        // Check if order is after the configured start date
        if (!$this->isOrderAfterStartDate($order)) {
            return false;
        }

        // Check if order is from selected brands (mandatory - must have selected brands)
        if (!$this->isFromSelectedBrands($order)) {
            return false;
        }

        return true;
    }

    /**
     * Get archive retention days from configuration
     *
     * @return int
     */
    public function getArchiveRetentionDays()
    {
        // Return default 90 days
        return 90;
    }

    /**
     * Check if automatic sync is enabled
     *
     * @return bool
     */
    public function isAutomaticSyncEnabled()
    {
        // Return false since automatic sync is not in simplified config
        return false;
    }

    /**
     * Debug method to check configuration values
     *
     * @return array
     */
    public function getDebugInfo()
    {
        return [
            'isEnabled' => $this->isEnabled(),
            'startDate' => $this->getStartDate(),
            'selectedBrands' => $this->getSelectedBrands(),
            'apiEndpoint' => $this->getApiEndpoint()
        ];
    }

    /**
     * Check if order is archived
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isOrderArchived($order)
    {
        $connection = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\ResourceConnection::class)
            ->getConnection();
        
        $tableName = $connection->getTableName('vin_inventory_archived_order');
        
        // Check if table exists first
        if (!$connection->isTableExists($tableName)) {
            return false;
        }
        
        $select = $connection->select()
            ->from($tableName, ['order_id'])
            ->where('order_id = ?', $order->getId());
        
        $result = $connection->fetchOne($select);
        
        return !empty($result);
    }
}
