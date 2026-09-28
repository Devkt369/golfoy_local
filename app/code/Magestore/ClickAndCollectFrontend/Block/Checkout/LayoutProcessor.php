<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magestore\ClickAndCollectFrontend\Model\IsInStorePickupDeliveryAvailableForCart;

/**
 * Class LayoutProcessor
 *
 * Use for modify the layout
 */
class LayoutProcessor implements LayoutProcessorInterface
{
    private const PICKUP_PATH = 'click-and-collect';

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryAvailableForCartInterface
     */
    private $inStorePickupDeliveryAvailableForCart;

    /**
     * @param ArrayManager $arrayManager
     * @param ScopeConfigInterface $config
     * @param Session $session
     * @param IsInStorePickupDeliveryAvailableForCart $inStorePickupDeliveryAvailableForCart
     */
    public function __construct(
        ArrayManager $arrayManager,
        ScopeConfigInterface $config,
        Session $session,
        IsInStorePickupDeliveryAvailableForCart $inStorePickupDeliveryAvailableForCart
    ) {
        $this->arrayManager = $arrayManager;
        $this->config = $config;
        $this->checkoutSession = $session;
        $this->inStorePickupDeliveryAvailableForCart = $inStorePickupDeliveryAvailableForCart;
    }

    /**
     * @inheritDoc
     */
    public function process($jsLayout)
    {
        if (!$this->inStorePickupDeliveryAvailableForCart->execute((int)$this->checkoutSession->getQuoteId())) {
            return $this->removeStorePickup($jsLayout);
        } else {
            return $jsLayout;
        }
    }

    /**
     * Remove store pickup ui components from layout.
     *
     * @param array $jsLayout
     *
     * @return array
     */
    private function removeStorePickup(array $jsLayout): array
    {
        $storePickupPath = $this->arrayManager->findPath(self::PICKUP_PATH, $jsLayout);
        $jsLayout = $this->arrayManager->remove($storePickupPath, $jsLayout);

        return $jsLayout;
    }
}
