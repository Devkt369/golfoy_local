<?php

namespace Magestore\Core\Api\Data;

/**
 * Object current user
 */
interface CurrentUserInterface
{
    const USER_TYPE = 'user_type';
    const USER_ID = 'user_id';
    const USER_NAME = 'user_name';

    /**
     * Get user type
     *
     * @return string
     */
    public function getUserType();

    /**
     * Set user type
     *
     * @param string $userType
     * @return $this
     */
    public function setUserType(string $userType);

    /**
     * Get user id
     *
     * @return int
     */
    public function getUserId();

    /**
     * Set user id
     *
     * @param int $id
     * @return $this
     */
    public function setUserId(int $id);

    /**
     * Get user name
     *
     * @return string
     */
    public function getUserName();

    /**
     * Set user name
     *
     * @param string $userName
     * @return $this
     */
    public function setUserName(string $userName);
}
