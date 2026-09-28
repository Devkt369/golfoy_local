<?php
namespace ArtM\Delhiveryqc\Model\Oauth;

class Token extends \Magento\Integration\Model\Oauth\Token
{
    /**
     * Get token lifetime (in seconds)
     *
     * @return int
     */
    public function getTokenLifetime()
    {
        return 3600000; // Change this value as needed
    }
}