<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;

class AuthTypeOptions implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('None')],
            ['value' => 'api_key', 'label' => __('API Key')],
            ['value' => 'bearer_token', 'label' => __('Bearer Token')],
            ['value' => 'basic_auth', 'label' => __('Basic Auth')],
            ['value' => 'oauth', 'label' => __('OAuth')],
            ['value' => 'custom', 'label' => __('Custom')],
        ];
    }
}
