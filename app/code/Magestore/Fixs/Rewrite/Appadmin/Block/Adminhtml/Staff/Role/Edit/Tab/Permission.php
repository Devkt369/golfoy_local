<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Fixs\Rewrite\Appadmin\Block\Adminhtml\Staff\Role\Edit\Tab;

/**
 * Permission
 */
class Permission extends \Magestore\Appadmin\Block\Adminhtml\Staff\Role\Edit\Tab\Permission
{
    /**
     * @var string
     */
    protected $_template = 'Magestore_Fixs::role/edit.phtml';
    
    /**
     * Get Json Representation of Resource Tree
     *
     * @return array
     */
    public function getTree()
    {
        return $this->_integrationData->mapResources($this->getAclResources(), $this->getSelectedResources());
    }
    
    /**
     * Get lit of all ACL resources declared in the system.
     *
     * @return array
     */
    private function getAclResources()
    {
        $resources = $this->_aclResourceProvider->getAclResources();
        $configResource = array_filter(
            $resources,
            function ($node) {
                return $node['id'] == 'Magestore_Webpos::webpos';
            }
        );
        $configResource = reset($configResource);
        return isset($configResource['children']) ? $configResource['children'] : [];
    }
}
