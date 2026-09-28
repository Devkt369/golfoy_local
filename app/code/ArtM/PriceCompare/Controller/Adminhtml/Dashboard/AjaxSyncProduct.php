<?php
namespace ArtM\PriceCompare\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ResourceConnection;

class AjaxSyncProduct extends Action
{
    protected $resource;

    public function __construct(
        Action\Context $context,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resource = $resource;
    }

    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $productId = $this->getRequest()->getParam('product_id');
        $websiteId = $this->getRequest()->getParam('website_id');
        $selectors = $this->getRequest()->getParam('selectors'); // comma-separated
        $url = $this->getRequest()->getParam('url');
        if (!$productId || !$websiteId || !$selectors || !$url) {
            return $resultJson->setData(['success' => false, 'error' => 'Missing params']);
        }
        // Call API with retries. This avoids intermittent failures caused by target-site latency.
        $apiUrl = 'http://127.0.0.1:5001/get-price?url=' . urlencode($url) . '&selector=' . urlencode($selectors);
        $lastApiUrl = $apiUrl;
        $attrResults = [];
        $apiError = null;

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 60,
                'ignore_errors' => true,
                'header' => "Connection: close\r\n",
            ],
        ]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $resp = @file_get_contents($apiUrl, false, $context);
            if ($resp === false || $resp === null) {
                $apiError = 'No response from scraper API';
                continue;
            }

            $json = json_decode($resp, true);
            if (!is_array($json)) {
                $apiError = 'Invalid JSON from scraper API';
                continue;
            }

            if (isset($json['result']) && is_array($json['result'])) {
                $attrResults = $json['result'];
                $apiError = null;
                break;
            }

            if (isset($json['error'])) {
                $apiError = (string)$json['error'];
            } else {
                $apiError = 'Missing result payload from scraper API';
            }
        }

        // Fallback: try a light-weight PHP extraction for missing attributes.
        $selectorGroups = $this->parseSelectorGroups($selectors);
        $fallbackResults = $this->fallbackExtractAttributes($url, $selectorGroups, $attrResults);
        if (!empty($fallbackResults)) {
            $attrResults = array_merge($attrResults, $fallbackResults);
        }

        // Save to fetched_data column (as JSON)
        $connection = $this->resource->getConnection();
        $productTable = $this->resource->getTableName('artm_pricecompare_product');
        $existing = $connection->fetchOne("SELECT fetched_data FROM $productTable WHERE product_id = ?", [$productId]);
        $fetchedData = $existing ? json_decode($existing, true) : [];

        if (!is_array($fetchedData)) {
            $fetchedData = [];
        }

        $existingWebsiteData = [];
        if (isset($fetchedData[$websiteId]) && is_array($fetchedData[$websiteId])) {
            $existingWebsiteData = $fetchedData[$websiteId];
        }

        $hasAnyNewValue = false;

        // Merge per-attribute. Only replace when new value is meaningful.
        // If new value is empty and old exists, append " - old" once.
        $mergedWebsiteData = $existingWebsiteData;
        foreach ($attrResults as $attribute => $value) {
            $isMeaningful = true;
            if (is_array($value) || $value === null) {
                $isMeaningful = false;
            } elseif (is_string($value)) {
                $trimmedValue = trim($value);
                if ($trimmedValue === '' || strtolower($trimmedValue) === 'null') {
                    $isMeaningful = false;
                }
            } elseif ($value === '') {
                $isMeaningful = false;
            }

            if ($isMeaningful) {
                $hasAnyNewValue = true;
                $mergedWebsiteData[$attribute] = $value;
                continue;
            }

            if (array_key_exists($attribute, $mergedWebsiteData)) {
                $oldValue = $mergedWebsiteData[$attribute];
                if ($oldValue !== null && $oldValue !== '' && !is_array($oldValue)) {
                    $oldValueString = trim((string)$oldValue);
                    if ($oldValueString !== '' && stripos($oldValueString, ' - old') === false) {
                        $mergedWebsiteData[$attribute] = $oldValueString . ' - old';
                    }
                }
            }
        }

        // If API returned nothing meaningful at all (e.g. {}), mark all existing values as old once.
        if (!$hasAnyNewValue && !empty($existingWebsiteData)) {
            foreach ($existingWebsiteData as $attribute => $oldValue) {
                if ($oldValue === null || $oldValue === '' || is_array($oldValue)) {
                    continue;
                }
                $oldValueString = trim((string)$oldValue);
                if ($oldValueString !== '' && stripos($oldValueString, ' - old') === false) {
                    $mergedWebsiteData[$attribute] = $oldValueString . ' - old';
                }
            }
        }

        $fetchedData[$websiteId] = $mergedWebsiteData;
        $connection->update($productTable, [
            'fetched_data' => json_encode($fetchedData),
            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s')
        ], ['product_id = ?' => $productId]);
        return $resultJson->setData([
            'success' => true,
            'value' => $mergedWebsiteData,
            'stale' => !$hasAnyNewValue,
            'message' => !$hasAnyNewValue ? ($apiError ?: 'No fresh values fetched, kept previous values as old') : null,
            'debug_api_url' => $lastApiUrl,
            'fallback_used' => !empty($fallbackResults)
        ]);
    }

    private function parseSelectorGroups($selectors)
    {
        $groups = [];
        $parts = explode('|', (string)$selectors);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $pair = explode(':', $part, 2);
            if (count($pair) !== 2) {
                continue;
            }

            $label = trim($pair[0]);
            $selector = trim($pair[1]);
            if ($label !== '') {
                $groups[$label] = $selector;
            }
        }

        return $groups;
    }

    private function fallbackExtractAttributes($url, array $selectorGroups, array $currentResults)
    {
        if (empty($selectorGroups)) {
            return [];
        }

        $html = $this->fetchHtml($url);
        if ($html === null || trim($html) === '') {
            return [];
        }

        $fallback = [];
        $prices = $this->extractPricesFromProductJsonLd($html);
        $name = $this->extractNameFromHtml($html);

        foreach ($selectorGroups as $label => $selectorString) {
            $existingValue = $currentResults[$label] ?? null;
            if ($this->isMeaningfulValue($existingValue)) {
                continue;
            }

            $labelLower = strtolower($label);
            $value = null;

            if (strpos($labelLower, 'name') !== false) {
                $value = $name;
            } elseif (strpos($labelLower, 'mrp') !== false || strpos($labelLower, 'compare') !== false || strpos($labelLower, 'old') !== false) {
                $value = $prices['max'] ?? null;
            } elseif (strpos($labelLower, 'final') !== false || strpos($labelLower, 'price') !== false) {
                $value = $prices['min'] ?? null;
            }

            if ($this->isMeaningfulValue($value)) {
                $fallback[$label] = $value;
            }
        }

        return $fallback;
    }

    private function fetchHtml($url)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => "User-Agent: Mozilla/5.0\r\nConnection: close\r\n",
            ],
        ]);

        $html = @file_get_contents($url, false, $context);
        if ($html !== false && $html !== null) {
            return $html;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            $html = curl_exec($ch);
            curl_close($ch);
            if (is_string($html) && $html !== '') {
                return $html;
            }
        }

        return null;
    }

    private function extractNameFromHtml($html)
    {
        $patterns = [
            '/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+name=["\']title["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<h1[^>]*>(.*?)<\/h1>/is',
            '/<title[^>]*>(.*?)<\/title>/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $text = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES));
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }

    private function extractPricesFromProductJsonLd($html)
    {
        $numbers = [];

        if (!preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $jsonChunk) {
            $decoded = json_decode(trim($jsonChunk), true);
            if (!is_array($decoded)) {
                continue;
            }

            $this->collectJsonLdProductPrices($decoded, $numbers);
        }

        $numbers = array_values(array_unique(array_filter($numbers, function ($n) {
            return $n > 0;
        })));

        if (empty($numbers)) {
            return [];
        }

        sort($numbers, SORT_NUMERIC);

        return [
            'min' => $this->formatInr((string)$numbers[0]),
            'max' => $this->formatInr((string)$numbers[count($numbers) - 1]),
        ];
    }

    private function collectJsonLdProductPrices($node, array &$numbers)
    {
        if (!is_array($node)) {
            return;
        }

        if (isset($node['@graph']) && is_array($node['@graph'])) {
            foreach ($node['@graph'] as $graphNode) {
                $this->collectJsonLdProductPrices($graphNode, $numbers);
            }
            return;
        }

        if (isset($node['@type'])) {
            $type = $node['@type'];
            $isProduct = false;

            if (is_string($type) && stripos($type, 'Product') !== false) {
                $isProduct = true;
            } elseif (is_array($type)) {
                foreach ($type as $t) {
                    if (is_string($t) && stripos($t, 'Product') !== false) {
                        $isProduct = true;
                        break;
                    }
                }
            }

            if ($isProduct && isset($node['offers'])) {
                $this->collectOfferPrices($node['offers'], $numbers);
            }
        }

        foreach ($node as $child) {
            if (is_array($child)) {
                $this->collectJsonLdProductPrices($child, $numbers);
            }
        }
    }

    private function collectOfferPrices($offers, array &$numbers)
    {
        if (is_array($offers) && isset($offers[0])) {
            foreach ($offers as $offer) {
                $this->collectOfferPrices($offer, $numbers);
            }
            return;
        }

        if (!is_array($offers)) {
            return;
        }

        $fields = ['price', 'lowPrice', 'highPrice'];
        foreach ($fields as $field) {
            if (!isset($offers[$field])) {
                continue;
            }

            $num = $this->extractNumericValue($offers[$field]);
            if ($num !== null) {
                $numbers[] = $num;
            }
        }

        if (isset($offers['priceSpecification']) && is_array($offers['priceSpecification'])) {
            $this->collectOfferPrices($offers['priceSpecification'], $numbers);
        }
    }

    private function extractNumericValue($value)
    {
        $value = preg_replace('/[^0-9.]/', '', (string)$value);
        if ($value === '') {
            return null;
        }

        return (float)$value;
    }

    private function formatInr($value)
    {
        $value = (string)$value;
        $number = preg_replace('/[^0-9.]/', '', $value);
        if ($number === '') {
            return null;
        }

        $parts = explode('.', $number, 2);
        $int = ltrim($parts[0], '0');
        if ($int === '') {
            $int = '0';
        }

        $intWithComma = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $int);
        if (isset($parts[1]) && $parts[1] !== '') {
            $dec = substr($parts[1], 0, 2);
            return 'Rs. ' . $intWithComma . '.' . str_pad($dec, 2, '0');
        }

        return 'Rs. ' . $intWithComma;
    }

    private function isMeaningfulValue($value)
    {
        if (is_array($value) || $value === null) {
            return false;
        }

        $stringValue = trim((string)$value);
        if ($stringValue === '' || strtolower($stringValue) === 'null') {
            return false;
        }

        return true;
    }
}
