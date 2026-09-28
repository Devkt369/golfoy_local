<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types = 1);

namespace Magestore\Webpos\Model\DataProvider\Staff;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magestore\Webpos\Api\DataProvider\GetCurrentStaffIdBySessionInterface;
use Magestore\Webpos\Api\Staff\StaffManagementInterface;

/**
 * Class GetCurrentStaffIdBySession
 *
 * Data Provider to get staff by session
 */
class GetCurrentStaffIdBySession extends DataObject implements GetCurrentStaffIdBySessionInterface
{
    /**
     * @var StaffManagementInterface
     */
    protected $staffManagement;

    /**
     * GetCurrentStaffById constructor.
     *
     * @param StaffManagementInterface $staffManagement
     * @param array $data
     */
    public function __construct(
        StaffManagementInterface $staffManagement,
        $data = []
    ) {
        $this->staffManagement = $staffManagement;
        parent::__construct($data);
    }

    /**
     * Get staff by session
     *
     * @param string $session
     * @return int|null
     * @throws LocalizedException
     */
    public function execute($session)
    {
        $staffId = $this->_getData(self::STAFF_ID);
        if (!$staffId) {
            $staffId = $this->staffManagement->authorizeSession($session);
            $this->setData(self::STAFF_ID, $staffId);
        }
        return $staffId;
    }
}
