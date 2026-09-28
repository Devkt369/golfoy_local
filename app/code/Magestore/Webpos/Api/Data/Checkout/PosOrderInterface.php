<?php
/**
 * Copyright © 2021 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Api\Data\Checkout;

interface PosOrderInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    const ID = 'id';
    const STATUS = 'status';
    const CREATED_AT = 'created_at';
    const ADDITIONAL_DATA = 'additional_data';
    const INCREMENT_ID = 'increment_id';
    const SESSION_ID = 'session_id';
    const STORE_ID = 'store_id';
    const LOCATION_ID = 'location_id';
    const POS_ID = 'pos_id';
    const POS_STAFF_ID = 'pos_staff_id';
    const ORDER_CREATED_TIME = 'order_created_time';
    const PARAMS = 'params';
    const QUEUEING_STATUS = 'queueing_status';
    const NUMBER_OF_TRIALS = 'number_of_trials';
    const IS_NOTIFY_ERROR = 'is_notify_error';

    const QUEUEING_STATUS_PROCESSING = 'processing';
    const QUEUEING_STATUS_FAIL = 'fail';
    const QUEUEING_STATUS_COMPLETED = 'completed';

    const ERROR_NOTIFICATION_ALREADY_SENT = 1;
    const ERROR_NOTIFICATION_DID_NOT_SENT_YET = 0;

    /**
     * Get id
     *
     * @return int|null
     */
    public function getPosOrderId(): ?int;

    /**
     * Set id
     *
     * @param int|null $id
     * @return void
     */
    public function setPosOrderId(?int $id): void;

    /**
     * Get Status
     *
     * @return int|null
     */
    public function getStatus(): ?int;

    /**
     * Set Status
     *
     * @param int|null $status
     * @return void
     */
    public function setStatus(?int $status): void;

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set Created At
     *
     * @param string|null $createdAt
     * @return void
     */
    public function setCreatedAt(?string $createdAt): void;

    /**
     * Get Additional Data
     *
     * @return string|null
     */
    public function getAdditionalData(): ?string;

    /**
     * Set Additional Data
     *
     * @param string|null $additionalData
     * @return void
     */
    public function setAdditionalData(?string $additionalData): void;

    /**
     * Get Increment Id
     *
     * @return string|null
     */
    public function getIncrementId(): ?string;

    /**
     * Set Increment Id
     *
     * @param string|null $incrementId
     * @return void
     */
    public function setIncrementId(?string $incrementId): void;

    /**
     * Get Session Id
     *
     * @return string|null
     */
    public function getSessionId(): ?string;

    /**
     * Set Session Id
     *
     * @param string|null $sessionId
     * @return void
     */
    public function setSessionId(?string $sessionId): void;

    /**
     * Get Store Id
     *
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * Set Store Id
     *
     * @param int|null $storeId
     * @return void
     */
    public function setStoreId(?int $storeId): void;

    /**
     * Get Location Id
     *
     * @return int|null
     */
    public function getLocationId(): ?int;

    /**
     * Set Location Id
     *
     * @param int|null $locationId
     * @return void
     */
    public function setLocationId(?int $locationId): void;

    /**
     * Get Pos Id
     *
     * @return int|null
     */
    public function getPosId(): ?int;

    /**
     * Set Pos Id
     *
     * @param int|null $posId
     * @return void
     */
    public function setPosId(?int $posId): void;

    /**
     * Get Pos Staff Id
     *
     * @return int|null
     */
    public function getPosStaffId(): ?int;

    /**
     * Set Pos Staff Id
     *
     * @param int|null $posStaffId
     * @return void
     */
    public function setPosStaffId(?int $posStaffId): void;

    /**
     * Get Order Created Time
     *
     * @return string|null
     */
    public function getOrderCreatedTime(): ?string;

    /**
     * Set Order Created Time
     *
     * @param string|null $orderCreatedTime
     * @return void
     */
    public function setOrderCreatedTime(?string $orderCreatedTime): void;

    /**
     * Get Params
     *
     * @return string|null
     */
    public function getParams(): ?string;

    /**
     * Set Params
     *
     * @param string|null $params
     * @return void
     */
    public function setParams(?string $params): void;

    /**
     * Get Queueing Status
     *
     * @return string|null
     */
    public function getQueueingStatus(): ?string;

    /**
     * Set Queueing Status
     *
     * @param string|null $queueingStatus
     * @return void
     */
    public function setQueueingStatus(?string $queueingStatus): void;

    /**
     * Get Number Of Trials
     *
     * @return int|null
     */
    public function getNumberOfTrials(): ?int;

    /**
     * Set Number Of Trials
     *
     * @param int|null $numberOfTrials
     * @return void
     */
    public function setNumberOfTrials(?int $numberOfTrials): void;

    /**
     * Get Is Notify Error
     *
     * @return int|null
     */
    public function getIsNotifyError(): ?int;

    /**
     * Set Is Notify Error
     *
     * @param int|null $isNotifyError
     * @return void
     */
    public function setIsNotifyError(?int $isNotifyError): void;

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magestore\Webpos\Api\Data\Checkout\PosOrderExtensionInterface|null
     * @since 102.0.0
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magestore\Webpos\Api\Data\Checkout\PosOrderExtensionInterface $extensionAttributes
     * @return void
     * @since 102.0.0
     */
    public function setExtensionAttributes(
        \Magestore\Webpos\Api\Data\Checkout\PosOrderExtensionInterface $extensionAttributes
    );
}
