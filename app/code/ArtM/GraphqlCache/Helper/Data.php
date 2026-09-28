<?php
namespace ArtM\GraphqlCache\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ALLOWED_ORIGINS = 'react_config/security/allowed_domains';

    public function getAllowedOrigins()
    {
        $origins = $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_ORIGINS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        if (!$origins) {
            return []; // No config set
        }

        return array_map('trim', explode(',', $origins));
    }

}
