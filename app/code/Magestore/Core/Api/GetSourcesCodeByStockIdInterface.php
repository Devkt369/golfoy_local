<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Core\Api;

/**
 * Get Sources Code By Stock Id
 */
interface GetSourcesCodeByStockIdInterface
{
    /**
     * Get Sources Code By Stock Id
     *
     * @param int $stockId
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(int $stockId);
}
