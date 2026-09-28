<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\CustomercreditGraphQl\Model\Resolver;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magestore\Customercredit\Model\CreditcodeFactory;
use Magestore\Customercredit\Model\CustomercreditFactory;
use Magestore\Customercredit\Model\Source\Status;
use Magestore\Customercredit\Model\TransactionFactory;
use Magestore\Customercredit\Model\TransactionType;

/**
 * Redeem store credit code for customer
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RedeemStoreCreditCode implements ResolverInterface
{
    const MAGESTORE_STORE_CREDIT_GENERAL_ACTIVE = 'customercredit/general/enable';

    const STORE_CREDIT_ACTIVE_STATUS = 1;

    const CREDIT_CODE = 'credit_code';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var CreditcodeFactory
     */
    private $creditcodeFactory;

    /**
     * @var CustomercreditFactory
     */
    private $customercreditFactory;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * RedeemStoreCreditCode constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRegistry $customerRegistry
     * @param CreditcodeFactory $creditcodeFactory
     * @param CustomercreditFactory $customercreditFactory
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CustomerRegistry $customerRegistry,
        CreditcodeFactory $creditcodeFactory,
        CustomercreditFactory $customercreditFactory,
        TransactionFactory $transactionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->customerRegistry = $customerRegistry;
        $this->creditcodeFactory = $creditcodeFactory;
        $this->customercreditFactory = $customercreditFactory;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $currentStoreId = (int)$context->getExtensionAttributes()->getStore()->getId();

        $isStoreCreditActive = (int)$this->scopeConfig->getValue(
            self::MAGESTORE_STORE_CREDIT_GENERAL_ACTIVE,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $currentStoreId
        );
        if (!$isStoreCreditActive) {
            throw new LocalizedException(
                __("Store Credit is not active")
            );
        }

        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __('The current customer isn\'t authorized.')
            );
        }

        /** @var Customer $customer */
        $currentCustomer = $this->customerRegistry->retrieve($context->getUserId());
        if (!$currentCustomer && !$currentCustomer->getId()) {
            throw new GraphQlInputException(
                __('Something went wrong while loading the customer.')
            );
        }
        $creditCode = $args['credit_code'];
        $customerId = $currentCustomer->getId();
        $credit = $this->creditcodeFactory->create()->getCollection()
            ->addFieldToFilter(self::CREDIT_CODE, $creditCode);
        if (!$credit->getSize()) {
            throw new GraphQlNoSuchEntityException(
                __('Code is invalid. Please check again!')
            );
        } elseif ($credit->getFirstItem()->getStatus() != self::STORE_CREDIT_ACTIVE_STATUS) {
            return [
                'status' => false,
                'message' =>  __("Code was canceled.")
            ];
        } else {
            $this->creditcodeFactory->create()->changeCodeStatus($credit->getFirstItem()->getId(), Status::STATUS_USED);
            $creditAmount = $credit->getFirstItem()->getAmountCredit();
            $this->transactionFactory->create()->addTransactionHistory(
                $customerId,
                TransactionType::TYPE_REDEEM_CREDIT,
                __("redeem credit by code '") . $creditCode . "'",
                "",
                $creditAmount
            );
            $this->customercreditFactory->create()->changeCustomerCredit($creditAmount, $customerId);
            return [
                'status' => true,
                'message' =>  __("Code was redeemed successfully!")
            ];
        }
    }
}
