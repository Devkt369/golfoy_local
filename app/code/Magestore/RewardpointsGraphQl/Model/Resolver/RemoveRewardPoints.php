<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\RewardpointsGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magestore\Rewardpoints\Helper\Config as RewardPointConfig;

/**
 * Removes reward points from cart
 */
class RemoveRewardPoints implements ResolverInterface
{
    const XML_ENABLE = 'enable';

    /**
     * @var RewardPointConfig
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
     * ApplyRewardPoints constructor.
     *
     * @param RewardPointConfig $config
     * @param GetCartForUser $getCartForUser
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        RewardPointConfig $config,
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
                __("Reward point is not active.")
            );
        }

        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(
                __('The current customer isn\'t authorized.')
            );
        }

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        /** @var CartInterface $cart */
        $cart = $this->getCartForUser->execute($args['cartId'], $context->getUserId(), $storeId);

        if ($cart->getUsePoint()) {
            //Remove Reward points from cart
            $cart->setUsePoint(0);
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
