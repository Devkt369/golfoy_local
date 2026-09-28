<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Fixs\Plugin\Webpos\Integration;

/**
 * DataPlugin
 */
class DataPlugin
{
    const PATH = 'integration';
    
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $request;
    
    /**
     * Validator constructor.
     *
     * @param \Magento\Framework\Webapi\Rest\Request $request
     */
    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request
    ) {
        $this->request = $request;
    }
    
    /**
     * Remove posclient resource
     *
     * @param \Magento\Integration\Helper\Data $helper
     * @param array $resources
     * @param array $selectedResources
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    
    public function beforeMapResources(
        \Magento\Integration\Helper\Data $helper,
        array                            $resources,
        array                            $selectedResources = []
    ) {
        $restricted = $this->getRestrictedIds();
        foreach ($resources as $key => $resource) {
            if (in_array($resource['id'], $restricted)) {
                unset($resources[$key]);
            }
        }
        return [$resources, $selectedResources];
    }
    
    /**
     * Get restricted ids
     *
     * @return array|string[]
     */
    public function getRestrictedIds()
    {
        $pathInfo = $this->request->getPathInfo();
        if (strpos($pathInfo, self::PATH) === false) {
            return ['Magestore_Webpos::posclient'];
        }
        return [];
    }
}
