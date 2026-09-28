<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposIntegration\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\InstallDataInterface;
use Magestore\WebposIntegration\Api\ApiServiceInterface;

/**
 * WebposIntegration - InstallData
 */
class InstallData implements InstallDataInterface
{
    
    /**
     * @var ApiServiceInterface
     */
    private $apiService;
    
    /**
     * InstallData constructor.
     *
     * @param \Magestore\WebposIntegration\Api\ApiServiceInterface $apiService
     */
    public function __construct(
        ApiServiceInterface $apiService
    ) {
        $this->apiService = $apiService;
    }
    
    /**
     * Install
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->apiService->setupIntegration();
        $integration = $this->apiService->getIntegration();
        $this->apiService->createAccessToken($integration);
    }
}
