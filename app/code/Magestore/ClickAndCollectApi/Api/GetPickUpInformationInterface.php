<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api;

/**
 * Get pickup information interface
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface GetPickUpInformationInterface
{
    /**
     * Get pickup information
     *
     * @param \Magento\Sales\Model\Order $order
     * @param bool $isConvertLocationTimezone
     * @return array
     */
    public function execute(
        \Magento\Sales\Model\Order $order,
        bool $isConvertLocationTimezone = false
    ): string;
}
