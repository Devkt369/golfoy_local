<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Customercredit\Api\Data;

/**
 * Represents Transaction Interface
 *
 * @api
 */
interface TransactionInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const TRANSACTION_ID = 'transaction_id';
    const CUSTOMER_ID = 'customer_id';
    const TYPE_TRANSACTION_ID = 'type_transaction_id';
    const DETAIL_TRANSACTION = 'detail_transaction';
    const ORDER_INCREMENT_ID = 'order_increment_id';
    const AMOUNT_CREDIT = 'amount_credit';
    const BEGIN_BALANCE = 'begin_balance';
    const END_BALANCE = 'end_balance';
    const TRANSACTION_TIME = 'transaction_time';
    const CUSTOMER_GROUP_IDS = 'customer_group_ids';
    const STATUS = 'status';
    const SPENT_CREDIT = 'spent_credit';
    const RECEIVED_CREDIT = 'received_credit';

    /**
     * Get transaction id
     *
     * @return int|null
     */
    public function getTransactionId(): ?int;

    /**
     * Set transaction id
     *
     * @param int|null $transactionId
     * @return $this
     */
    public function setTransactionId(?int $transactionId);

    /**
     * Get customer id
     *
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * Set customer id
     *
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId(?int $customerId);

    /**
     * Get TypeTransactionId id
     *
     * @return int|null
     */
    public function getTypeTransactionId(): ?int;

    /**
     * Set TypeTransactionId id
     *
     * @param int|null $typeTransactionId
     * @return $this
     */
    public function setTypeTransactionId(?int $typeTransactionId);

    /**
     * Get detailTransaction id
     *
     * @return string|null
     */
    public function getDetailTransaction(): ?string;

    /**
     * Set detailTransaction id
     *
     * @param string|null $detailTransaction
     * @return $this
     */
    public function setDetailTransaction(?string $detailTransaction);

    /**
     * Get OrderIncrementId id
     *
     * @return int|null
     */
    public function getOrderIncrementId(): ?int;

    /**
     * Set OrderIncrementId id
     *
     * @param int|null $orderIncrementId
     * @return $this
     */
    public function setOrderIncrementId(?int $orderIncrementId);

    /**
     * Get AmountCredit id
     *
     * @return float|null
     */
    public function getAmountCredit(): ?float;

    /**
     * Set AmountCredit id
     *
     * @param float|null $amountCredit
     * @return $this
     */
    public function setAmountCredit(?float $amountCredit);

    /**
     * Get BeginBalance id
     *
     * @return float|null
     */
    public function getBeginBalance(): ?float;

    /**
     * Set BeginBalance id
     *
     * @param float|null $beginBalance
     * @return $this
     */
    public function setBeginBalance(?float $beginBalance);

    /**
     * Get End Balance id
     *
     * @return float|null
     */
    public function getEndBalance(): ?float;

    /**
     * Set BeginBalance id
     *
     * @param float|null $endBalance
     * @return $this
     */
    public function setEndBalance(?float $endBalance);

    /**
     * Get transaction time id
     *
     * @return string|null
     */
    public function getTransactionTime(): ?string;

    /**
     * Set transaction Time id
     *
     * @param string|null $transactionTime
     * @return $this
     */
    public function setTransactionTime(?string $transactionTime);

    /**
     * Get CustomerGroupIds id
     *
     * @return string|null
     */
    public function getCustomerGroupIds(): ?string;

    /**
     * Set OrderIncrementId id
     *
     * @param string|null $customerGroupIds
     * @return $this
     */
    public function setCustomerGroupIds(?string $customerGroupIds);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set OrderIncrementId
     *
     * @param string|null $status
     * @return $this
     */
    public function setStatus(?string $status);

    /**
     * Get SpentCredit
     *
     * @return float|null
     */
    public function getSpentCredit(): ?float;

    /**
     * Set SpentCredit
     *
     * @param float|null $spentCredit
     * @return $this
     */
    public function setSpentCredit(?float $spentCredit);

    /**
     * Get Received Credit
     *
     * @return float|null
     */
    public function getReceivedCredit(): ?float;

    /**
     * Set Received Credit
     *
     * @param float|null $receivedCredit
     * @return $this
     */
    public function setReceivedCredit(?float $receivedCredit);
}
