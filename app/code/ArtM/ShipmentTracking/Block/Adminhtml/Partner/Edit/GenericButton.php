<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Block\Adminhtml\Partner\Edit;

use Magento\Backend\Block\Widget\Context;

class GenericButton
{
    public function __construct(
        protected readonly Context $context
    ) {
    }

    public function getPartnerId(): int
    {
        return (int)$this->context->getRequest()->getParam('partner_id');
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
