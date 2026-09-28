<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Plugin\Sales\Model\Convert;

use Magento\Sales\Model\Convert\Order as CoreConvertOrder;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\Order\Invoice;
use Magestore\Webpos\Helper\Data;

/**
 * Plugin to add location_id to invoice
 */
class Order
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * Order constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * After convert order to invoice
     *
     * @param CoreConvertOrder $subject
     * @param Invoice $result
     * @param OrderModel $order
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterToInvoice(
        CoreConvertOrder $subject,
        Invoice $result,
        OrderModel $order
    ) {
        if ($order->getPosLocationId()) {
            $locationId = $order->getPosLocationId();
            $result->setPosLocationId($locationId);
        }
        return $result;
    }
}
