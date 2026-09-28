<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Plugin\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\BillingAddressManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magestore\ClickAndCollect\Model\Carrier\Method;

/**
 * Disallow use Billing Address for shipping if Quote Delivery Method is In-Store Pickup.
 */
class DoNotUseBillingAddressForShipping
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        CartRepositoryInterface $cartRepository
    ) {
        $this->cartRepository = $cartRepository;
    }

    /**
     * Disallow use Billing Address for shipping if Quote Delivery Method is In-Store Pickup.
     *
     * @param BillingAddressManagementInterface $subject
     * @param int $cartId
     * @param AddressInterface $address
     * @param bool $useForShipping
     *
     * @return array
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAssign(
        BillingAddressManagementInterface $subject,
        int $cartId,
        AddressInterface $address,
        bool $useForShipping = false
    ): array {
        $quote = $this->cartRepository->getActive($cartId);

        if ($this->isClickCollectDeliveryCart($quote)) {
            $useForShipping = false;
        }

        return [$cartId, $address, $useForShipping];
    }

    /**
     * Is instore pickup delivery
     *
     * @param CartInterface $cart
     * @return bool
     */
    public function isClickCollectDeliveryCart(CartInterface $cart)
    {
        if (!$cart->getShippingAddress() || !$cart->getShippingAddress()->getShippingMethod()) {
            return false;
        }

        /** @var Quote $cart */
        return $cart->getShippingAddress()->getShippingMethod() === Method::CLICK_AND_COLLECT_SHIPPING_METHOD;
    }
}
