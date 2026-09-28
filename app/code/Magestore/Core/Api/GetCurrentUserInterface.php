<?php

namespace Magestore\Core\Api;

/**
 * Provider get current interface
 */
interface GetCurrentUserInterface
{
    /**
     * Set Current User
     *
     * @param \Magestore\Core\Api\Data\CurrentUserInterface $currentUser
     * @return void
     */
    public function setCurrentUser(\Magestore\Core\Api\Data\CurrentUserInterface $currentUser);

    /**
     * Get Current User
     *
     * @return \Magestore\Core\Api\Data\CurrentUserInterface|null
     */
    public function getCurrentUser();
}
