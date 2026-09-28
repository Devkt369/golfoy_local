<?php

namespace ArtM\Common\Plugin\Service;

use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Api\Data\CreditmemoInterface;

class CreditmemoServicePlugin
{
    /**
     * Around Plugin for refund()
     *
     * @param CreditmemoService $subject
     * @param callable $proceed
     * @param CreditmemoInterface $creditmemo
     * @param bool $offlineRequested
     * @return CreditmemoInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundRefund(
        CreditmemoService $subject,
        callable $proceed,
        CreditmemoInterface $creditmemo,
        $offlineRequested = false
    ) {
        $result = $proceed($creditmemo, $offlineRequested);
        $order = $creditmemo->getOrder();
        $state = 'closed';
        foreach ($order->getAllItems() as $item) {
            if (!$item->getQtyRefunded() || $item->getQtyRefunded() < $item->getQtyOrdered()) {
                $state = 'processing';
                break;
            }
        }
        $order->setState($state)->setStatus($state)->save();

        return $result;
    }
}