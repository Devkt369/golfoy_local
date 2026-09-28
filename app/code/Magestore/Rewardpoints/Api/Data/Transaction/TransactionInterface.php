<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_RewardPoints
 * @module      RewardPoints
 * @author        Magestore Developer
 *
 * @copyright   Copyright (c) 2016 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 *
 */

namespace Magestore\Rewardpoints\Api\Data\Transaction;

/**
 * Rewardpoints - TransactionInterface
 */
interface TransactionInterface
{
    const CUSTOMER_ID = "customer_id";

    /**
     * Get Transaction Id
     *
     * @return mixed
     */
    public function getTransactionId();

    /**
     * Get Reward Id
     *
     * @return mixed
     */
    public function getRewardId();

    /**
     * Get Customer Id
     *
     * @return mixed
     */
    public function getCustomerId();

    /**
     * Get Customer Email
     *
     * @return mixed
     */
    public function getCustomerEmail();

    /**
     * Get Current Point Balance
     *
     * @return mixed
     */
    public function getCurrentPointBalance();

    /**
     * Get Title
     *
     * @return mixed
     */
    public function getTitle();

    /**
     * Get Action
     *
     * @return mixed
     */
    public function getAction();

    /**
     * Get Store Id
     *
     * @return mixed
     */
    public function getStoreId();

    /**
     * Get Point Amount
     *
     * @return mixed
     */
    public function getPointAmount();

    /**
     * Get Status
     *
     * @return mixed
     */
    public function getStatus();

    /**
     * Get Status Label
     *
     * @return mixed
     */
    public function getStatusLabel();

    /**
     * Get Created Time
     *
     * @return mixed
     */
    public function getCreatedTime();

    /**
     * Get Updated Time
     *
     * @return mixed
     */
    public function getUpdatedTime();

    /**
     * Get Expiration Date
     *
     * @return mixed
     */
    public function getExpirationDate();

    /**
     * Get Order Id
     *
     * @return mixed
     */
    public function getOrderId();

    /**
     * Get Order Increment Id
     *
     * @return mixed
     */
    public function getOrderIncrementId();

    /**
     * Get Order Amount
     *
     * @return mixed
     */
    public function getOrderAmount();

    /**
     * Get Discount
     *
     * @return mixed
     */
    public function getDiscount();

    /**
     * Get Extra Content
     *
     * @return mixed
     */
    public function getExtraContent();

    /**
     * Set Transaction Id
     *
     * @param int $transactionId
     * @return mixed
     */
    public function setTransactionId($transactionId);

    /**
     * Set Reward Id
     *
     * @param int $rewardId
     * @return mixed
     */
    public function setRewardId($rewardId);

    /**
     * Set Customer Id
     *
     * @param int $customerId
     * @return mixed
     */
    public function setCustomerId($customerId);

    /**
     * Set Customer Email
     *
     * @param string $customerEmail
     * @return mixed
     */
    public function setCustomerEmail($customerEmail);

    /**
     * Set Current Point Balance
     *
     * @param int $balance
     * @return mixed
     */
    public function setCurrentPointBalance($balance);

    /**
     * Set Title
     *
     * @param string $title
     * @return mixed
     */
    public function setTitle($title);

    /**
     * Set Action
     *
     * @param string $action
     * @return mixed
     */
    public function setAction($action);

    /**
     * Set Store Id
     *
     * @param int $storeId
     * @return mixed
     */
    public function setStoreId($storeId);

    /**
     * Set Point Amount
     *
     * @param int $pointAmount
     * @return mixed
     */
    public function setPointAmount($pointAmount);

    /**
     * Set Status
     *
     * @param int $status
     * @return mixed
     */
    public function setStatus($status);

    /**
     * Set Created Time
     *
     * @param string $createdTime
     * @return mixed
     */
    public function setCreatedTime($createdTime);

    /**
     * Set Updated Time
     *
     * @param string $time
     * @return mixed
     */
    public function setUpdatedTime($time);

    /**
     * Set Expiration Date
     *
     * @param string $time
     * @return mixed
     */
    public function setExpirationDate($time);

    /**
     * Set Order Id
     *
     * @param int $id
     * @return mixed
     */
    public function setOrderId($id);

    /**
     * Set Order Increment Id
     *
     * @param string $id
     * @return mixed
     */
    public function setOrderIncrementId($id);

    /**
     * Set Order Amount
     *
     * @param float $amount
     * @return mixed
     */
    public function setOrderAmount($amount);

    /**
     * Set Discount
     *
     * @param float $discount
     * @return mixed
     */
    public function setDiscount($discount);

    /**
     * Set Extra Content
     *
     * @param string $content
     * @return mixed
     */
    public function setExtraContent($content);
}
