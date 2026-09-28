<?php

declare(strict_types=1);

namespace Magestore\Core\Model;

use Magento\Framework\DataObject;
use Magestore\Core\Api\Data\CurrentUserInterface;

/**
 * Model current user
 */
class CurrentUser extends DataObject implements CurrentUserInterface
{
    /**
     * @inheritDoc
     */
    public function getUserType()
    {
        return $this->getData(self::USER_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setUserType(string $userType)
    {
        $this->setData(self::USER_TYPE, $userType);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUserId()
    {
        return $this->getData(self::USER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setUserId(int $id)
    {
        $this->setData(self::USER_ID, $id);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUserName()
    {
        return $this->getData(self::USER_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setUserName(string $userName)
    {
        $this->setData(self::USER_NAME, $userName);
        return $this;
    }
}
