<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Webpos\Api\Staff;

interface StaffManagementInterface
{
    /**
     * Function login api
     *
     * @param \Magestore\Appadmin\Api\Data\Staff\StaffInterface $staff
     * @param string $app
     * @return \Magestore\Webpos\Api\Data\Staff\Login\LoginResultInterface
     * @throws \Magento\Framework\Exception\AuthorizationException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function login($staff, $app = \Magestore\Webpos\Api\Data\Staff\SessionInterface::POS_APP);

    /**
     * Function continueLogin api
     *
     * @return \Magestore\Webpos\Api\Data\Staff\Login\LoginResultInterface
     */
    public function continueLogin();

    /**
     * Function Logout
     *
     * @return \Magestore\Webpos\Api\Data\Staff\Logout\LogoutResultInterface
     */
    public function logout();

    /**
     * Function isAllowResource
     *
     * @param string $resource
     * @param int $staffId
     * @return bool
     */
    public function isAllowResource($resource, $staffId);

    /**
     * Function is Allow Resource By Session Id
     *
     * @param string $resource
     * @param string $sessionId
     * @return bool
     */
    public function isAllowResourceBySessionId(string $resource, string $sessionId);

    /**
     * Function authorizeSession
     *
     * @param string $phpSession
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorizeSession($phpSession);

    /**
     * Change password api
     *
     * @param string $username
     * @param string $oldPassword
     * @param string $newPassword
     * @param string $confirmationPassword
     * @return boolean|string
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function changePassword($username, $oldPassword, $newPassword, $confirmationPassword);
}
