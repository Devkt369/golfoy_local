<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\GoogleMap;

use Magento\Framework\Exception\LocalizedException;
use Magestore\ClickAndCollectApi\Api\GoogleMap\GetMapImageFromPositionInterface;
use Magestore\Webpos\Model\DistanceProvider\GoogleMap\GetApiKey;

/**
 * Class GetMapImageFromPosition
 *
 * Get map from lat and lng
 */
class GetMapImageFromPosition implements GetMapImageFromPositionInterface
{
    /**
     * @var GetApiKey
     */
    private $apiKey;

    /**
     * GetMapImageFromPosition constructor.
     *
     * @param GetApiKey $apiKey
     */
    public function __construct(
        GetApiKey $apiKey
    ) {
        $this->apiKey = $apiKey;
    }

    /**
     * Execute method
     *
     * @param string $lat
     * @param string $lng
     * @return array
     */
    public function execute(string $lat, string $lng): array
    {
        try {
            $gooleAPI = $this->apiKey->execute();
        } catch (LocalizedException $exception) {
            return [
                'image' => '',
                'url' => ''
            ];
        }
        return [
            'image' => 'http://maps.google.com/maps/api/staticmap?key='
                . $gooleAPI .
                '&center='
                . $lat . ','
                . $lng
                . '&zoom=15&size=200x200&markers=color:red|label:S|'
                . $lat . ','
                . $lng . '&sensor=false',
            'url' => 'https://www.google.com/maps/search/?api=1&query='. $lat . ',' . $lng
        ];
    }
}
