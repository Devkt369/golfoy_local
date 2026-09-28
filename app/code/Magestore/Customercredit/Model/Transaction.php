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
 * @package     Magestore_Customercredit
 * @copyright   Copyright (c) 2017 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 *
 */

namespace Magestore\Customercredit\Model;

use Exception;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\GroupManagement;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magestore\Customercredit\Api\Data\TransactionInterface;
use Magestore\Customercredit\Model\ResourceModel\Transaction\Collection;
use Magestore\Customercredit\Model\ResourceModel\Transaction as TransactionResourceModel;

/**
 * Customer credit - Transaction model
 */
class Transaction extends AbstractModel implements TransactionInterface
{
    /**
     * @var CustomerFactory
     */
    protected $customerFactory;
    /**
     * @var CustomercreditFactory
     */
    protected $_customercreditModel;

    /**
     * Transaction constructor.
     * @param Context $context
     * @param Registry $registry
     * @param CustomerFactory $customerFactory
     * @param CustomercreditFactory $customercreditFactory
     * @param TransactionResourceModel $resource
     * @param Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CustomerFactory $customerFactory,
        CustomercreditFactory $customercreditFactory,
        ResourceModel\Transaction $resource,
        Collection $resourceCollection,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->customerFactory = $customerFactory;
        $this->_customercreditModel = $customercreditFactory;
    }

    /**
     * Set resource model and Id field name
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(TransactionResourceModel::class);
        $this->setIdFieldName(self::TRANSACTION_ID);
    }

    /**
     * @inheritDoc
     */
    public function beforeSave()
    {
        $this->setTransactionTime(date("Y-m-d H:i:s"));
        if (!$this->getStatus()) {
            $this->setStatus('Completed');
        }
        return parent::beforeSave();
    }

    /**
     * Function addTransactionHistory
     *
     * @param int $customer_id
     * @param int $transaction_type_id
     * @param string $transaction_detail
     * @param int $order_id
     * @param float $amount_credit
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addTransactionHistory(
        $customer_id,
        $transaction_type_id,
        $transaction_detail,
        $order_id,
        $amount_credit
    ) {
        $spent_credit = 0;
        $received_credit = 0;

        if ($transaction_type_id == TransactionType::TYPE_CANCEL_ORDER
            || $transaction_type_id == TransactionType::TYPE_REFUND_ORDER_INTO_CREDIT
        ) {
            $spent_credit = ($amount_credit < 0) ? $amount_credit : -$amount_credit;
        }

        if ($transaction_type_id == TransactionType::TYPE_CHECK_OUT_BY_CREDIT) {
            $spent_credit = ($amount_credit > 0) ? $amount_credit : -$amount_credit;
        } elseif ($transaction_type_id == TransactionType::TYPE_REFUND_ORDER_INTO_CREDIT) {
            $received_credit = ($amount_credit > 0) ? $amount_credit : -$amount_credit;
        }

        if ($transaction_type_id == TransactionType::TYPE_BUY_CREDIT) {
            $received_credit = ($amount_credit > 0) ? $amount_credit : -$amount_credit;
        }

        // guest checkout
        if (!$customer_id) {
            $customer_group_id = GroupManagement::NOT_LOGGED_IN_ID;
            $customer_id = 0;
            $begin_balance = 0;
            $end_balance = 0;
        } else {
            $customer = $this->customerFactory->create()->load($customer_id);
            $customer_group_id = (float)$customer->getGroupId();
            $begin_balance = $this->_customercreditModel->create()
                ->load($customer_id, 'customer_id')
                ->getCreditBalance();
            $end_balance = $begin_balance + $amount_credit;
        }
        if ($end_balance < 0) {
            $end_balance = 0;
        }
        try {
            $this->setTransactionId(null)
                ->setCustomerId($customer_id)
                ->setTypeTransactionId($transaction_type_id)
                ->setDetailTransaction($transaction_detail)
                ->setOrderIncrementId((int) $order_id)
                ->setAmountCredit($amount_credit)
                ->setBeginBalance($begin_balance)
                ->setEndBalance($end_balance)
                ->setCustomerGroupIds($customer_group_id)
                ->setSpentCredit($spent_credit)
                ->setReceivedCredit($received_credit);
            $this->save();
        } catch (Exception $ex) {
            $this->_logger->error($ex->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function getTransactionId(): ?int
    {
        return $this->getData(self::TRANSACTION_ID) ?
            (int) $this->getData(self::TRANSACTION_ID)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setTransactionId(?int $transactionId)
    {
        return $this->setData(self::TRANSACTION_ID, $transactionId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId(): ?int
    {
        return $this->getData(self::CUSTOMER_ID) ?
            (int) $this->getData(self::CUSTOMER_ID)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId(?int $customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getTypeTransactionId(): ?int
    {
        return $this->getData(self::TYPE_TRANSACTION_ID) ?
            (int) $this->getData(self::TYPE_TRANSACTION_ID)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setTypeTransactionId(?int $typeTransactionId)
    {
        return $this->setData(self::TYPE_TRANSACTION_ID, $typeTransactionId);
    }

    /**
     * @inheritDoc
     */
    public function getDetailTransaction(): ?string
    {
        return $this->getData(self::DETAIL_TRANSACTION) ?
            $this->getData(self::DETAIL_TRANSACTION)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setDetailTransaction(?string $detailTransaction)
    {
        return $this->setData(self::DETAIL_TRANSACTION, $detailTransaction);
    }

    /**
     * @inheritDoc
     */
    public function getOrderIncrementId(): ?int
    {
        return $this->getData(self::ORDER_INCREMENT_ID) ?
            (int) $this->getData(self::ORDER_INCREMENT_ID)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setOrderIncrementId(?int $orderIncrementId)
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $orderIncrementId);
    }

    /**
     * @inheritDoc
     */
    public function getAmountCredit(): ?float
    {
        return $this->getData(self::AMOUNT_CREDIT) ?
            (float) $this->getData(self::AMOUNT_CREDIT)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setAmountCredit(?float $amountCredit)
    {
        return $this->setData(self::AMOUNT_CREDIT, $amountCredit);
    }

    /**
     * @inheritDoc
     */
    public function getBeginBalance(): ?float
    {
        return $this->getData(self::BEGIN_BALANCE) ?
            (float) $this->getData(self::BEGIN_BALANCE)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setBeginBalance(?float $beginBalance)
    {
        return $this->setData(self::BEGIN_BALANCE, $beginBalance);
    }

    /**
     * @inheritDoc
     */
    public function getEndBalance(): ?float
    {
        return $this->getData(self::END_BALANCE) ?
            (float) $this->getData(self::END_BALANCE)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setEndBalance(?float $endBalance)
    {
        return $this->setData(self::END_BALANCE, $endBalance);
    }

    /**
     * @inheritDoc
     */
    public function getTransactionTime(): ?string
    {
        return $this->getData(self::TRANSACTION_TIME) ?
            $this->getData(self::TRANSACTION_TIME)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setTransactionTime(?string $transactionTime)
    {
        return $this->setData(self::TRANSACTION_TIME, $transactionTime);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerGroupIds(): ?string
    {
        return $this->getData(self::CUSTOMER_GROUP_IDS) ?
            $this->getData(self::CUSTOMER_GROUP_IDS)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setCustomerGroupIds(?string $customerGroupIds)
    {
        return $this->setData(self::CUSTOMER_GROUP_IDS, $customerGroupIds);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS) ?
            $this->getData(self::STATUS)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setStatus(?string $status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getSpentCredit(): ?float
    {
        return $this->getData(self::SPENT_CREDIT) ?
            (float) $this->getData(self::SPENT_CREDIT)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setSpentCredit(?float $spentCredit)
    {
        return $this->setData(self::SPENT_CREDIT, $spentCredit);
    }

    /**
     * @inheritDoc
     */
    public function getReceivedCredit(): ?float
    {
        return $this->getData(self::RECEIVED_CREDIT) ?
            (float) $this->getData(self::RECEIVED_CREDIT)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setReceivedCredit(?float $receivedCredit)
    {
        return $this->setData(self::RECEIVED_CREDIT, $receivedCredit);
    }
}
