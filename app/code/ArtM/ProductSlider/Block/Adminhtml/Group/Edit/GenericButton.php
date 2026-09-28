<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Block\Adminhtml\Group\Edit;

use Magento\Backend\Block\Widget\Context;

class GenericButton
{
    public function __construct(
        protected readonly Context $context
    ) {
    }

    public function getGroupId(): int
    {
        return (int)$this->context->getRequest()->getParam('group_id');
    }

    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
