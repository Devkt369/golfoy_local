<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magestore\Giftvoucher\Api\Data\Redeem\ResponseInterface;
use Magestore\Giftvoucher\Helper\Data as DataHelper;
use Magestore\Giftvoucher\Api\Redeem\CheckoutServiceInterface;

/**
 * Apply gift code to cart
 */
class ApplyGiftCode implements ResolverInterface
{
    const MAGESTORE_GIFTVOUCHER_GENERAL_ACTIVE = 'active';
    const FROM_EXIST_GIFT_CODE = '';

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
        $giftCode = $args['gift_code'];

        if (!(int) $this->dataHelper->getGeneralConfig(self::MAGESTORE_GIFTVOUCHER_GENERAL_ACTIVE, $storeId)) {
            return null;
        }

        $cart = $this->getCartForUser->execute($maskedCartId, $currentUserId, $storeId);
        $cartId = $cart->getId();

        if (!$cart->getItemsCount()) {
            throw new GraphQlInputException(__('The "%1" Cart doesn\'t contain products.', $cartId));
        }

        $usingGiftCodes = $this->checkoutService->getUsingGiftCodes($cartId);
        $isAddNewCode = true;

        foreach ($usingGiftCodes as $giftCodeUsing) {
            if ($giftCodeUsing['code'] == $giftCode) {
                $isAddNewCode = false;
            }
        }

        if (isset($args['amount_applied']) && $args['amount_applied']) {
            if ($isAddNewCode) {
                // Add new code
                $this->checkoutService->applyCodes(
                    $cartId,
                    $usingGiftCodes,
                    self::FROM_EXIST_GIFT_CODE,
                    $giftCode
                );
            }
            // Modify amount
            $resultApplyCode = $this->changeApplyAmount(
                $cartId,
                $giftCode,
                $args['amount_applied']
            );
        } else {
            $resultApplyCode = $this->checkoutService->applyCodes(
                $cartId,
                $usingGiftCodes,
                self::FROM_EXIST_GIFT_CODE,
                $giftCode
            );
        }
        return $this->checkoutService->handleOutputGiftCode(
            $resultApplyCode,
            $maskedCartId,
            $currentUserId,
            $storeId
        );
    }

    /**
     * Change applied gift code amount
     *
     * @param string $cartId
     * @param string $giftCode
     * @param float $amount
     * @return ResponseInterface
     * @throws LocalizedException
     */
    private function changeApplyAmount(string $cartId, string $giftCode, float $amount)
    {
        // Modify amount only
        $usingGiftCodes = $this->checkoutService->getUsingGiftCodes($cartId);

        foreach ($usingGiftCodes as &$usingGiftCode) {
            if ($usingGiftCode['code'] == $giftCode) {
                $usingGiftCode['discount'] = $amount;
            }
        }
        return $this->checkoutService->applyCodes(
            $cartId,
            $usingGiftCodes,
            self::FROM_EXIST_GIFT_CODE,
            ''
        );
    }
}
