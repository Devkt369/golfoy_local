<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
//declare(strict_types = 1);

namespace Magestore\WebposIntegration\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Patch Data - EnableIntegrationAsBearer
 */
class EnableIntegrationAsBearer implements DataPatchInterface
{
    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $configWriter;
    
    /**
     * EnableIntegrationAsBearer constructor.
     *
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     */
    public function __construct(
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    ) {
        $this->configWriter = $configWriter;
    }
    
    /**
     * Apply
     *
     * @return $this|\Magento\Framework\Setup\Patch\DataPatchInterface
     */
    public function apply()
    {
        $this->configWriter->save('oauth/consumer/enable_integration_as_bearer', 1);
        return $this;
    }
    
    /**
     * GetDependencies
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }
    
    /**
     * GetAliases
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
}
