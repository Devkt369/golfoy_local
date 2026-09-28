<?php

namespace Meetanshi\Sms\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class SMSQueue
 * @package Meetanshi\Sms\Model
 */
class SMSQueue extends AbstractModel
{
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Meetanshi\Sms\Model\ResourceModel\SMSQueue::class);
    }

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getEntityId()
    {
        return $this->getData('entity_id');
    }

    /**
     * Set entity ID
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId)
    {
        return $this->setData('entity_id', $entityId);
    }

    /**
     * Get mobile number
     *
     * @return string
     */
    public function getMobileNumber()
    {
        return $this->getData('mobile_number');
    }

    /**
     * Set mobile number
     *
     * @param string $mobileNumber
     * @return $this
     */
    public function setMobileNumber($mobileNumber)
    {
        return $this->setData('mobile_number', $mobileNumber);
    }

    /**
     * Get message payload
     *
     * @return string
     */
    public function getMessagePayload()
    {
        return $this->getData('message_payload');
    }

    /**
     * Set message payload
     *
     * @param string $payload
     * @return $this
     */
    public function setMessagePayload($payload)
    {
        return $this->setData('message_payload', $payload);
    }

    /**
     * Get observer name
     *
     * @return string|null
     */
    public function getObserverName()
    {
        return $this->getData('observer_name');
    }

    /**
     * Set observer name
     *
     * @param string $observerName
     * @return $this
     */
    public function setObserverName($observerName)
    {
        return $this->setData('observer_name', $observerName);
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getData('status');
    }

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }

    /**
     * Get attempts
     *
     * @return int
     */
    public function getAttempts()
    {
        return $this->getData('attempts');
    }

    /**
     * Set attempts
     *
     * @param int $attempts
     * @return $this
     */
    public function setAttempts($attempts)
    {
        return $this->setData('attempts', $attempts);
    }

    /**
     * Get error message
     *
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->getData('error_message');
    }

    /**
     * Set error message
     *
     * @param string $errorMessage
     * @return $this
     */
    public function setErrorMessage($errorMessage)
    {
        return $this->setData('error_message', $errorMessage);
    }

    /**
     * Get created at
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    /**
     * Get updated at
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData('updated_at');
    }

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData('updated_at', $updatedAt);
    }

    /**
     * Get scheduled at
     *
     * @return string|null
     */
    public function getScheduledAt()
    {
        return $this->getData('scheduled_at');
    }

    /**
     * Set scheduled at
     *
     * @param string $scheduledAt
     * @return $this
     */
    public function setScheduledAt($scheduledAt)
    {
        return $this->setData('scheduled_at', $scheduledAt);
    }
}