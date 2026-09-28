<?php

declare(strict_types=1);

namespace ArtM\LiveChat\Block\Adminhtml;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Inbox extends Template
{
    public function __construct(Context $context, private readonly ScopeConfigInterface $scopeConfig, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function getJsConfig(): string
    {
        return (string) json_encode([
            'serverUrl' => rtrim((string) $this->scopeConfig->getValue('artm_livechat/general/server_url'), '/'),
            'tokenUrl' => $this->getUrl('artm_livechat/inbox/token'),
        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
    }
}