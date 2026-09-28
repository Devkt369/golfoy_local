<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\Staff;

use Magento\Framework\Model\AbstractModel;
use Magestore\Webpos\Api\Data\Staff\SessionInterface;

/**
 * Model Session
 */
class Session extends AbstractModel implements SessionInterface
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magestore\Webpos\Model\ResourceModel\Staff\Session::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }
    /**
     * @inheritDoc
     */
    public function getStaffId()
    {
        return $this->getData(self::STAFF_ID);
    }

    /**
     * @inheritDoc
     */
    public function setStaffId($staffId)
    {
        return $this->setData(self::STAFF_ID, $staffId);
    }
    /**
     * @inheritDoc
     */
    public function getLoggedDate()
    {
        return $this->getData(self::LOGGED_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setLoggedDate($loggedDate)
    {
        return $this->setData(self::LOGGED_DATE, $loggedDate);
    }
    /**
     * @inheritDoc
     */
    public function getSessionId()
    {
        return $this->getData(self::SESSION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setSessionId($sessionId)
    {
        return $this->setData(self::SESSION_ID, $sessionId);
    }

    /**
     * @inheritDoc
     */
    public function getLocationId()
    {
        return $this->getData(self::LOCATION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setLocationId($locationId)
    {
        return $this->setData(self::LOCATION_ID, $locationId);
    }

    /**
     * @inheritDoc
     */
    public function getPosId()
    {
        return $this->getData(self::POS_ID);
    }

    /**
     * @inheritDoc
     */
    public function setPosId($posId)
    {
        return $this->setData(self::POS_ID, $posId);
    }

    /**
     * @inheritdoc
     */
    public function getHasException()
    {
        return $this->getData(self::HAS_EXCEPTION);
    }

    /**
     * @inheritdoc
     */
    public function setHasException($hasException)
    {
        return $this->setData(self::HAS_EXCEPTION, $hasException);
    }

    /**
     * @inheritdoc
     */
    public function getApp()
    {
        return $this->getData(self::APP);
    }

    /**
     * @inheritdoc
     */
    public function setApp($app)
    {
        return $this->setData(self::APP, $app);
    }
    /**
     * @inheritDoc
     */
    public function getExtensionAttributes()
    {
        return $this->getData(self::EXTENSION_ATTRIBUTES_KEY);
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(
        \Magestore\Webpos\Api\Data\Staff\SessionExtensionInterface $extensionAttributes
    ) {
        return $this->setData(self::EXTENSION_ATTRIBUTES_KEY, $extensionAttributes);
    }
}
