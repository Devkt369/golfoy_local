<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\CustomercreditGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magestore\Customercredit\Helper\Creditproduct as StoreCreditConfig;

/**
 * Removes store credit from cart
 */
class RemoveStoreCredit implements ResolverInterface
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
     * RemoveStoreCredit constructor.
     *
     * @param StoreCreditConfig $config
     * @param GetCartForUser $getCartForUser
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        StoreCreditConfig $config,
        GetCartForUser $getCartForUser,
        CartRepositoryInterface $cartRepository
    ) {
        $this->config = $config;
        $this->getCartForUser = $getCartForUser;
        $this->cartRepository = $cartRepository;
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
            throw new LocalizedException(
                __("Store Credit is not active")
            );
        }

        if ($context->getExtensionAttributes()->getIsCustomer() === false) {
            throw new GraphQlAuthorizationException(
                __('The current customer isn\'t authorized.')
            );
        }

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        /** @var CartInterface $cart */
        $cart = $this->getCartForUser->execute($args['cartId'], $context->getUserId(), $storeId);

        if ($cart->getUseCustomerCredit()) {
            //Remove store credit from cart
            $cart->setUseCustomerCredit(0);
            $cart->setCustomerCreditAmountEntered(0);
            $cart->setCustomerCreditAmount(0);
            $cart->collectTotals();
            $this->cartRepository->save($cart);
        }

        return [
            'cart' => [
                'model' => $cart
            ]
        ];
    }
}
