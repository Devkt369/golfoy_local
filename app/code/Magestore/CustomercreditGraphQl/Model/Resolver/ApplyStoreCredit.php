<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\CustomercreditGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magestore\Customercredit\Helper\Creditproduct as StoreCreditConfig;
use Magestore\Customercredit\Model\Customercredit;
use Magestore\Customercredit\Model\CustomercreditFactory;

/**
 * Apply store credit to cart
 */
class ApplyStoreCredit implements ResolverInterface
{
    const XML_ENABLE = 'enable';

    /**
     * @var StoreCreditConfig
     */
    private $config;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CustomercreditFactory
     */
    private $customerCreditFactory;

    /**
     * ApplyStoreCredit constructor.
     *
     * @param StoreCreditConfig $config
     * @param GetCartForUser $getCartForUser
     * @param CartRepositoryInterface $cartRepository
     * @param CustomercreditFactory $customerCreditFactory
     */
    public function __construct(
        StoreCreditConfig $config,
        GetCartForUser $getCartForUser,
        CartRepositoryInterface $cartRepository,
        CustomercreditFactory $customerCreditFactory
    ) {
        $this->config = $config;
        $this->getCartForUser = $getCartForUser;
        $this->cartRepository = $cartRepository;
        $this->customerCreditFactory = $customerCreditFactory;
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

        if (!(int) $this->config->getGeneralConfig(self::XML_ENABLE, $currentStoreId)) {
            return null;
        }

        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __('The current customer isn\'t authorized.')
            );
        }

        /** @var Customercredit $creditModel */
        $creditModel = $this->customerCreditFactory->create();
        $creditModel->load($context->getUserId(), 'customer_id');
        $creditAvailable = $creditModel ? (float) $creditModel->getCreditBalance() : 0;
        if ($args['creditApplied'] > $creditAvailable) {
            throw new GraphQlInputException(
                __('You can not apply more than you credit balance.')
            );
        }

        /** @var CartInterface $cart */
        $cart = $this->getCartForUser->execute(
            $args['cartId'],
            $context->getUserId(),
            (int)$context->getExtensionAttributes()->getStore()->getId()
        );

        $cart->setUseCustomerCredit(1);
        $cart->setCustomerCreditAmountEntered($args['creditApplied']);
        $cart->setCustomerCreditAmount($args['creditApplied']);
        $cart->collectTotals();
        $this->cartRepository->save($cart);
        return [
            'cart' => [
                'model' => $cart
            ]
        ];
    }
}
