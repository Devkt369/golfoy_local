<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\DistanceProvider\GoogleMap;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryDistanceBasedSourceSelection\Model\Convert\LatLngToQueryString;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;
use Magestore\ClickAndCollectApi\Api\GetDistanceInterface;
use Magestore\Webpos\Model\DistanceProvider\GoogleMap\GetApiKey;

/**
 * @inheritdoc
 */
class GetDistance implements GetDistanceInterface
{
    private const GOOGLE_ENDPOINT = 'https://maps.googleapis.com/maps/api/distancematrix/json';
    private const DISTANCE_CALCULATION_MODE = 'driving';
    private const DISTANCE_CALCULATION_VALUE = 'distance';
    private const ZERO_RESULT_RESPONSE = 'ZERO_RESULTS';

    /**
     * @var array
     */
    private $distanceCache = [];

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var GetApiKey
     */
    private $getApiKey;

    /**
     * @var LatLngToQueryString
     */
    private $latLngToQueryString;

    /**
     * GetLatLngFromAddress constructor.
     *
     * @param ClientInterface $client
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $json
     * @param GetApiKey $getApiKey
     * @param LatLngToQueryString $latLngToQueryString
     */
    public function __construct(
        ClientInterface $client,
        ScopeConfigInterface $scopeConfig,
        Json $json,
        GetApiKey $getApiKey,
        LatLngToQueryString $latLngToQueryString
    ) {
        $this->client = $client;
        $this->json = $json;
        $this->getApiKey = $getApiKey;
        $this->scopeConfig = $scopeConfig;
        $this->latLngToQueryString = $latLngToQueryString;
    }

    /**
     * @inheritdoc
     */
    public function execute(LatLngInterface $source, LatLngInterface $destination): float
    {
        $sourceString = $this->latLngToQueryString->execute($source);
        $destinationString =  $this->latLngToQueryString->execute($destination);

        $key = $sourceString . '|' . $destinationString;

        if (!isset($this->distanceCache[$key])) {
            $queryString = http_build_query([
                'key' => $this->getApiKey->execute(),
                'origins' => $sourceString,
                'destinations' => $destinationString,
                'mode' => self::DISTANCE_CALCULATION_MODE,
            ]);

            $this->client->get(self::GOOGLE_ENDPOINT . '?' . $queryString);
            if ($this->client->getStatus() !== 200) {
                throw new LocalizedException(__('Unable to connect google API for distance matrix'));
            }

            $res = $this->json->unserialize($this->client->getBody());

            if ($res['status'] !== 'OK'
                || $res['rows'][0]['elements'][0]['status'] === self::ZERO_RESULT_RESPONSE
            ) {
                throw new LocalizedException(
                    __(
                        'Unable to get distance between %1 and %2',
                        $sourceString,
                        $destinationString
                    )
                );
            }

            $element = $res['rows'][0]['elements'][0];

            $this->distanceCache[$key] = (float)$element[self::DISTANCE_CALCULATION_VALUE]['value'];
        }

        return $this->distanceCache[$key];
    }
}
