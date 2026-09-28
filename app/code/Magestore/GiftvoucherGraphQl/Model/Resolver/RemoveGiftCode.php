<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magestore\Giftvoucher\Helper\Data as DataHelper;
use Magestore\Giftvoucher\Api\Redeem\CheckoutServiceInterface;

/**
 * Apply gift code to cart
 */
class RemoveGiftCode implements ResolverInterface
{
    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var CheckoutServiceInterface
     */
    private $checkoutService;

    /**
     * ApplyRewardPoints constructor.
     *
     * @param DataHelper $dataHelper
     * @param GetCartForUser $getCartForUser
     * @param CheckoutServiceInterface $checkoutService
     */
    public function __construct(
        DataHelper $dataHelper,
        GetCartForUser $getCartForUser,
        CheckoutServiceInterface $checkoutService
    ) {
        $this->dataHelper = $dataHelper;
        $this->getCartForUser = $getCartForUser;
        $this->checkoutService = $checkoutService;
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
        $maskedCartId = $args['cart_id'];

        $currentUserId = $context->getUserId();
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $giftCode = $args['gift_code'] ?? '';
        $isRemoveAll = $args['is_remove_all'] ?? false;

        if (!(int) $this->dataHelper->getGeneralConfig(ApplyGiftCode::MAGESTORE_GIFTVOUCHER_GENERAL_ACTIVE, $storeId)) {
            return null;
        }

        $cart = $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId);
        $cartId = $cart->getId();

        if (!$cart->getItemsCount()) {
            throw new GraphQlInputException(__('The "%1" Cart doesn\'t contain products.', $cartId));
        }

        if ($isRemoveAll) {
            $resultRemoveCode = $this->checkoutService->removeCodes($cartId);
        } else {
            $resultRemoveCode = $this->checkoutService->removeCode($cartId, $giftCode);
        }

        return $this->checkoutService->handleOutputGiftCode($resultRemoveCode, $maskedCartId, $currentUserId, $storeId);
    }
}
