<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\WebposAmastyRewards\Plugin\Webpos\Sales\Data;

use Magento\Framework\ObjectManagerInterface;
use Magestore\Webpos\Api\Data\Sales\Order\CreditmemoExtensionInterface;
use Magestore\Webpos\Api\Data\Sales\Order\CreditmemoExtensionInterfaceFactory;
use Magestore\Webpos\Api\Data\Sales\Order\CreditmemoInterface;
use Magestore\WebposAmastyRewards\Helper\Data;

/**
 * Plugin - Creditmemo
 */
class CreditmemoPlugin
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Data $helper
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Data $helper
    ) {
        $this->objectManager = $objectManager;
        $this->helper = $helper;
    }

    /**
     * After Get Extension Attributes
     *
     * @param CreditmemoInterface $creditmemo
     * @param CreditmemoExtensionInterface|null $creditmemoExtension
     * @return CreditmemoExtensionInterface
     */
    public function afterGetExtensionAttributes(
        CreditmemoInterface $creditmemo,
        CreditmemoExtensionInterface $creditmemoExtension = null
    ) {
        if (!$this->helper->isEnableAmastyRewards()) {
            return $creditmemoExtension;
        }
        if ($creditmemoExtension === null) {
            $creditmemoExtension = $this->objectManager->get(CreditmemoExtensionInterfaceFactory::class)->create();
        }

        $rewardPointsToRefund = $creditmemoExtension->getRewardPointsToRefund();
        if ($rewardPointsToRefund === null) {
            $rewardPointsToRefund = $creditmemo->getRewardPointsToRefund();
            $creditmemoExtension->setRewardPointsToRefund($rewardPointsToRefund);
        }

        $earnedPoints = $creditmemoExtension->getEarnedPoints();
        if ($earnedPoints === null) {
            $earnedPoints = $creditmemo->getEarnedPoints();
            $creditmemoExtension->setEarnedPoints($earnedPoints);
        }

        $creditmemo->setExtensionAttributes($creditmemoExtension);
        return $creditmemoExtension;
    }
}
