<?php

namespace ArtM\MsiPriority\Service;

use Magento\Framework\HTTP\Client\Curl;
use ArtM\MsiPriority\Logger\Logger;

use function GuzzleHttp\json_decode;

class ApiClient
{
    private const SKU_CREATE_PATH = '/RestWS/api/eretail/v3/sku/create';  // 'https://golfoy.vineretail.com/RestWS/api/eretail/v3/sku/create
    private const SKU_CHANNEL_MAP_PATH = '/RestWS/api/eretail/v1/sku/skuChannelMapping';
    // Added the new endpoint configuration here
    private const OLD_SKU_UPDATE_CHANNEL_MAPPING_PATH = '/RestWS/api/eretail/v1/order/updateChannelSkuDetails';

    /**
     * @var Config
     */
    private $config;


    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Curl
     */
    private $curl;

    public function __construct(
        Config $config,
        Curl $curl,
        Logger $logger,
    ) {
        $this->config = $config;
        $this->curl = $curl;
        $this->logger = $logger;
    }

    public function syncSku(array $skuPayload, $storeId = null)
    {
        $url = $this->config->getBaseUrl($storeId) . self::SKU_CREATE_PATH;
        $body = $skuPayload;
        $this->logger->debug('Preparing SKU create API call.', ['url' => $url, 'store_id' => $storeId]);

        return $this->postJsonCreateSku($url, $body, $storeId);
    }

    public function mapSkuChannel(array $mappingPayload, $storeId = null)
    {
        if (isset($mappingPayload['skuList'])) {
            $url = $this->config->getBaseUrl($storeId)
                . self::OLD_SKU_UPDATE_CHANNEL_MAPPING_PATH;

            return $this->postFormMapSku($url, $mappingPayload, $storeId);
        }

        $url = $this->config->getBaseUrl($storeId)
            . self::SKU_CHANNEL_MAP_PATH;

        $this->logger->debug('SKU channel API call.', [
            'url' => $url,
            'payload' => $mappingPayload
        ]);

        return $this->postFormMapSku(
            $url,
            [
                'RequestBody' => json_encode($mappingPayload),
                'ApiOwner' => $this->config->getApiOwner($storeId),
                'ApiKey' => $this->config->getApiKey($storeId),
            ],
            $storeId
        );
    }

    private function postJsonCreateSku($url, array $body, $storeId = null)
    {
        $jsonBody = json_encode($body);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'ApiOwner: ' . $this->config->getApiOwner($storeId),
            'ApiKey: ' . $this->config->getApiKey($storeId),
        ];

        // Generate equivalent curl command
        $curlCommand = 'curl -X POST "' . $url . '"';

        foreach ($headers as $header) {
            $curlCommand .= ' -H "' . $header . '"';
        }

        $curlCommand .= " -d '" . $jsonBody . "'";

        $this->curl->setTimeout($this->config->getTimeout($storeId));

        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'ApiOwner' => $this->config->getApiOwner($storeId),
            'ApiKey' => $this->config->getApiKey($storeId),
        ]);

        $this->curl->post($url, $jsonBody);

        return $this->buildResponse($url, $body);
    }

    private function postFormMapSku($url, array $postData, $storeId = null)
    {
        $this->curl->setTimeout($this->config->getTimeout($storeId));

        $this->curl->setHeaders([
            'Accept' => 'application/json',
            'ApiOwner' => $this->config->getApiOwner($storeId),
            'ApiKey' => $this->config->getApiKey($storeId),
            'Content-Type' => 'application/json',
        ]);

        $body = json_encode($postData);

        $this->curl->post($url, $body);
        // echo '<pre>';
        // print_r($this->buildResponse($url, $postData));
        // die('API response debug postFormMapSku');

        return $this->buildResponse($url, $postData);
    }

    private function buildResponse($url, array $payload)
    {
        // return $this->curl->getData();
        $statusCode = (int) $this->curl->getStatus();
        $responseBody = (string) $this->curl->getBody();
        $decoded = json_decode($responseBody, true);

        $this->logger->info('Vinculum API call', [
            'url' => $url,
            'status' => $statusCode,
            'payload' => $payload,
            'response' => $decoded ?: $responseBody,
        ]);

        return [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'status' => $statusCode,
            'response' => $decoded ?: $responseBody,
        ];
    }
}
