<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Plugin\Staff;

use Magento\Framework\Registry;
use Magestore\Webpos\Model\Staff\StaffManagement as StaffManagementCore;

/**
 * Class StaffManagement
 *
 * Plugin to validate permission for click and collect
 */
class StaffManagement
{
    const CLICK_AND_COLLECT_ACCESS_ACL = 'Magestore_ClickAndCollectAdminUi::click_and_collect';

    const CLICK_AND_COLLECT_LOGIN_API_URL = 'click-and-collect/staff/login';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * StaffManagement constructor.
     *
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * Before validate access app
     *
     * @param StaffManagementCore $staffManagement
     * @param int $roleId
     * @param string $appAcl
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeValidateAccessApp(
        StaffManagementCore $staffManagement,
        int $roleId,
        string $appAcl
    ) : array {
        if ($this->isClickAndCollectLoginApi()) {
            return [$roleId, self::CLICK_AND_COLLECT_ACCESS_ACL];
        } else {
            return [
                $roleId, $appAcl
            ];
        }
    }

    /**
     * Is click and collect login api
     *
     * @return bool
     */
    private function isClickAndCollectLoginApi()
    {
        $posApiUrl = $this->registry->registry('pos_api_url');
        if (!$posApiUrl) {
            return false;
        }
        if (strpos($posApiUrl, self::CLICK_AND_COLLECT_LOGIN_API_URL) !== false) {
            return true;
        }
        return false;
    }
}
