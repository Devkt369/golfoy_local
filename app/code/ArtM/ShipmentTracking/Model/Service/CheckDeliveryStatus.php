<?php

namespace ArtM\ShipmentTracking\Model\Service;

use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory as TrackCollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory;

class CheckDeliveryStatus
{
    private const INDIA_POST_TOKEN_CACHE_KEY = 'indiapost_tokens';
    const BLUEDART_CACHE_KEY = 'artm_bluedart_jwt_token';
    const BLUEDART_CACHE_LIFETIME = 82800;
    /**
     * @var TrackCollectionFactory
     */
    protected $trackCollectionFactory;
    protected $shipmentCollectionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CurlFactory
     */
    protected $curlFactory;

    /**
     * @var CacheInterface
     */
    protected $cacheManager;

    /**
     * @param TrackCollectionFactory $trackCollectionFactory
     * @param LoggerInterface $logger
     * @param CurlFactory $curlFactory
     */
    public function __construct(
        TrackCollectionFactory $trackCollectionFactory,
        LoggerInterface $logger,
        CollectionFactory $shipmentCollectionFactory,
        CacheInterface $cacheManager,
        CurlFactory $curlFactory
    ) {
        $this->trackCollectionFactory = $trackCollectionFactory;
        $this->logger = $logger;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->cacheManager = $cacheManager;
        $this->curlFactory = $curlFactory;
    }

    /**
     * Process tracking information via API
     *
     * @param string $title
     * @param string $trackNumber
     * @param int $orderId
     * @return string API response
     */
    public function processTracking($title, $trackNumber, $orderId)
    {
        $curl = $this->curlFactory->create();
        $response = '';
        // echo $this->isCourierMatch($title, ['delhivery']);
        // die("Testing: Title - {$title}, Track Number - {$trackNumber}, Order ID - {$orderId}");
        try {
            if ($this->isCourierMatch($title, ['delhivery'])) {
                return $this->delhiveryTrackingInfo($title, $trackNumber, $orderId);
            } elseif ($this->isCourierMatch($title, ['shree maruti', 'shreemaruti'])) {
                return $this->shreeMarutiTrackingInfo($title, $trackNumber, $orderId);
            } elseif ($this->isCourierMatch($title, ['blue dart', 'bluedart'])) {
                return $this->bluedartTrackingInfo($title, $trackNumber, $orderId);
            } elseif ($this->isCourierMatch($title, ['india post', 'indiapost', 'speed post', 'speedpost'])) {
                return $this->indiaPostTrackingInfo($title, $trackNumber, $orderId);
            } else {
                if ($this->isCourierMatch($title, ['shippingpartner', 'shipping partner'])) {
                    switch ($this->getTrackingTitleByNumber($trackNumber)) {
                        case 'Delhivery':
                            return $this->delhiveryTrackingInfo($title, $trackNumber, $orderId);

                        case 'Shree Maruti':
                            return $this->shreeMarutiTrackingInfo($title, $trackNumber, $orderId);

                        case 'BlueDart':
                            // var_dump($this->bluedartTrackingInfo($title, $trackNumber, $orderId));die('find bluedartTrackingInfo');
                            return $this->bluedartTrackingInfo($title, $trackNumber, $orderId);

                        case 'Speed Post':
                            return $this->indiaPostTrackingInfo($title, $trackNumber, $orderId);

                        default:
                            $this->logger->info("Order ID: {$orderId}, Carrier: {$title} (Default), Track Number: {$trackNumber}, Default Payload Triggered");
                            return $this->detectAndTrackShipment($title, $trackNumber, $orderId);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Error checking track status for order {$orderId}, track {$trackNumber}: " . $e->getMessage());
        }
        return $response;
    }

    public function detectAndTrackShipment($title, $trackNumber, $orderId)
    {
        $trackers = [
            'Delhivery' => function () use ($title, $trackNumber, $orderId) {
                return $this->delhiveryTrackingInfo($title, $trackNumber, $orderId);
            },
            'Shree Maruti' => function () use ($title, $trackNumber, $orderId) {
                return $this->shreeMarutiTrackingInfo($title, $trackNumber, $orderId);
            },
            'BlueDart' => function () use ($title, $trackNumber, $orderId) {
                return $this->bluedartTrackingInfo($title, $trackNumber, $orderId);
            },
            'Speed Post' => function () use ($title, $trackNumber, $orderId) {
                return $this->indiaPostTrackingInfo($title, $trackNumber, $orderId);
            }
        ];

        foreach ($trackers as $carrier => $callback) {
            try {
                $response = $callback();

                if ($this->isValidTrackingResponse($response)) {
                    $this->logger->info(
                        "Order ID: {$orderId}, Track Number: {$trackNumber}, Detected Carrier: {$carrier}"
                    );
// die('valid response');
                    return [
                        'carrier' => $carrier,
                        'response' => $response
                    ];
                }
            } catch (\Exception $e) {
                $this->logger->error(
                    "Order ID: {$orderId}, Carrier: {$carrier}, Error: " . $e->getMessage()
                );
            }
        }

        $this->logger->warning(
            "Order ID: {$orderId}, Track Number: {$trackNumber}, No carrier matched."
        );

        return 'matching not found';
    }

    private function isValidTrackingResponse($response)
    {
        if (empty($response)) {
            return false;
        }

        if (is_string($response)) {
            if (
                stripos($response, 'matching not found') !== false
                || stripos($response, 'Data does not exists') !== false
            ) {
                return false;
            }

            $decoded = json_decode($response, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $response = $decoded;
            }
        }

        if (is_array($response)) {
            return (
                !empty($response['success'])
                && (
                    !empty($response['tracking_info'])
                    || !empty($response['checkpoints'])
                    || !empty($response['data'])
                )
            );
        }

        return true;
    }

    /**
     * Undocumented function
     *
     * @param string $trackNumber
     * @return string
     */
    public function getTrackingTitleByNumber($trackNumber)
    {
        $trackNumber = trim($trackNumber);

        // If starts with alphabet -> Speed Post
        if (preg_match('/^[A-Za-z]/', $trackNumber)) {
            return 'Speed Post';
        }

        // If starts with 13 or 14 and length is 14 -> Delhivery
        if (
            preg_match('/^(13|14)/', $trackNumber) &&
            strlen($trackNumber) == 14
        ) {
            return 'Delhivery';
        }

        // If starts with 7,8,9 and length is 11 -> BlueDart
        if (
            preg_match('/^[56789]/', $trackNumber) &&
            strlen($trackNumber) == 11
        ) {
            return 'BlueDart';
        }

        // If starts with 25 and length is 14 -> Shree Maruti
        if (
            preg_match('/^(25|26)/', $trackNumber) &&
            strlen($trackNumber) == 14
        ) {
            return 'Shree Maruti';
        }

        return 'Unknown Courier';
    }

    // /**
    //  * Undocumented function
    //  *
    //  * @param string $title
    //  * @param string $trackNumber
    //  * @param int $orderId
    //  * @return mixed
    //  */
    // public function shreeMarutiTrackingInfo($title, $trackNumber, $orderId)
    // {
    //     $url = 'https://apis-hubops.innofulfill.com/tracking/v2/' . $trackNumber;
    //     $curl = $this->curlFactory->create();
    //     $curl->setOption(CURLOPT_TIMEOUT, 30);
    //     $curl->get($url);
    //     $response = $curl->getBody();

    //     // $this->logger->info("Order ID: {$orderId}, Carrier: Shree Maruti, Track Number: {$trackNumber}, API Response: " . $response);
    //     return $response;
    // }

    /**
     * Get Shree Maruti Tracking Information
     *
     * @param string $title
     * @param string $trackNumber
     * @param int $orderId
     * @return string
     */
    public function shreeMarutiTrackingInfo($title, $trackNumber, $orderId)
    {
        $url = 'https://apis.innofulfill.com/gateway/tracking-v2/api/tracking/awb/' . $trackNumber;

        $curl = $this->curlFactory->create();
        $curl->setOption(CURLOPT_TIMEOUT, 30);
        $curl->addHeader('Api-Key', 'innofulfill_live_t3BzXSIDO8R1rgSNrhTn1TXMd8boBak9_c91af5d7');
        $curl->addHeader('Accept', 'application/json');
        $curl->get($url);
        $response = $curl->getBody();
                            
        return $response;
    }

    /**
     * Get India Post Tracking Information
     *
     * @param string $title
     * @param string $trackNumber
     * @param int $orderId
     * @return array|string
     */
    public function indiaPostTrackingInfo($title, $trackNumber, $orderId)
    {
        $username = '1000008488';
        $password = 'Dop@1234';

        /** @var Curl $curl */
        $curl = $this->curlFactory->create();

        $accessToken = $this->getValidAccessToken(
            $curl,
            $username,
            $password
        );

        if (!$accessToken) {
            return [
                'error' => 'Failed to authenticate with India Post API'
            ];
        }

        $url = "https://app.indiapost.gov.in/beextcustomer/v1/tracking/{$trackNumber}";

        $curl->addHeader('accept', 'application/json');
        $curl->addHeader('Authorization', 'Bearer ' . $accessToken);

        $curl->get($url);

        $response = $curl->getBody();



        $statusCode = $curl->getStatus();

        if ($statusCode == 401) {

            $this->clearTokenCache();

            $curl = $this->curlFactory->create();

            $accessToken = $this->getValidAccessToken(
                $curl,
                $username,
                $password,
                true
            );

            if ($accessToken) {

                $curl->addHeader('accept', 'application/json');
                $curl->addHeader(
                    'Authorization',
                    'Bearer ' . $accessToken
                );

                $curl->get($url);
                $response = $curl->getBody();
            }
        }

        return $response;
    }

    /**
     * Get valid token
     *
     * @param Curl $curl
     * @param string $username
     * @param string $password
     * @param bool $forceRefresh
     * @return string|false
     */
    private function getValidAccessToken(
        $curl,
        $username,
        $password,
        $forceRefresh = false
    ) {
        $tokens = [];

        $cachedData = $this->cacheManager->load(
            self::INDIA_POST_TOKEN_CACHE_KEY
        );

        if ($cachedData) {
            $tokens = json_decode($cachedData, true) ?: [];
        }

        // Access token still valid
        if (
            !$forceRefresh &&
            !empty($tokens['access_token']) &&
            !empty($tokens['expires_at']) &&
            $this->isTokenValid($tokens['expires_at'])
        ) {
            return $tokens['access_token'];
        }

        // Try refresh token
        if (
            !$forceRefresh &&
            !empty($tokens['refresh_token'])
        ) {
            $newToken = $this->refreshAccessToken(
                $curl,
                $tokens['refresh_token']
            );

            if ($newToken) {
                return $newToken;
            }
        }

        // Fallback login
        return $this->loginAndGetToken(
            $curl,
            $username,
            $password
        );
    }

    /**
     * Login and get token
     *
     * @param Curl $curl
     * @param string $username
     * @param string $password
     * @return string|false
     */
    private function loginAndGetToken(
        $curl,
        $username,
        $password
    ) {
        $url = 'https://app.indiapost.gov.in/beextcustomer/v1/access/login';

        $payload = json_encode([
            'username' => $username,
            'password' => $password
        ]);

        $curl->addHeader('Content-Type', 'application/json');
        $curl->addHeader('accept', 'application/json');

        $curl->post($url, $payload);

        $response = json_decode(
            $curl->getBody(),
            true
        );

        if (
            isset($response['success']) &&
            $response['success'] === true &&
            !empty($response['data']['access_token'])
        ) {
            $this->saveTokensToCache(
                $response['data']
            );

            return $response['data']['access_token'];
        }

        return false;
    }

    /**
     * Refresh token
     *
     * @param Curl $curl
     * @param string $refreshToken
     * @return string|false
     */
    private function refreshAccessToken(
        $curl,
        $refreshToken
    ) {
        $url = 'https://app.indiapost.gov.in/beextcustomer/v1/access/TokenWithRtoken';

        $payload = json_encode([
            'refresh_token' => $refreshToken
        ]);

        $curl->addHeader('Content-Type', 'application/json');
        $curl->addHeader('accept', 'application/json');
        $curl->addHeader(
            'Authorization',
            'Bearer ' . $refreshToken
        );

        $curl->post($url, $payload);

        $response = json_decode(
            $curl->getBody(),
            true
        );

        if (!empty($response['access_token'])) {

            $this->saveTokensToCache($response);

            return $response['access_token'];
        }

        return false;
    }

    /**
     * Save tokens
     *
     * @param array $tokenData
     * @return void
     */
    private function saveTokensToCache(array $tokenData)
    {
        $expiresIn = (int)($tokenData['expires_in'] ?? 1800);

        $tokens = [
            'access_token'  => $tokenData['access_token'] ?? '',
            'refresh_token' => $tokenData['refresh_token'] ?? '',
            'expires_at'    => time() + $expiresIn - 60
        ];

        $this->cacheManager->save(
            json_encode($tokens),
            self::INDIA_POST_TOKEN_CACHE_KEY,
            [],
            7200
        );
    }

    /**
     * Check token validity
     *
     * @param int $expiresAt
     * @return bool
     */
    private function isTokenValid($expiresAt)
    {
        return ((int)$expiresAt > time());
    }

    /**
     * Clear token cache
     *
     * @return void
     */
    private function clearTokenCache()
    {
        $this->cacheManager->remove(
            self::INDIA_POST_TOKEN_CACHE_KEY
        );
    }

    /**
     * BlueDart Tracking
     *
     * Priority:
     * 1. Official BlueDart API
     * 2. eShipz API (Fallback)
     */
    public function bluedartTrackingInfo($title, $trackNumber, $orderId)
    {
        $response = $this->bluedartTrackingInfoWithLicence($title, $trackNumber, $orderId);

        $officialData = json_decode($response, true);

        $isInvalidWaybill = false;

        if (
            isset($officialData['ShipmentData']['Shipment'][0])
        ) {
            $shipment = $officialData['ShipmentData']['Shipment'][0];

            $status = trim($shipment['Status'] ?? '');
            $statusType = trim($shipment['StatusType'] ?? '');

            if (
                $statusType === 'NF' ||
                stripos($status, 'Incorrect Waybill') !== false ||
                stripos($status, 'No Information') !== false
            ) {
                $isInvalidWaybill = true;
            } else {
                // Official API returned valid shipment
                return json_encode(
                    $this->normalizeBlueDartResponse($officialData)
                );
            }
        }

        /**
         * --------------------------------------------------------
         * SECOND : eShipz API (Fallback)
         * --------------------------------------------------------
         */
        if ($isInvalidWaybill) {

            $url = 'https://track.eshipz.com/track?awb=' . urlencode($trackNumber);

            $curl = $this->curlFactory->create();
            $curl->setOption(CURLOPT_TIMEOUT, 30);
            $curl->get($url);

            $html = $curl->getBody();

            $trackingData = [];
            $trackingInfo = [];

            // response_data
            if (preg_match('/var\s+response_data\s*=\s*(\[.*?\]);/s', $html, $matches)) {
                $trackingData = json_decode($matches[1], true);
            }

            // trackingInfo
            if (preg_match('/var\s+trackingInfo\s*=\s*(\{.*?\});/s', $html, $matches)) {
                $trackingInfo = json_decode($matches[1], true);
            }

            return json_encode([
                'success'       => true,
                'tracking_info' => $trackingInfo,
                'checkpoints'   => $trackingData
            ]);
        }

        /**
         * Neither API returned anything useful
         */
        return json_encode([
            'success' => false,
            'message' => 'Tracking information not found.'
        ]);
    }

    private function normalizeBlueDartResponse(array $response)
    {
        $shipment = $response['ShipmentData']['Shipment'][0] ?? [];

        if (empty($shipment)) {
            return [
                'success' => false,
                'tracking_info' => [],
                'checkpoints' => []
            ];
        }

        $trackingInfo = [
            'tracking_number'    => $shipment['WaybillNo'] ?? '',
            'current_status'     => $shipment['Status'] ?? '',
            'expected_delivery'  => $shipment['ExpectedDelivery'] ?? '',
            'origin'             => $shipment['Origin'] ?? '',
            'destination'        => $shipment['Destination'] ?? '',
            'receiver'           => $shipment['ReceivedBy'] ?? '',
            'courier'            => 'BlueDart'
        ];

        $checkpoints = [];

        foreach ($shipment['Scans'] as $scan) {

            $detail = $scan['ScanDetail'];

            $checkpoints[] = [
                'tag' => $this->mapBlueDartTag($detail['ScanType']),
                'subtag' => $detail['ScanType'],
                'message' => trim($detail['Scan']),
                'location' => $detail['ScannedLocation'],
                'city' => $detail['ScannedLocation'],
                'checkpoint_time' => date(
                    'Y-m-d\TH:i:s',
                    strtotime($detail['ScanDate'].' '.$detail['ScanTime'])
                ),
                'slug' => 'bluedart',
                'code' => $detail['ScanCode']
            ];
        }

        return [
            'success' => true,
            'tracking_info' => $trackingInfo,
            'checkpoints' => $checkpoints
        ];
    }

    private function mapBlueDartTag($scanType)
    {
        switch ($scanType) {

            case 'DL':
                return 'Delivered';

            case 'UD':
                return 'InTransit';

            case 'PU':
                return 'InfoReceived';

            default:
                return 'Unknown';
        }
    }

    
    /**
    * Get BlueDart JWT Token
    *
    * @return string
    */
    private function getBlueDartJwtToken()
    {
        // Check cache first
        $token = $this->cacheManager->load(self::BLUEDART_CACHE_KEY);

        if (!empty($token)) {
            return $token;
        }
        // die('asdfdf');
        return $this->generateBlueDartJwtToken();
    }

    /**
     * Generate BlueDart JWT Token
     *
     * @return string
     * @throws \Exception
     */
    private function generateBlueDartJwtToken()
    {
        $url = 'https://apigateway.bluedart.com/in/transportation/token/v1/login';

        $curl = $this->curlFactory->create();

        $curl->setOption(CURLOPT_TIMEOUT, 30);

        $curl->addHeader('ClientID', 'A3FEkZ6wl9xGyZNTCBA9d6mDoA4XxYBR');
        $curl->addHeader('clientSecret', 'kyHoYw7KDg2JRPwt');

        $curl->get($url);

        $response = json_decode($curl->getBody(), true);

        if (empty($response['JWTToken'])) {
            throw new \Exception('Unable to generate BlueDart JWT Token.');
        }

        $token = $response['JWTToken'];
        // Save in Magento cache
        $this->cacheManager->save(
            $token,
            self::BLUEDART_CACHE_KEY,
            [],
            self::BLUEDART_CACHE_LIFETIME
        );

        return $token;
    }

    /**
     * Remove cached JWT token
     */
    private function removeBlueDartJwtToken()
    {
        $this->cacheManager->remove(self::BLUEDART_CACHE_KEY);
    }

    /**
     * BlueDart Tracking
     *
     * @param string $title
     * @param string $trackNumber
     * @param int $orderId
     * @return string
     */
    public function bluedartTrackingInfoWithLicence($title, $trackNumber, $orderId)
    {
        // print_r($response);die;
        $response = $this->callBlueDartTrackingApi($trackNumber);
        $decoded = json_decode($response, true);

        // JWT expired or unauthorized
        if (
            isset($decoded['ShipmentData']['Error']) &&
            stripos($decoded['ShipmentData']['Error'], 'token') !== false
        ) {

            $this->removeBlueDartJwtToken();

            // Retry once with fresh token
            $response = $this->callBlueDartTrackingApi($trackNumber);
        }

        return $response;
    }

    /**
     * Call BlueDart Tracking API
     *
     * @param string $trackNumber
     * @return string
     */
    private function callBlueDartTrackingApi($trackNumber)
    {
        $jwtToken = $this->getBlueDartJwtToken();

        $url =
            'https://apigateway.bluedart.com/in/transportation/tracking/v1?' .
            http_build_query([
                'handler' => 'tnt',
                'action'  => 'custawbquery',
                'loginid' => 'PKL34863',
                'awb'     => 'awb',
                'numbers' => $trackNumber,
                'format'  => 'json',
                'lickey'  => 'vstlrsl4it61u7z7oqou54qsivospqp0',
                'verno'   => '1.3',
                'scan'    => '1'
            ]);

        $curl = $this->curlFactory->create();

        $curl->setOption(CURLOPT_TIMEOUT, 30);

        $curl->addHeader('JWTToken', $jwtToken);

        $curl->get($url);

        return $curl->getBody();
    }

    /**
     * @param string $title
     * @param string $trackNumber
     * @param int $orderId
     * @return mixed
     */
    public function delhiveryTrackingInfo($title, $trackNumber, $orderId)
    {
        $curl = $this->curlFactory->create();
        $url = 'https://track.delhivery.com/api/v1/packages/json/?waybill=' . urlencode($trackNumber);
        $curl->addHeader('Authorization', 'Token a63cd01b5f4cba43da08e60c757cc06a564ab216');
        $curl->addHeader('Content-Type', 'application/json');
        $curl->setOption(CURLOPT_TIMEOUT, 30);
        $curl->get($url);
        $response = $curl->getBody();

        // $this->logger->info("Order ID: {$orderId}, Carrier: Delhivery, Track Number: {$trackNumber}, API Response: " . $response);
        return $response;
    }

    /**
     *
     * @param int $shipmentId
     * @return mixed
     */
    public function getShipmentItemsDetails($shipmentId)
    {
        $shipment = $this->shipmentCollectionFactory->create()
            ->addFieldToFilter('increment_id', $shipmentId)
            ->getFirstItem();
        if (!$shipment->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Shipment not found.')
            );
        }
        $items = [];
        foreach ($shipment->getItems() as $item) {
            $orderItem = $item->getOrderItem();
            $image = '';
            if ($orderItem->getProduct() && $orderItem->getProduct()->getSmallImage()) {
                $image = $orderItem->getProduct()->getSmallImage();
            }
            $items[] = [
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'qty_shipped' => (int)$item->getQty(),
                'image' => $image
            ];
        }
        return $items;
    }

    // public function isCourierMatch($title, array $keywords, $maxDistance = 3)
    // {
    //     $title = strtolower(trim($title));

    //     foreach ($keywords as $keyword) {
    //         $keyword = strtolower($keyword);

    //         if (strpos($title, $keyword) !== false) {
    //             return true;
    //         }

    //         if (levenshtein($title, $keyword) <= $maxDistance) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

    public function isCourierMatch($title, array $keywords, $maxDistance = 2)
    {
        // Normalize title
        $title = strtolower($title);
        $title = preg_replace('/[^a-z0-9\s]/', ' ', $title); // Remove special chars
        $title = preg_replace('/\s+/', ' ', trim($title));

        foreach ($keywords as $keyword) {

            // Normalize keyword
            $keyword = strtolower($keyword);
            $keyword = preg_replace('/[^a-z0-9\s]/', ' ', $keyword);
            $keyword = preg_replace('/\s+/', ' ', trim($keyword));

            // Exact substring match
            if (strpos($title, $keyword) !== false) {
                return true;
            }

            // Compare every consecutive phrase in the title
            $titleWords = explode(' ', $title);
            $keywordWords = explode(' ', $keyword);
            $count = count($keywordWords);

            for ($i = 0; $i <= count($titleWords) - $count; $i++) {
                $phrase = implode(' ', array_slice($titleWords, $i, $count));

                if (levenshtein($phrase, $keyword) <= $maxDistance) {
                    return true;
                }
            }
        }

        return false;
    }
}
